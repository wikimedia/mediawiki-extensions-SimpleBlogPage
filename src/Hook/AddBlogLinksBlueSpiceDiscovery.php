<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;

class AddBlogLinksBlueSpiceDiscovery implements BlueSpiceDiscoveryTemplateDataProviderAfterInit {

	/**
	 * @inheritDoc
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'panel/create', 'ca-simpleblogpage-create' );
	}
}
