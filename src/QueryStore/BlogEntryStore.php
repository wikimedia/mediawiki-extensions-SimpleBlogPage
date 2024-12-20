<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use BadFunctionCallException;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Watchlist\WatchedItemStore;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class BlogEntryStore implements IStore {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;
	/** @var WatchedItemStore */
	private $watchedItemStore;

	/** @var TitleFactory */
	private $titleFactory;

	public function __construct( ILoadBalancer $lb, WatchedItemStore $watchedItemStore, TitleFactory $titleFactory ) {
		$this->lb = $lb;
		$this->watchedItemStore = $watchedItemStore;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @return void
	 */
	public function getWriter() {
		throw new BadFunctionCallException( 'Not implemented' );
	}

	/**
	 * @return Reader
	 */
	public function getReader() {
		return new Reader( $this->lb, $this->watchedItemStore, $this->titleFactory );
	}
}
