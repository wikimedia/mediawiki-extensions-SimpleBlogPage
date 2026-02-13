<?php

namespace MediaWiki\Extension\SimpleBlogPage\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogPermissionChecker;
use MediaWiki\Extension\SimpleBlogPage\QueryStore\BlogEntryStore;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Watchlist\WatchedItemStore;
use MWStake\MediaWiki\Component\CommonWebAPIs\Rest\QueryStore;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class ListHandler extends QueryStore {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;
	/** @var WatchedItemStore */
	private $watchedItemStore;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var BlogPermissionChecker */
	private BlogPermissionChecker $permissionChecker;

	/**
	 * @param HookContainer $hookContainer
	 * @param ILoadBalancer $lb
	 * @param WatchedItemStore $watchedItemStore
	 * @param TitleFactory $titleFactory
	 * @param BlogPermissionChecker $permissionChecker
	 */
	public function __construct(
		HookContainer $hookContainer, ILoadBalancer $lb, WatchedItemStore $watchedItemStore,
		TitleFactory $titleFactory, BlogPermissionChecker $permissionChecker
	) {
		parent::__construct( $hookContainer );
		$this->lb = $lb;
		$this->watchedItemStore = $watchedItemStore;
		$this->titleFactory = $titleFactory;
		$this->permissionChecker = $permissionChecker;
	}

	/**
	 * @return IStore
	 */
	protected function getStore(): IStore {
		$permissions = $this->permissionChecker->getGeneralReadPermissions( RequestContext::getMain()->getUser() );
		return new BlogEntryStore( $this->lb, $this->watchedItemStore, $this->titleFactory, $permissions );
	}
}
