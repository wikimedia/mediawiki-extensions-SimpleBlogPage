<?php

namespace MediaWiki\Extension\SimpleBlogPage\Integration;

use BS\UsageTracker\CollectorResult;
use BS\UsageTracker\Collectors\Base as UsageTrackerBase;

class UsageTracker extends UsageTrackerBase {

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Number of blog posts';
	}

	/**
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'simpleblogpage-number-of-posts';
	}

	/**
	 *
	 * @return CollectorResult
	 */
	public function getUsageData() {
		$res = new CollectorResult( $this );

		$db = $this->services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$row = $db->newSelectQueryBuilder()
			->select( 'COUNT(*) as count' )
			->from( 'page' )
			->where( [
				'page_namespace' => [ NS_BLOG, NS_USER_BLOG ],
				'page_title LIKE ' . $db->addQuotes( '%/%' )
			] )
			->caller( __METHOD__ )
			->fetchRow();

		$res->count = $row ? $row->count : 0;
		return $res;
	}
}
