<?php

namespace MediaWiki\Extension\SimpleBlogPage\ContentHandler;

use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\WikitextContentHandler;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Parser\ParserOutput;

class BlogRootHandler extends WikitextContentHandler {

	protected function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$parserOutput ) {
		$parserOutput->setRawText( 'ROOT' );
	}

	protected function getContentClass() {
		return BlogRootContent::class;
	}
}