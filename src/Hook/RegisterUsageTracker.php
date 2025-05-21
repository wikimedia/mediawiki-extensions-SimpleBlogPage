<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\Extension\SimpleBlogPage\Integration\UsageTracker;

class RegisterUsageTracker {

	/**
	 * @param array &$collectorConfig
	 * @return void
	 */
	public function onBSUsageTrackerRegisterCollectors( array &$collectorConfig ) {
		$collectorConfig['simpleblogpage-number-of-posts'] = [
			'class' => UsageTracker::class,
			'config' => []
		];
	}
}
