<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MWStake\MediaWiki\Component\DataStore\Record;

class BlogEntryQueryRecord extends Record {

	public const BLOG_ENTRY_NAME = 'name';
	public const BLOG_ENTRY_NAMESPACE = 'namespace';
	public const BLOG_ENTRY_AUTHOR = 'author';
	public const BLOG_ENTRY_TIMESTAMP = 'timestamp';
	public const BLOG_ENTRY_ROOT = 'root';
	public const BLOG_ENTRY_WIKI_PAGE = 'wikipage';
	public const BLOG_ENTRY_TYPE = 'type';
	public const META_CAN_WATCH = 'canWatch';
	public const META_IS_WATCHING = 'isWatching';
}
