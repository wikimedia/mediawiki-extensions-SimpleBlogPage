<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\Extension\SimpleBlogPage\Maintenance\CreateDefaultBlog;
use MediaWiki\Extension\SimpleBlogPage\Maintenance\MigrateBlueSpiceSocialBlog;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addPostDatabaseUpdateMaintenance(
			CreateDefaultBlog::class
		);
		$updater->addPostDatabaseUpdateMaintenance(
			MigrateBlueSpiceSocialBlog::class
		);
	}
}
