<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Message\Message;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;

class CreateBlogRootPage implements PageSaveCompleteHook {

	/** @var BlogFactory */
	private $blogFactory;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @param BlogFactory $blogFactory
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct( BlogFactory $blogFactory, WikiPageFactory $wikiPageFactory ) {
		$this->blogFactory = $blogFactory;
		$this->wikiPageFactory = $wikiPageFactory;
	}

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
}