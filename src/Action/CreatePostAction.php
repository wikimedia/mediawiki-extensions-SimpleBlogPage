<?php

namespace MediaWiki\Extension\SimpleBlogPage\Action;

use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\MediaWikiServices;
use SpecialPage;

class CreatePostAction extends \EditAction {

	/**
	 * @return void
	 */
	public function show() {
		if ( !$this->getTitle()->exists() ) {
			/** @var BlogFactory $blogFactory */
			$blogFactory = MediaWikiServices::getInstance()->getService( 'SimpleBlogPage.BlogFactory' );
			$root = $blogFactory->getBlogRootPage( $this->getTitle() );
			$this->getOutput()->redirect(
				SpecialPage::getTitleFor( 'CreateBlogPost', $root->getText() )->getLocalURL()
			);
		} else {
			parent::show();
		}
	}
}
