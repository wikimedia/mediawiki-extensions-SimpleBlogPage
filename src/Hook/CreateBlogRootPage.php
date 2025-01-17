<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Hook\UserLoginCompleteHook;
use MediaWiki\Message\Message;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\TitleFactory;

class CreateBlogRootPage implements PageSaveCompleteHook, UserLoginCompleteHook {

	/** @var BlogFactory */
	private $blogFactory;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param BlogFactory $blogFactory
	 * @param WikiPageFactory $wikiPageFactory
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		BlogFactory $blogFactory, WikiPageFactory $wikiPageFactory, TitleFactory $titleFactory
	) {
		$this->blogFactory = $blogFactory;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		if ( !( $flags & EDIT_NEW ) ) {
			return;
		}
		$title = $wikiPage->getTitle();
		if ( $title->getNamespace() !== NS_BLOG && $title->getNamespace() !== NS_USER_BLOG ) {
			return;
		}
		if ( !$title->isSubpage() ) {
			return;
		}
		$root = $this->blogFactory->getBlogRootPage( $title );
		if ( $root->exists() ) {
			return;
		}
		$rootWp = $this->wikiPageFactory->newFromTitle( $root );
		$updater = $rootWp->newPageUpdater( $user );
		$updater->setContent( SlotRecord::MAIN, new BlogRootContent( '' ) );
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				Message::newFromKey( 'simpleblogpage-create-root-summary' )->text()
			)
		);
		if ( !$updater->getStatus()->isGood() ) {
			$this->blogFactory->getLogger()->error( 'Failed creating blog root page after blog post creation', [
				'title' => $title->getPrefixedText(),
			] + $updater->getStatus()->getMessages() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onUserLoginComplete( $user, &$inject_html, $direct ) {
		$userBlogPage = $this->titleFactory->makeTitle( NS_USER_BLOG, $user->getName() );
		if ( $userBlogPage->exists() ) {
			return;
		}
		$userBlogWp = $this->wikiPageFactory->newFromTitle( $userBlogPage );
		$updater = $userBlogWp->newPageUpdater( $user );
		$updater->setContent( SlotRecord::MAIN, new BlogRootContent( '' ) );
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				Message::newFromKey( 'simpleblogpage-create-root-summary' )->text()
			)
		);
	}
}
