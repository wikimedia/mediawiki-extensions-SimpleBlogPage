<?php

namespace MediaWiki\Extension\SimpleBlogPage\Rest;

use MediaWiki\Extension\SimpleBlogPage\QueryStore\BlogEntryStore;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Rest\Response;
use MWStake\MediaWiki\Component\CommonWebAPIs\Rest\QueryStore;
use MWStake\MediaWiki\Component\DataStore\IStore;
use MWStake\MediaWiki\Component\DataStore\ResultSet;
use Wikimedia\Rdbms\ILoadBalancer;

class ListHandler extends QueryStore {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;

	/**
	 * @param HookContainer $hookContainer
	 * @param ILoadBalancer $lb
	 */
	public function __construct( HookContainer $hookContainer, ILoadBalancer $lb ) {
		parent::__construct( $hookContainer );
		$this->lb = $lb;
	}


	/**
	 * @return IStore
	 */
	protected function getStore(): IStore {
		return new BlogEntryStore( $this->lb );
	}
}