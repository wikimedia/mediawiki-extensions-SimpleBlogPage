<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogPermissionChecker;
use MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceDiscovery\ArticlesHomeLink;
use MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceEclipse\ActionEntryPoint;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class AddBlogLinks implements
	MWStakeCommonUIRegisterSkinSlotComponents,
	SkinTemplateNavigation__UniversalHook
{

	/** @var SpecialPageFactory */
	private $spf;

	/** @var Config */
	private $config;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var BlogPermissionChecker */
	private $permissionChecker;

	/**
	 * @param SpecialPageFactory $spf
	 * @param Config $config
	 * @param TitleFactory $titleFactory
	 * @param BlogPermissionChecker $permissionChecker
	 */
	public function __construct(
		SpecialPageFactory $spf, Config $config, TitleFactory $titleFactory, BlogPermissionChecker $permissionChecker
	) {
		$this->spf = $spf;
		$this->config = $config;
		$this->titleFactory = $titleFactory;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$canReadAnything = false;
		$context = RequestContext::getMain();
		$user = $context->getUser();
		foreach ( $this->permissionChecker->getGeneralReadPermissions( $user ) as $canRead ) {
			if ( $canRead ) {
				$canReadAnything = true;
				break;
			}
		}
		if ( !$canReadAnything ) {
			return;
		}
		if ( $this->config->get( 'SimpleBlogPageShowInMainLinks' ) ) {
			$skin = $context->getSkin();
			$registry->register(
				'MainLinksPanel',
				[
					'simpleblogpage-blog-overview' => [
						'factory' => function () use ( $skin ) {
							if ( is_a( $skin, 'SkinBlueSpiceEclipseSkin', true ) ) {
								return new ActionEntryPoint( $this->spf, $this->permissionChecker );
							}
							return new ArticlesHomeLink( $this->spf, [] );
						},
						'position' => 30
					]
				]
			);
		}
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
		if ( !$this->permissionChecker->canCreateBlogs( $user ) ) {
			return;
		}
		$skinTemplate->getOutput()->addModules( [ 'ext.simpleBlogPage.bootstrap' ] );
		$links['actions']['simpleblogpage-create'] = [
			'id' => 'ca-simpleblogpage-create',
			'href' => '',
			'text' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
			'title' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
		];
	}
}
