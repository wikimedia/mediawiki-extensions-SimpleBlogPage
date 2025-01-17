<?php

namespace MediaWiki\Extension\SimpleBlogPage\Maintenance;

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\Message\Message;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\User;

require_once dirname( __DIR__, 4 ) . '/maintenance/Maintenance.php';

class CreateDefaultBlog extends LoggedUpdateMaintenance {

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$defaultBlog = Message::newFromKey( 'simpleblogpage-blog-type-general' )->text();
		$page = $this->getServiceContainer()->getTitleFactory()->makeTitle( NS_BLOG, $defaultBlog );
		if ( $page->exists() ) {
			$this->output( "Default blog already exists. Nothing to do.\n" );
			return true;
		}

		$this->output( "Creating default blog...\n" );
		$content = new BlogRootContent( '' );
		$author = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
		$wp = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $page );
		$updater = $wp->newPageUpdater( $author );
		$updater->setContent( SlotRecord::MAIN, $content );
		$updater->saveRevision( CommentStoreComment::newUnsavedComment( 'Create default blog' ) );
		if ( !$updater->getStatus()->isOK() ) {
			$this->error( "failed\n" );
			return false;
		}
		$this->output( "done\n" );
		return true;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'simpleblogpage-create-default-blog';
	}
}

$maintClass = CreateDefaultBlog::class;
require_once RUN_MAINTENANCE_IF_MAIN;
