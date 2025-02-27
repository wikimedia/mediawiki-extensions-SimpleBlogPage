<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceDiscovery\ArticlesHomeLink;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class AddBlogLinks implements
	MWStakeCommonUIRegisterSkinSlotComponents,
	SkinTemplateNavigation__UniversalHook
{

	/** @var SpecialPageFactory */
	private $spf;

	/** @var PermissionManager */
	private $permissionManager;

	/**
	 * @param SpecialPageFactory $spf
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( SpecialPageFactory $spf, PermissionManager $permissionManager ) {
		$this->spf = $spf;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'MainLinksPanel',
			[
				'simpleblogpage-blog-overview' => [
					'factory' => function () {
						return new ArticlesHomeLink( $this->spf, [] );
					},
					'position' => 30
				]
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $skinTemplate, &$links ): void {
		$user = $skinTemplate->getUser();
		$overviewSpecial = $this->spf->getPage( 'Blogs' );
		$links['user-menu']['simpleblog_myblog'] = [
			'id' => 'pt-simpleblog_myblog',
			'href' => $overviewSpecial->getPageTitle( 'User_blog:' . $user->getName() )->getLocalURL(),
			'text' => $skinTemplate->msg( 'simpleblogpage-user-blogoverview-label' )->plain(),
			'position' => 50,
		];

		if ( !$this->permissionManager->userHasRight( $user, 'createblogpost' ) ) {
			return;
		}
		$skinTemplate->getOutput()->addModules( [ 'ext.simpleBlogPage.bootstrap' ] );
		$links['actions']['simpleblogpage-create'] = [
			'text' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
			'title' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
			'href' => '',
			'class' => 'simpleblogpage-create-label'
		];
	}
}
