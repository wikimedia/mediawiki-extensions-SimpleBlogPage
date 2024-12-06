<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;

class SetDefaultContentModel implements ContentHandlerDefaultModelForHook {

	/**
	 * @inheritDoc
	 */
	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( $title->getNamespace() !== NS_BLOG ) {
			return true;
		}
		// All "root" pages in the blog namespace should use the "blog_root" content model
		// All subpages should use the "blog_post" content model
		if ( $title->isSubpage() ) {
			$model = 'blog_post';
		} else {
			$model = 'blog_root';
		}

		return true;
	}
}
