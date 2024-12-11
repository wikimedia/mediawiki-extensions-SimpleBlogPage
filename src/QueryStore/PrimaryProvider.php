<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\IBucketProvider;
use MWStake\MediaWiki\Component\DataStore\PrimaryDatabaseDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use Wikimedia\Rdbms\IDatabase;

class PrimaryProvider extends PrimaryDatabaseDataProvider implements IBucketProvider {

	/**
	 * @var array
	 */
	private $buckets = [];

	protected function getTableNames() {
		return [ 'page', 'revision', 'actor', 'user' ];
	}

	protected function getFields() {
		return [
			'page_id', 'page_title as root', 'page_namespace as namespace',
			'user_name as author', 'rev_timestamp as timestamp'
		];
	}

	protected function appendPreFilterCond( &$conds, Filter $filter ) {
		if ( $filter->getField() === 'root' ) {
			$value = $filter->getValue();
			if ( !is_array( $value ) ) {
				$value = [ $value ];
			}
			$rootFilter = [];
			foreach ( $value as $root ) {
				$rootFilter[] = "page_title LIKE '$root/%'";
			}
			$conds[] = '(' . $this->db->makeList( $rootFilter, IDatabase::LIST_OR ). ')';
			$filter->setApplied( true );
		}
	}


	protected function getJoinConds( ReaderParams $params ) {
		return [
			'revision' => [
				'INNER JOIN',
				[ 'page_latest = rev_id' ]
			],
			'actor' => [
				'INNER JOIN',
				[ 'rev_actor = actor_id' ]
			],
			'user' => [
				'INNER JOIN',
				[ 'actor_user = user_id' ]
			]
		];
	}


	protected function appendRowToData( \stdClass $row ) {
		$item = new BlogEntryQueryRecord( (object) [
			BlogEntryQueryRecord::BLOG_ENTRY_PAGE => $this->getBlogName( $row->root ),
			BlogEntryQueryRecord::BLOG_ENTRY_NAMESPACE => $row->namespace,
			BlogEntryQueryRecord::BLOG_ENTRY_AUTHOR => $row->author,
			BlogEntryQueryRecord::BLOG_ENTRY_TIMESTAMP => $row->timestamp,
			BlogEntryQueryRecord::BLOG_ENTRY_ROOT => $this->getBlogRoot( $row->root )
		] );
		$this->addToBuckets( $item );
		$this->data[] = $item;
	}

	protected function getDefaultConds() {
		return [
			"page_namespace = 502 OR (page_namespace = 1502 AND page_content_model = 'blog_post')",
		];
	}

	/**
	 * @param BlogEntryQueryRecord $row
	 * @return void
	 */
	public function addToBuckets( BlogEntryQueryRecord $row ) {
		$fields = $this->schema->getBucketFields();
		foreach ( $fields as $field ) {
			$value = $row->get( $field );
			if ( !isset( $this->buckets[$field] ) ) {
				$this->buckets[$field] = [];
			}
			if ( !in_array( $value, $this->buckets[$field] ) ) {
				$this->buckets[$field][] = $value;
			}
		}
	}

	/**
	 * @return array
	 */
	public function getBuckets(): array {
		return $this->buckets;
	}

	/**
	 * @param string $pageTitle
	 * @return string
	 */
	private function getBlogName( string $pageTitle ) {
		// Remove first */ part
		$parts = explode( '/', $pageTitle );
		array_shift( $parts );
		return implode( '/', $parts );
	}

	/**
	 * @param string $pageTitle
	 * @return string
	 */
	private function getBlogRoot( string $pageTitle ) {
		$parts = explode( '/', $pageTitle );
		return $parts[0];
	}
}
