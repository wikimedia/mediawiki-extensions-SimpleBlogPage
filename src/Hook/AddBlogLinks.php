<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceDiscovery\ArticlesHomeLink;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class AddBlogLinks implements
	MWStakeCommonUIRegisterSkinSlotComponents,
	SkinTemplateNavigation__UniversalHook,
	BlueSpiceDiscoveryTemplateDataProviderAfterInit
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
		if ( !$this->permissionManager->userHasRight( $user, 'createblogpost' ) ) {
			return;
		}

		$special = $this->spf->getPage( 'CreateBlogPost' );
		if ( !$special ) {
			return;
		}
		$links['actions']['simpleblogpage-create'] = [
			'text' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
			'title' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
			'href' => '',
			'class' => 'simpleblogpage-create-label'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'panel/create', 'ca-simpleblogpage-create' );
	}
}
