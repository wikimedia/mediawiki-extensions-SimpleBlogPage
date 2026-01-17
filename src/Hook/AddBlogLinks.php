<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceDiscovery\ArticlesHomeLink;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class AddBlogLinks implements
	MWStakeCommonUIRegisterSkinSlotComponents,
	SkinTemplateNavigation__UniversalHook
{

	/** @var SpecialPageFactory */
	private $spf;

	/** @var PermissionManager */
	private $permissionManager;

	/** @var Config */
	private $config;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param SpecialPageFactory $spf
	 * @param PermissionManager $permissionManager
	 * @param Config $config
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		SpecialPageFactory $spf, PermissionManager $permissionManager, Config $config, TitleFactory $titleFactory
	) {
		$this->spf = $spf;
		$this->permissionManager = $permissionManager;
		$this->config = $config;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$readBlog = $this->permissionManager->quickUserCan(
			'read',
			RequestContext::getMain()->getUser(),
			$this->titleFactory->makeTitle( NS_BLOG, 'Dummy' )
		);
		$readUserBlog = $this->permissionManager->quickUserCan(
			'read',
			RequestContext::getMain()->getUser(),
			$this->titleFactory->makeTitle( NS_USER_BLOG, 'Dummy' )
		);
		if ( !( $readBlog && $readUserBlog ) ) {
			return;
		}
		if ( $this->config->get( 'SimpleBlogPageShowInMainLinks' ) ) {
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
			'id' => 'ca-simpleblogpage-create',
			'href' => '',
			'text' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
			'title' => $skinTemplate->getContext()->msg( 'simpleblogpage-create-label' )->text(),
		];
	}
}
