<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MediaWiki\Title\TitleFactory;
use MediaWiki\Watchlist\WatchedItemStore;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;
	/** @var WatchedItemStore */
	private $watchedItemStore;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var array */
	private array $permissions;

	/**
	 * @param ILoadBalancer $lb
	 * @param WatchedItemStore $watchedItemStore
	 * @param TitleFactory $titleFactory
	 * @param array $permissions
	 */
	public function __construct(
		ILoadBalancer $lb, WatchedItemStore $watchedItemStore, TitleFactory $titleFactory, array $permissions = []
	) {
		parent::__construct();
		$this->lb = $lb;
		$this->watchedItemStore = $watchedItemStore;
		$this->titleFactory = $titleFactory;
		$this->permissions = $permissions;
	}

	/**
	 * @return BlogEntryQuerySchema
	 */
	public function getSchema() {
		return new BlogEntryQuerySchema();
	}

	/**
	 * @param ReaderParams $params
	 * @return PrimaryProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryProvider(
			$this->lb->getConnection( DB_REPLICA ),
			$this->getSchema(),
			$this->permissions
		);
	}

	/**
	 * @return ISecondaryDataProvider
	 */
	protected function makeSecondaryDataProvider() {
		return new SecondaryProvider( $this->watchedItemStore, $this->titleFactory );
	}
}
