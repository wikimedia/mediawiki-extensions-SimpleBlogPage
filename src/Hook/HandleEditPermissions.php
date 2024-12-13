<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;

class HandleEditPermissions implements getUserPermissionsErrorsHook {

	/**
	 * @inheritDoc
	 */
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		if ( $title->getNamespace() !== NS_BLOG && $title->getNamespace() !== NS_USER_BLOG ) {
			return true;
		}
		if ( $action !== 'edit' ) {
			return true;
		}
		if ( $title->getNamespace() === NS_USER_BLOG ) {
			if ( $title->getBaseTitle()->getText() !== $user->getName() ) {
				$result = 'simpleblogpage-no-blog-no-create';
				return false;
			}
		}
		if ( !$user->isAllowed( 'createblogpost' ) ) {
			$result = 'simpleblogpage-no-blog-no-create';
			return false;
		}
		return true;
	}
}
