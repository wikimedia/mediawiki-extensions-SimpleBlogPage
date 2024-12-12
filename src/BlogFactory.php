<?php

namespace MediaWiki\Extension\SimpleBlogPage;

use InvalidArgumentException;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\WikitextContent;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Extension\SimpleBlogPage\Util\HtmlSnippetCreator;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageProps;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionRenderer;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdateStatus;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PermissionsError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

class BlogFactory implements LoggerAwareInterface {

	/** @var LoggerInterface */
	private $logger;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var ILoadBalancer */
	private $lb;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var Language */
	private $language;

	/** @var RevisionRenderer */
	private $revisionRenderer;

	/** @var PageProps */
	private $pageProps;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param ILoadBalancer $lb
	 * @param TitleFactory $titleFactory
	 * @param Language $language
	 * @param RevisionRenderer $revisionRenderer
	 * @param PageProps $pageProps
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory, ILoadBalancer $lb, TitleFactory $titleFactory,
		Language $language, RevisionRenderer $revisionRenderer, PageProps $pageProps
	) {
		$this->wikiPageFactory = $wikiPageFactory;
		$this->lb = $lb;
		$this->titleFactory = $titleFactory;
		$this->language = $language;
		$this->revisionRenderer = $revisionRenderer;
		$this->pageProps = $pageProps;
		$this->logger = new NullLogger();
	}

	/**
	 * @param Title $target
	 * @param string $text
	 * @param Authority $author
	 * @param array $meta
	 * @return void
	 * @throws PermissionsError
	 */
	public function createBlogEntry( Title $target, string $text, Authority $author, array $meta = [] ) {
		$this->assertActorCan( 'create', $author );
		$this->assertTargetTitleValid( $target );
		$root = $this->getBlogRootPage( $target );
		if ( !$root->exists() ) {
			$this->createRoot( $root, $author );
		}
		$this->logger->debug( 'Creating blog entry at ' . $target->getPrefixedText(), [
			'author' => $author->getUser()->getName(),
			'meta' => $meta,
			'text' => $text,
		] );
		$text = $this->compileBlogText( $text, $meta );
		$status = $this->editPost( $target, $text, $author, EDIT_NEW );
		if ( !$status->isOK() ) {
			$error = $status->getMessages()[0];
			$msg = Message::newFromSpecifier( $error )->text();
			$this->logger->error( 'Create post entry failed: ' . $msg );
			throw new RuntimeException( $msg );
		}
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param Title|null $targetTitle
	 * @return void
	 */
	public function assertTargetTitleValid( ?Title $targetTitle ) {
		if (
			!$targetTitle || $targetTitle->exists() || !$targetTitle->canExist()
			|| ( $targetTitle->getNamespace() !== NS_BLOG && $targetTitle->getNamespace() !== NS_USER_BLOG )
			|| !$targetTitle->isSubpage()
		) {
			throw new InvalidArgumentException( Message::newFromKey( 'simpleblogpage-error-invalid-target-page' ) );
		}
	}

	/**
	 * @param Title|null $title
	 * @param bool $mustExist
	 * @return void
	 */
	public function assertTitleIsBlog( ?Title $title, bool $mustExist = false ) {
		if (
			!$title ||
			( $title->getNamespace() !== NS_BLOG && $title->getNamespace() !== NS_USER_BLOG ) ||
			!$title->isSubpage()
		) {
			throw new InvalidArgumentException( Message::newFromKey( 'simpleblogpage-error-invalid-target-page' ) );
		}
		if ( $mustExist && !$title->exists() ) {
			throw new InvalidArgumentException( Message::newFromKey( 'simpleblogpage-error-invalid-target-page' ) );
		}
	}

	/**
	 * @param RevisionRecord $revision
	 * @return BlogEntry
	 */
	public function getEntryFromRevision( RevisionRecord $revision ): BlogEntry {
		$content = $revision->getContent( SlotRecord::MAIN );
		if (
			( $revision->getPage()->getNamespace() !== NS_BLOG && $revision->getPage()->getNamespace() !== NS_USER_BLOG ) ||
			( $revision->getPage()->getNamespace() === NS_BLOG && !( $content instanceof BlogPostContent ) ) ||
			( $revision->getPage()->getNamespace() === NS_USER_BLOG && !( $content instanceof WikitextContent ) )
		) {
			throw new InvalidArgumentException( Message::newFromKey( 'simpleblogpage-error-invalid-content' ) );
		}
		return $this->constructBlogEntry( $content, $revision );
	}

	/**
	 * Data for API - client-side-rendering
	 *
	 * @param BlogEntry $entry
	 * @param Authority $forUser
	 * @return array
	 * @throws PermissionsError
	 */
	public function serializeForOutput( BlogEntry $entry, Authority $forUser ): array {
		$this->assertActorCan( 'read', $forUser );
		$rendered = $this->renderText( $entry, $forUser );

		try {
			$snippetMaker = new HtmlSnippetCreator( $rendered, 300 );
			$snippet = $snippetMaker->getSnippet();
			$hasMore = $snippetMaker->hasMore();
		} catch ( Throwable $e ) {
			$this->logger->error( 'Failed to create snippet', [
				'page' => $entry->getRevision()->getPage()->getDBkey(),
				'namespace' => $entry->getRevision()->getPage()->getNamespace(),
				'revid' => $entry->getRevision()->getId(),
				'exception' => $e->getMessage(),
			] );
			// Fallback, cannot create snippet, show all
			$snippet = $rendered;
			$hasMore = false;
		}

		$meta = $entry->getMeta( $this->language, $forUser );
		$meta['hasMoreText'] = $hasMore;
		$meta['root'] = $this->getPageDisplayTitle( $entry->getRoot() );
		$meta['name'] = $this->getPageDisplayTitle( $entry->getTitle() );
		return [
			'text' => $snippet,
			'meta' => $meta,
		];
	}

	/**
	 * @return array
	 */
	public function getBlogRootNames(): array {
		$res = $this->getRawBlogRoots();
		$roots = [];
		if ( $res ) {
			foreach ( $res as $row ) {
				$roots[$row->page_title] = $this->getPageDisplayTitle( $this->titleFactory->newFromRow( $row ) );
			}
		}
		return $roots;
	}

	/**
	 * @param Title $page
	 * @return Title
	 */
	public function getBlogRootPage( Title $page ): Title {
		$this->assertTargetTitleValid( $page );
		$rootPage = $page;
		while ( $rootPage && $rootPage->isSubpage() ) {
			$rootPage = $rootPage->getBaseTitle();
		}
		return $rootPage;
	}

	/**
	 * @param Title $page
	 * @return bool
	 */
	public function hasPosts( PageIdentity $page ): bool {
		$res = $this->lb->getConnection( DB_REPLICA )->selectRowCount(
			'page',
			'*',
			[
				'page_namespace' => $page->getNamespace(),
				'page_title LIKE \'' . $page->getDBkey() . '/%\'',
				'page_content_model' => 'blog_post',
			],
		);
		return $res > 0;
	}

	/**
	 * @return IResultWrapper|null
	 */
	private function getRawBlogRoots(): ?IResultWrapper {
		$db = $this->lb->getConnection( DB_REPLICA );
		$query = $db->newSelectQueryBuilder()
			->table( 'page' )
			->fields( [ 'page_id', 'page_title', 'page_namespace' ] )
			->where( [
				'page_namespace' => NS_BLOG,
				'page_content_model' => 'blog_root',
			] );
		$res = $db->query( $query->getSQL(), __METHOD__ );
		if ( !$res ) {
			return null;
		}
		return $res;
	}

	/**
	 * @param WikitextContent $content
	 * @param RevisionRecord $revisionRecord
	 * @return BlogEntry
	 */
	private function constructBlogEntry( WikitextContent $content, RevisionRecord $revisionRecord ): BlogEntry {
		$title = $this->titleFactory->castFromPageReference( $revisionRecord->getPage() );
		$root = $this->getBlogRootPage( $title );
		return new BlogEntry( $title, $content->getText(), $revisionRecord, $root );
	}

	/**
	 * @param Title $root
	 * @param Authority $author
	 * @return PageUpdateStatus
	 */
	private function createRoot( Title $root, Authority $author ): PageUpdateStatus {
		$wikipage = $this->wikiPageFactory->newFromTitle( $root );
		$updater = $wikipage->newPageUpdater( $author );
		$content = new BlogRootContent( '' );
		$updater->setContent( SlotRecord::MAIN, $content );
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment( Message::newFromKey( 'simpleblogpage-create-root-summary' ) ),
			EDIT_NEW
		);
		return $updater->getStatus();
	}

	/**
	 * @param Title $target
	 * @param string $text
	 * @param Authority $author
	 * @param int $flags
	 * @return PageUpdateStatus
	 */
	private function editPost( Title $target, string $text, Authority $author, int $flags = 0 ): PageUpdateStatus {
		$wikipage = $this->wikiPageFactory->newFromTitle( $target );
		$updater = $wikipage->newPageUpdater( $author );
		$content = new BlogPostContent( $text );
		$updater->setContent( SlotRecord::MAIN, $content );
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment( Message::newFromKey( 'simpleblogpage-edit-summary' ) ),
			$flags
		);
		return $updater->getStatus();
	}

	/**
	 * @param string $text
	 * @param array $meta
	 * @return string
	 */
	private function compileBlogText( string $text, array $meta ): string {
		return $text;
	}

	/**
	 * @param string $action
	 * @param Authority $actor
	 * @return void
	 * @throws PermissionsError
	 */
	private function assertActorCan( string $action, Authority $actor ) {
		$rights = [];
		switch ( $action ) {
			case 'create':
				$rights = [ 'createblogpost' ];
				break;
			case 'read':
				$rights = [ 'read' ];
				break;
		}
		if ( empty( $rights ) ) {
			return;
		}
		if ( !$actor->isAllowedAll( ...$rights ) ) {
			throw new PermissionsError( $rights[0] );
		}
	}

	/**
	 * @param BlogEntry $entry
	 * @param Authority|null $forAuthority
	 * @return string|null
	 */
	private function renderText( BlogEntry $entry, ?Authority $forAuthority ): ?string {
		$rr = $this->revisionRenderer->getRenderedRevision( $entry->getRevision(), null, $forAuthority );
		if ( !$rr ) {
			$this->logger->error( 'Failed to render blog entry', [
				'page' => $entry->getRevision()->getPage()->getDBkey(),
				'namespace' => $entry->getRevision()->getPage()->getNamespace(),
				'revid' => $entry->getRevision()->getId(),
			] );
			throw new RuntimeException( Message::newFromKey( 'simpleblogpage-error-rendering-failed' ) );
		}
		$po = $rr->getRevisionParserOutput();
		if ( !$po ) {
			$this->logger->error( 'Failed to render blog entry', [
				'page' => $entry->getRevision()->getPage()->getDBkey(),
				'namespace' => $entry->getRevision()->getPage()->getNamespace(),
				'revid' => $entry->getRevision()->getId(),
			] );
			throw new RuntimeException( Message::newFromKey( 'simpleblogpage-error-rendering-failed' ) );
		}
		return $po->getRawText();
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	private function getPageDisplayTitle( Title $title ) {
		$props = $this->pageProps->getProperties( $title, [ 'displaytitle' ] );
		if ( isset( $props[$title->getArticleID()]['displaytitle'] ) ) {
			return $props[$title->getArticleID()]['displaytitle'];
		}
		return $title->getText();
	}

	/**
	 * @param Title|null $title
	 * @param Title $root
	 * @return string
	 */
	private function getBlogEntryName( ?Title $title, Title $root ) {
		$blogPage = $title->getText();
		$display = $this->getPageDisplayTitle( $title );
		if ( $display !== $blogPage ) {
			return $display;
		}
		return substr( $blogPage, strlen( $root->getText() . '/' ) );
	}
}
