<?php

namespace MediaWiki\Extension\SimpleBlogPage\ContentHandler;

use MediaWiki\Content\WikitextContentHandler;
use MediaWiki\Extension\SimpleBlogPage\Action\CreatePostAction;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;

class BlogPostHandler extends WikitextContentHandler {

	/**
	 * @return string[]
	 */
	public function getActionOverrides() {
		return [
			'edit' => CreatePostAction::class
		];
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return BlogPostContent::class;
	}
}
