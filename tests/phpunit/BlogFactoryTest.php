<?php

namespace MediaWiki\Extension\SimpleBlogPage\Tests;

use InvalidArgumentException;
use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\SimpleBlogPage\BlogEntry;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionRenderer;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use PermissionsError;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\SelectQueryBuilder;

class BlogFactoryTest extends TestCase {

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
				'name' => 'Bar',
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
		$dbMock->expects( $this->exactly( 2 ) )
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

		$expected = [
			'Blog:Foo' => [
				'display' => 'DisplayFoo',
				'dbKey' => 'Foo',
				'type' => 'global'
			],
			'Blog:Bar' => [
				'display' => 'Bar',
				'dbKey' => 'Bar',
				'type' => 'global'
			]
		];
		$blogFactory = $this->getBlogFactory( $dbMock );
		$this->assertSame( $expected, $blogFactory->getBlogRootNames() );

		$newExpected = [ 'User_blog:John' => [
			'display' => Message::newFromKey( 'simpleblogpage-user-blog-label' )->text(),
			'dbKey' => 'John',
			'type' => 'user'
		] ] + $expected;
		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( 'John' );
		$this->assertSame( $newExpected, $blogFactory->getBlogRootNames( $user ) );
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
			$this->getLBMock( $db ),
			$this->getTitleFactoryMock(),
			$this->getLanguageMock(),
			$this->getRevisionRendererMock(),
			$this->getPagePropsMock(),
		);
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
			$titleMock->method( 'getPrefixedDBkey' )->willReturn( 'Blog:' . $row->page_title );
			return $titleMock;
		} );
		$mock->method( 'makeTitle' )->willReturnCallback( function ( $ns, $title ) {
			$titleMock = $this->createMock( Title::class );
			$titleMock->method( 'getArticleID' )->willReturn( 1 );
			$titleMock->method( 'getNamespace' )->willReturn( $ns );
			$titleMock->method( 'getText' )->willReturn( $title );
			$titleMock->method( 'getDBkey' )->willReturn( $title );
			$titleMock->method( 'getPrefixedDBkey' )->willReturn(
				( $ns === NS_USER_BLOG ? 'User_blog:' : 'Blog:' ) . $title
			);
			return $titleMock;
		} );
		$mock->method( 'castFromPageReference' )->willReturnCallback( static function ( PageReference $ref ) {
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
