<?php

namespace MediaWiki\Extension\SimpleBlogPage\Maintenance;

use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\Message\Message;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
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
		foreach ( $blogPages as $data ) {
			if ( $this->migratePage( $data ) ) {
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
			$revisions = [];
			$revision = $this->getServiceContainer()->getRevisionStore()->getFirstRevision( $title );
			$page = null;
			while ( $revision ) {
				$blogRevisionDetails = $this->getBlogRevisionDetails( $revision );
				if ( !$blogRevisionDetails ) {
					break;
				}
				if ( !$page ) {
					$page = $this->makeBlogPage( $blogRevisionDetails['page'] );
					if ( !$page ) {
						break;
					}
				}
				$newRevision = new \WikiRevision();
				$newRevision->setTitle( $page );
				$newRevision->setTimestamp( $revision->getTimestamp() );
				$newRevision->setUsername( $revision->getUser()?->getName() );
				$newRevision->setContent( SlotRecord::MAIN, new BlogPostContent( $blogRevisionDetails['text' ] ) );
				$revisions[] = $newRevision;

				$revision = $this->getServiceContainer()->getRevisionStore()->getNextRevision( $revision );
			}
			if ( !$revisions ) {
				continue;
			}

			$blogPages[] = [
				'page' => $page,
				'revisions' => $revisions
			];
		}

		return $blogPages;
	}

	/**
	 * @param RevisionRecord $revision
	 * @return array|null
	 */
	private function getBlogRevisionDetails( RevisionRecord $revision ): ?array {
		$content = $revision->getContent( SlotRecord::MAIN );
		if ( !$content ) {
			return null;
		}
		$text = $content->getNativeData();
		$json = json_decode( $text, true );
		if ( !$json ) {
			return null;
		}
		if ( !isset( $json['type'] ) || $json['type'] !== 'blog' ) {
			return null;
		}
		if ( $json['archived'] ) {
			return null;
		}

		return [
			'page' => $json['blogtitle'],
			'text' => $json['text'],
		];
	}

	/**
	 * @param string $blogName
	 * @return Title|null
	 */
	private function makeBlogPage( string $blogName ): ?Title {
		$defaultBlog = Message::newFromKey( 'simpleblogpage-blog-type-general' )->text();
		$newTitle = $this->getServiceContainer()->getTitleFactory()->makeTitle( NS_BLOG, "$defaultBlog/$blogName" );
		if ( $newTitle->exists() ) {
			return $newTitle;
		}
		if ( !$newTitle->canExist() ) {
			$this->error( 'Cannot create blog page ' . $newTitle->getPrefixedText() );
			return null;
		}
		return $newTitle;
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	private function migratePage( array $data ) {
		$page = $data['page'];
		if ( $page->exists() ) {
			if ( $this->getOption( 'force' ) ) {
				$this->output( "Page {$page->getPrefixedText()} already exists. Deleting due to --force...\n" );
				$deletePage = $this->getServiceContainer()->getDeletePageFactory()->newDeletePage(
					$page->toPageIdentity(), User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] )
				);
				if ( !$deletePage->deleteUnsafe( 'Blog migration' )->isOK() ) {
					$this->error( "Failed to delete page {$page->getPrefixedText()}\n" );
					return false;
				}
			} else {
				$this->output( "Page {$page->getPrefixedText()} already exists. Skipping...\n" );
				return false;
			}
		}

		$importer = $this->getServiceContainer()->getOldRevisionImporter();

		$this->output( 'Importing blog post to ' . $page->getPrefixedText() . "..." );
		$importedCnt = 0;
		foreach ( $data['revisions'] as $revision ) {
			if ( $importer->import( $revision ) ) {
				$importedCnt++;
			}
		}
		$totalRevisions = count( $data['revisions'] );
		$this->output( " Imported $importedCnt/$totalRevisions revisions.\n" );
		if ( $importedCnt !== $totalRevisions ) {
			$this->error( "Failed to import all revisions for {$page->getPrefixedText()}\n" );
		}
		$this->forcePageLatest( $page );
		return $importedCnt > 0;
	}

	/**
	 * @param Title $title
	 * @return void
	 */
	private function forcePageLatest( Title $title ) {
		$db = $this->getDB( DB_PRIMARY );
		$maxRev = $db->newSelectQueryBuilder()
			->from( 'revision' )
			->select( 'MAX( rev_id ) as latest' )
			->where( [
				'rev_page' => $title->getArticleID(),
			] )
			->fetchRow();
		if ( !$maxRev ) {
			$this->error( "Failed to get max revision for {$title->getPrefixedText()}\n" );
			return;
		}
		$db->newUpdateQueryBuilder()
			->update( 'page' )
			->set( [
				'page_latest' => $maxRev->latest,
			] )
			->where( [
				'page_id' => $title->getArticleID(),
			] )
			->execute();
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
