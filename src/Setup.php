<?php

namespace MediaWiki\Extension\SimpleBlogPage;

class Setup {
	public static function callback() {
		mwsInitComponents();

		if ( isset( $GLOBALS['wgVisualEditorAvailableContentModels'] ) ) {
			$GLOBALS['wgVisualEditorAvailableContentModels']['blog_post'] = 'article';
			$GLOBALS['wgVisualEditorAvailableContentModels']['blog_root'] = 'article';
		}

		$GLOBALS['wgExtensionFunctions'][] = static function () {
			if (
				isset( $GLOBALS['wgCommentStreamsAllowedNamespaces'] ) &&
				is_array( $GLOBALS['wgCommentStreamsAllowedNamespaces'] )
			) {
				// This config is `null` by default, which will implicitly fallback
				// to all content namespaces. We don't want to override this default
				// but if it's already set, we can add the blog namespaces to it.
				$GLOBALS['wgCommentStreamsAllowedNamespaces'][] = NS_BLOG;
				$GLOBALS['wgCommentStreamsAllowedNamespaces'][] = NS_USER_BLOG;
			}
		};

		if ( isset( $GLOBALS['wgContentStabilizationUnavailableNamespaces'] ) ) {
			$GLOBALS['wgContentStabilizationUnavailableNamespaces'][] = NS_BLOG;
			$GLOBALS['wgContentStabilizationUnavailableNamespaces'][] = NS_USER_BLOG;
		}
		if ( isset( $GLOBALS['smwgNamespacesWithSemanticLinks'] ) ) {
			$GLOBALS['smwgNamespacesWithSemanticLinks'][NS_BLOG] = true;
			$GLOBALS['smwgNamespacesWithSemanticLinks'][NS_USER_BLOG] = true;
		}
	}
}
