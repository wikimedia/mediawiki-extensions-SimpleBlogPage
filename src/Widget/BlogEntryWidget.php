<?php

namespace MediaWiki\Extension\SimpleBlogPage\Widget;

use OOUI\HtmlSnippet;
use OOUI\PanelLayout;

class BlogEntryWidget extends PanelLayout {
	public function __construct( string $text, array $data ) {
		parent::__construct( [
			'expanded' => false,
			'padded' => true,
			'framed' => false,
			'classes' => [ 'blog-entry', 'native' ],
		] );
		$this->setAttributes( [ 'data-blog-meta' => json_encode( $data ) ] );
		$this->makeHeader();
		$this->makeContent( $text );
	}

	private function makeHeader() {
		$this->appendContent( new PanelLayout( [
			'expanded' => false,
			'padded' => false,
			'framed' => false,
			'classes' => [ 'blog-entry-header' ],
		] ) );
	}

	private function makeContent( string $text ) {
		$this->appendContent( new PanelLayout( [
			'expanded' => false,
			'padded' => true,
			'framed' => false,
			'classes' => [ 'blog-entry-body' ],
			'content' => new HtmlSnippet( $text ),
		] ) );
	}
}