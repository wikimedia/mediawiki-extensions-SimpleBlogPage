<?php

namespace MediaWiki\Extension\SimpleBlogPage;

use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;

class BlogPermissionChecker {

	/**
	 * @param TitleFactory $titleFactory
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly PermissionManager $permissionManager
	) {
	}

	/**
	 * @param Authority $authority
	 * @return array
	 */
	public function getGeneralReadPermissions( Authority $authority ): array {
		$blogPage = $this->titleFactory->makeTitle( NS_BLOG, 'PermissionCheck' );
		$userBlogPage = $this->titleFactory->makeTitle( NS_USER_BLOG, 'PermissionCheck' );
		return [
			NS_BLOG => $this->permissionManager->quickUserCan( 'read', $authority, $blogPage ),
			NS_USER_BLOG => $this->permissionManager->quickUserCan( 'read', $authority, $userBlogPage ),
		];
	}

	/**
	 * @param Authority $user
	 * @param Title|null $blogRoot
	 * @return bool
	 */
	public function canUserPostInBlog( Authority $user, ?Title $blogRoot ): bool {
		if ( $blogRoot && $blogRoot->getNamespace() === NS_USER_BLOG ) {
			if ( str_replace( ' ', '_', $user->getName() ) !== $blogRoot->getDBkey() ) {
				return false;
			}
		}
		if ( $blogRoot && !$this->userCanRead( $user, $blogRoot ) ) {
			return false;
		}

		return $user->isAllowed( 'createblogpost' );
	}

	/**
	 * @param Authority $user
	 * @param Title $title
	 * @return bool
	 */
	public function userCanRead( Authority $user, Title $title ): bool {
		return $this->permissionManager->quickUserCan( 'read', $user, $title );
	}

	/**
	 * @param Authority $user
	 * @return bool
	 */
	public function canCreateBlogs( Authority $user ): bool {
		return $this->permissionManager->userHasRight( $user, 'createblogpost' );
	}

}
