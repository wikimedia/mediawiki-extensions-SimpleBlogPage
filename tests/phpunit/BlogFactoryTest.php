<?php

namespace MediaWiki\Extension\SimpleBlogPage\Tests;

use InvalidArgumentException;
use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\SimpleBlogPage\BlogEntry;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Language\Language;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionRenderer;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\Storage\PageUpdateStatus;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use PermissionsError;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\SelectQueryBuilder;
use WikiPage;

class BlogFactoryTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::createBlogEntry
	 * @dataProvider  provideCreationData
	 * @return void
	 * @throws PermissionsError
	 */
	public function testCreateBlogEntry( bool $titleValid, bool $authorValid, string $expectException = '' ) {
		$author = $this->createMock( Authority::class );
		$author->method( 'isAllowedAll' )->willReturn( $authorValid );
		if ( !$titleValid ) {
			$targetTitle = $this->createMock( Title::class );
			$targetTitle->method( 'getNamespace' )->willReturn( $titleValid ? NS_BLOG : NS_MAIN );
			$targetTitle->method( 'getDBkey' )->willReturn( $titleValid ? 'Foo/Bar' : 'Foo' );
		} else {
			$targetTitle = Title::newFromText( 'Blog:Foo/Bar' );
		}

		$updaterMock = $this->createMock( PageUpdater::class );
		$updaterMock->method( 'getStatus' )->willReturn( PageUpdateStatus::newGood() );
		$wpFactoryMock = $this->createMock( WikiPageFactory::class );
		$wpMock = $this->createMock( WikiPage::class );
		$wpMock->method( 'newPageUpdater' )->willReturn( $updaterMock );
		if ( !$expectException ) {
			$wpFactoryMock->expects( $this->exactly( 2 ) )
				->method( 'newFromTitle' )->willReturn( $wpMock );
			$updaterMock->expects( $this->exactly( 2 ) )
				->method( 'setContent' );
			$updaterMock->expects( $this->exactly( 2 ) )
				->method( 'saveRevision' )
				->willReturn( $this->createMock( RevisionRecord::class ) );
		}
		$blogFactory = $this->getBlogFactory( null, $wpFactoryMock );
		if ( $expectException ) {
			$this->expectException( $expectException );
		}
		$blogFactory->createBlogEntry( $targetTitle, 'dummy', $author );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::assertTargetTitleValid
	 * @dataProvider provideTargetTitles
	 */
	public function testAssertTargetTitleValid( Title $title, bool $expectException ) {
		$factory = $this->getBlogFactory();
		if ( $expectException ) {
			$this->expectException( InvalidArgumentException::class );
		}
		$factory->assertTargetTitleValid( $title );
		$this->assertTrue( true );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::assertTitleIsBlog
	 * @dataProvider provideTargetTitles
	 * @return void
	 */
	public function testAssertTitleIsBlog( Title $title, bool $expectException ) {
		$factory = $this->getBlogFactory();
		if ( $expectException ) {
			$this->expectException( InvalidArgumentException::class );
		}
		$factory->assertTargetTitleValid( $title );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider provideRetrievalData
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::isBlog
	 */
	public function testGetEntryFromRevision( int $ns, string $contentClass, string $expectException = '' ) {
		$content = $this->createMock( $contentClass );
		$content->method( 'getText' )->willReturn( 'dummy' );
		$title = Title::makeTitle( $ns, 'Foo/Bar' );
		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getPage' )->willReturn( $title );
		$revision->method( 'getContent' )->willReturn( $content );

		if ( $expectException ) {
			$this->expectException( $expectException );
		}
		$blogFactory = $this->getBlogFactory();
		$entry = $blogFactory->getEntryFromRevision( $revision );
		$this->assertInstanceOf( BlogEntry::class, $entry );
		$this->assertSame( 'dummy', $entry->getText() );
		$this->assertSame( 'Foo/Bar', $entry->getTitle()->getText() );
		$this->assertSame( 'Foo', $entry->getRoot()->getText() );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::serializeForOutput
	 * @dataProvider provideIsAllowed
	 * @return void
	 */
	public function testSerializeForOutput( bool $isAllowed ) {
		$revisionMock = $this->createMock( RevisionRecord::class );
		$revisionMock->method( 'getTimestamp' )->willReturn( '20241010010101' );
		$entry = new BlogEntry(
			Title::newFromText( 'Blog:Foo/Bar' ),
			'dummy',
			$revisionMock,
			Title::newFromText( 'Blog:Foo' )
		);
		$blogFactory = $this->getBlogFactory();
		$authority = $this->createMock( Authority::class );
		$authority->method( 'isAllowedAll' )->willReturn( $isAllowed );
		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( 'John' );
		if ( !$isAllowed ) {
			$this->expectException( PermissionsError::class );
		}
		$authority->method( 'getUser' )->willReturn( $user );
		$revisionMock->method( 'getUser' )->willReturn( $user );
		$serialized = $blogFactory->serializeForOutput( $entry, $authority );

		$expected = [
			'text' => '<div>dummy <div style="clear:both;"></div></div>',
			'meta' => [
				'timestamp' => '20241010010101',
				'userTimestamp' => '2024.10.10 01:01:01',
				'revisionId' => null,
				'author' => 'John',
				'root' => 'Foo',
				'rootPage' => 'Blog:Foo',
				'entryPage' => 'Blog:Foo/Bar',
				'hasMoreText' => false,
				'name' => 'Foo/Bar',
			]
		];
		$this->assertSame( $expected, $serialized );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::getBlogRootNames
	 * @return void
	 */
	public function testGetBlogRootNames() {
		$dbMock = $this->createMock( IDatabase::class );
		$dbMock->method( 'newSelectQueryBuilder' )->willReturn( new SelectQueryBuilder( $dbMock ) );
		$dbMock->expects( $this->once() )
			->method( 'query' )
			->willReturn( new FakeResultWrapper( [
				[
					'page_id' => 1,
					'page_namespace' => NS_BLOG,
					'page_title' => 'Foo'
				],
				[
					'page_id' => 2,
					'page_namespace' => NS_BLOG,
					'page_title' => 'Bar'
				]
			] ) );

		$blogFactory = $this->getBlogFactory( $dbMock );
		$this->assertSame( [ 'Foo' => 'DisplayFoo', 'Bar' => 'Bar' ], $blogFactory->getBlogRootNames() );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::getBlogRootPage
	 * @dataProvider provideTargetTitles
	 * @return void
	 */
	public function testGetBlogRootPage( Title $title, bool $expectException, string $expectedRoot = '' ) {
		$blogFactory = $this->getBlogFactory();

		if ( $expectException ) {
			$this->expectException( InvalidArgumentException::class );
		}
		$root = $blogFactory->getBlogRootPage( $title );
		if ( !$expectException ) {
			$this->assertSame( $expectedRoot, $root->getText() );
		}
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleBlogPage\BlogFactory::getBlogRootTitle
	 * @return void
	 */
	public function testHasPosts() {
		$dbMock = $this->createMock( IDatabase::class );
		$dbMock->expects( $this->once() )
			->method( 'selectRowCount' )
			->with(
				'page',
				'*',
				[
					'page_namespace' => NS_BLOG,
					'page_title LIKE \'Foo/%\'',
					'page_content_model' => 'blog_post',
				]
			)
			->willReturn( 1 );

		$blogFactory = $this->getBlogFactory( $dbMock );
		$blogFactory->hasPosts( Title::newFromText( 'Blog:Foo' ) );
	}

	/**
	 * @return array[]
	 */
	public function provideTargetTitles(): array {
		return [
			'root' => [
				Title::newFromText( 'Blog:Root' ),
				true,
				'Root',
			],
			'wrong-ns' => [
				Title::newFromText( 'Test/ABC' ),
				true,
			],
			'valid' => [
				Title::newFromText( 'Blog:FOO/Bar' ),
				false,
				'FOO'
			],
			'user-blog-valid' => [
				Title::newFromText( 'User_blog:FOO/Bar' ),
				false,
				'FOO'
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function provideCreationData(): array {
		return [
			'invalid-author' => [
				'titleValid' => true,
				'authorValid' => false,
				'expectException' => PermissionsError::class
			],
			'invalid-page' => [
				'titleValid' => false,
				'authorValid' => true,
				'expectException' => InvalidArgumentException::class
			],
			'valid' => [
				'titleValid' => true,
				'authorValid' => true,
				'expectException' => ''
			],
		];
	}

	/**
	 * @return array[]
	 */
	public function provideRetrievalData(): array {
		return [
			'invalid-ns' => [
				'ns' => NS_MAIN,
				'contentClass' => BlogPostContent::class,
				'exception' => InvalidArgumentException::class,
			],
			'invalid-cm' => [
				'ns' => NS_BLOG,
				'contentClass' => JsonContent::class,
				'expectException' => InvalidArgumentException::class
			],
			'valid' => [
				'ns' => NS_BLOG,
				'contentClass' => BlogPostContent::class,
				'expectException' => ''
			]
		];
	}

	/**
	 * @return array
	 */
	public function provideIsAllowed(): array {
		return [
			'not-allowed' => [ 'allowed' => false ],
			'allowed' => [ 'allowed' => true ],
		];
	}

	/**
	 * @return BlogFactory
	 */
	private function getBlogFactory( ?IDatabase $db = null, ?WikiPageFactory $wpFactory = null ) {
		return new BlogFactory(
			$wpFactory ?? $this->getWikiPageFactoryMock(),
			$this->getLBMock( $db ),
			$this->getTitleFactoryMock(),
			$this->getLanguageMock(),
			$this->getRevisionRendererMock(),
			$this->getPagePropsMock(),
		);
	}

	/**
	 * @return WikiPageFactory
	 */
	private function getWikiPageFactoryMock() {
		$mock = $this->createMock( WikiPageFactory::class );
		return $mock;
	}

	/**
	 * @param IDatabase|null $db
	 * @return ILoadBalancer
	 */
	private function getLBMock( ?IDatabase $db = null ) {
		$mock = $this->createMock( ILoadBalancer::class );
		$mock->method( 'getConnection' )->willReturn( $db );
		return $mock;
	}

	/**
	 * @return TitleFactory
	 */
	private function getTitleFactoryMock() {
		$mock = $this->createMock( TitleFactory::class );
		$mock->method( 'newFromRow' )->willReturnCallback( function ( $row ) {
			$titleMock = $this->createMock( Title::class );
			$titleMock->method( 'getArticleID' )->willReturn( $row->page_id );
			$titleMock->method( 'getNamespace' )->willReturn( $row->page_namespace );
			$titleMock->method( 'getText' )->willReturn( $row->page_title );
			$titleMock->method( 'getDBkey' )->willReturn( $row->page_title );
			return $titleMock;
		} );
		$mock->method( 'castFromPageReference' )->willReturnCallback( function( PageReference $ref ) {
			return Title::makeTitle( $ref->getNamespace(), $ref->getDBkey() );
		} );
		return $mock;
	}

	/**
	 * @return Language
	 */
	private function getLanguageMock() {
		$mock = $this->createMock( Language::class );
		$mock->method( 'userTimeAndDate' )->willReturn( '2024.10.10 01:01:01' );
		return $mock;
	}

	/**
	 * @return RevisionRenderer
	 */
	private function getRevisionRendererMock() {
		$mock = $this->createMock( RevisionRenderer::class );
		$rrMock = $this->createMock( RenderedRevision::class );
		$poMock = $this->createMock( ParserOutput::class );
		$poMock->method( 'getRawText' )->willReturn( 'dummy' );
		$rrMock->method( 'getRevisionParserOutput' )->willReturn( $poMock );
		$mock->method( 'getRenderedRevision' )->willReturn( $rrMock );
		return $mock;
	}

	/**
	 * @return PageProps
	 */
	private function getPagePropsMock() {
		$mock = $this->createMock( PageProps::class );
		$mock->method( 'getProperties' )->willReturn( [
			'1' => [ 'displaytitle' => 'DisplayFoo' ],
			'3' => [ 'displaytitle' => 'DisplayBaz' ],
		] );
		return $mock;
	}
}