<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MWStake\MediaWiki\Component\DataStore\Record;

class BlogEntryQueryRecord extends Record {

	public const BLOG_ENTRY_PAGE = 'page';
	public const BLOG_ENTRY_NAMESPACE = 'namespace';
	public const BLOG_ENTRY_AUTHOR = 'author';
	public const BLOG_ENTRY_TIMESTAMP = 'timestamp';
	public const BLOG_ENTRY_ROOT = 'root';
}