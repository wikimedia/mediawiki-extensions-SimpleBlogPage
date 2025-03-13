<?php

namespace MediaWiki\Extension\SimpleBlogPage\Maintenance;

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\Message\Message;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\User;

require_once dirname( __DIR__, 4 ) . '/maintenance/Maintenance.php';

class MigrateBlueSpiceSocialBlog extends LoggedUpdateMaintenance {

	/**
	 * @return true
	 */
	protected function doDBUpdates() {
		if ( !$this->hasSocialBlog() ) {
			$this->output( "No BlueSpiceSocial content found. Skipping migration.\n" );
			return true;
		}
		$this->output( "Searching for SocialBlog pages...\n" );
		$blogPages = $this->getSocialBlogPages();
		$this->output( "Found " . count( $blogPages ) . " blog pages.\n" );
		$count = 0;
		foreach ( $blogPages as $page => $data ) {
			if ( $count > 0 && $count % 100 === 0 ) {
				$this->output( "Migrated $count pages...\n" );
			}
			if ( $this->migratePage( $page, $data ) ) {
				$count++;
			}

		}
		$this->output( "Migrated $count pages.\n" );
		return true;
	}

	/**
	 * @return bool
	 */
	private function hasSocialBlog(): bool {
		return $this->getDB( DB_REPLICA )->selectRowCount(
			'page',
			'*',
			[
				'page_namespace' => 1506,
			],
			__METHOD__
		) > 0;
	}

	/**
	 * @return array
	 */
	private function getSocialBlogPages(): array {
		$pages = $this->getDB( DB_REPLICA )->select(
			'page',
			[ 'page_title', 'page_id', 'page_namespace' ],
			[
				'page_namespace' => 1506,
			],
		);
		$blogPages = [];
		foreach ( $pages as $page ) {
			$title = $this->getServiceContainer()->getTitleFactory()->newFromRow( $page );
			$revision = $this->getServiceContainer()->getRevisionStore()->getRevisionByTitle( $title );
			if ( !$revision ) {
				continue;
			}
			$content = $revision->getContent( SlotRecord::MAIN );
			if ( !$content ) {
				continue;
			}
			$text = $content->getNativeData();
			$json = json_decode( $text, true );
			if ( !$json ) {
				continue;
			}
			if ( !isset( $json['type'] ) || $json['type'] !== 'blog' ) {
				continue;
			}
			if ( $json['archived'] ) {
				continue;
			}
			$blogPages[$json['blogtitle']] = [
				'text' => $json['text'],
				'author' => $json['ownerid'],
			];
		}

		return $blogPages;
	}

	/**
	 * @param string $title
	 * @param array $data
	 * @return bool
	 */
	private function migratePage( string $title, array $data ) {
		$defaultBlog = Message::newFromKey( 'simpleblogpage-blog-type-general' )->text();
		$newTitle = $this->getServiceContainer()->getTitleFactory()->makeTitle( NS_BLOG, "$defaultBlog/$title" );
		if ( $newTitle->exists() ) {
			return false;
		}
		if ( !$newTitle->canExist() ) {
			$this->error( 'Cannot create blog page ' . $newTitle->getPrefixedText() );
			return false;
		}
		$content = new BlogPostContent( $data['text'] );
		$author = $this->getServiceContainer()->getUserFactory()->newFromId( $data['author'] );
		if ( !$author->isAllowed( 'createblogpost' ) ) {
			$author = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
		}
		$wp = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $newTitle );
		$updater = $wp->newPageUpdater( $author );
		$updater->setContent( SlotRecord::MAIN, $content );
		$updater->saveRevision( CommentStoreComment::newUnsavedComment( 'Migrated from BlueSpiceSocialBlog' ) );
		if ( !$updater->getStatus()->isOK() ) {
			$this->error( 'Failed to save blog post ' . $newTitle->getPrefixedText() );
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'simpleblogpage-migrate-bluespicesocialblog';
	}
}

$maintClass = MigrateBlueSpiceSocialBlog::class;
require_once RUN_MAINTENANCE_IF_MAIN;
