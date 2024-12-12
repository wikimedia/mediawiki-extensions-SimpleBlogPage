<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MWStake\MediaWiki\Component\DataStore\FieldType;
use MWStake\MediaWiki\Component\DataStore\Schema;

class BlogEntryQuerySchema extends Schema {

	public function __construct() {
		parent::__construct( [
			BlogEntryQueryRecord::BLOG_ENTRY_NAME => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			BlogEntryQueryRecord::BLOG_ENTRY_NAMESPACE => [
				self::FILTERABLE => true,
				self::SORTABLE => false,
				self::TYPE => FieldType::INT
			],
			BlogEntryQueryRecord::BLOG_ENTRY_AUTHOR => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING,
				self::IS_BUCKET => true
			],
			BlogEntryQueryRecord::BLOG_ENTRY_TIMESTAMP => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			BlogEntryQueryRecord::BLOG_ENTRY_ROOT => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING,
				self::IS_BUCKET => true
			],
			BlogEntryQueryRecord::BLOG_ENTRY_WIKI_PAGE => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			]
		] );
	}
}
