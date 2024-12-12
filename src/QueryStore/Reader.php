<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;

	/**
	 * @param ILoadBalancer $lb
	 */
	public function __construct( ILoadBalancer $lb ) {
		parent::__construct();
		$this->lb = $lb;
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
			$this->getSchema()
		);
	}

	/**
	 * @return void
	 */
	protected function makeSecondaryDataProvider() {
		return null;
	}
}
