<?php

namespace MediaWiki\Extension\SimpleBlogPage;

use InvalidArgumentException;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdateStatus;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserIdentity;
use PermissionsError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BlogFactory implements LoggerAwareInterface {

	/** @var LoggerInterface */
	private $logger;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var RevisionStore */
	private $revisionStore;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var Language */
	private $language;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param RevisionStore $revisionStore
	 * @param TitleFactory $titleFactory
	 * @param Language $language
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory, RevisionStore $revisionStore, TitleFactory $titleFactory, Language $language
	) {
		$this->wikiPageFactory = $wikiPageFactory;
		$this->revisionStore = $revisionStore;
		$this->titleFactory = $titleFactory;
		$this->language = $language;
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
			throw new \RuntimeException( $msg );
		}
	}

	public function setLogger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param Title $page
	 * @return Title|null
	 */
	public function getBlogRootPage( Title $page ) {
		$rootPage = $page;
		while ( $rootPage->isSubpage() ) {
			$rootPage = $rootPage->getBaseTitle();
		}
		if ( !$rootPage || $rootPage->getContentModel() !== 'blog_root' ) {
			throw new InvalidArgumentException( Message::newFromKey( 'simpleblogpage-error-invalid-root-page' ) );
		}
		return $rootPage;
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
	 * @param BlogPostContent $content
	 * @param PageReference $page
	 * @param int|null $revisionId
	 * @param UserIdentity|null $forUser
	 * @return void
	 */
	public function newFromContent(
		BlogPostContent $content, PageReference $page, ?int $revisionId = null, ?UserIdentity $forUser = null
	) {
		$title = $this->titleFactory->castFromPageReference( $page );
		$revision = $revisionId ?
			$this->revisionStore->getRevisionById( $revisionId ) :
			$this->revisionStore->getRevisionByTitle( $title );
		if ( !$revision ) {
			throw new InvalidArgumentException( 'Revision not found' );
		}

		$data = ;
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
		}
		if ( empty( $rights ) ) {
			return;
		}
		if ( !$actor->isAllowedAll( ...$rights ) ) {
			throw new PermissionsError( 'createblogpost' );
		}
	}

}