<?php

namespace MediaWiki\Extension\SimpleBlogPage\ContentHandler;

use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\WikitextContentHandler;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use OOUI\MessageWidget;

class BlogRootHandler extends WikitextContentHandler {

	protected function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$parserOutput ) {
		OutputPage::setupOOUI();
		$parserOutput->setEnableOOUI( true );
		$parserOutput->addModules( [ 'ext.simpleBlogPage.render.rootPage' ] );
		$parserOutput->setRawText( Html::element( 'div', [
			'id' => 'blog-root',
			'data-blog' => $cpoParams->getPage()->getDBkey(),
		] ) );
	}

	protected function getContentClass() {
		return BlogRootContent::class;
	}
}