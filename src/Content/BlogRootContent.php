<?php

namespace MediaWiki\Extension\SimpleBlogPage\Content;

use MediaWiki\Content\WikitextContent;

class BlogRootContent extends WikitextContent {

	/**
	 * @param string $text
	 */
	public function __construct( string $text ) {
		parent::__construct( $text );
		$this->model_id = 'blog_root';
	}
}
