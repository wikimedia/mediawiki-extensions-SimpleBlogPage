<?php

namespace MediaWiki\Extension\SimpleBlogPage;

class Setup {
	public static function callback() {
		mwsInitComponents();

		$GLOBALS['wgVisualEditorAvailableContentModels']['blog_post'] = 'article';
		$GLOBALS['wgVisualEditorAvailableContentModels']['blog_root'] = 'article';

		$GLOBALS['wgExtensionFunctions'][] = static function() {
			$GLOBALS['wgCommentStreamsAllowedNamespaces'][] = NS_BLOG;
			$GLOBALS['wgCommentStreamsAllowedNamespaces'][] = NS_USER_BLOG;
		};
	}
}
