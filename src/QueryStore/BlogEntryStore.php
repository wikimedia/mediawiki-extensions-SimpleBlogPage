<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use BadFunctionCallException;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class BlogEntryStore implements IStore {

	/**
	 * @var ILoadBalancer
	 */
	private $lb;

	/**
	 * @param ILoadBalancer $lb
	 */
	public function __construct( ILoadBalancer $lb ) {
		$this->lb = $lb;
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
		return new Reader( $this->lb );
	}
}
