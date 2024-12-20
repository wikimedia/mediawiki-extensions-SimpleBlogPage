<?php

namespace MediaWiki\Extension\SimpleBlogPage\ContentHandler;

use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\WikitextContentHandler;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Html\Html;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;

class BlogRootHandler extends WikitextContentHandler {

	/**
	 * @inheritDoc
	 */
	protected function fillParserOutput(
		Content $content, ContentParseParams $cpoParams, ParserOutput &$parserOutput
	) {
		$title = Title::castFromPageReference( $cpoParams->getPage() );
		parent::fillParserOutput( $content, $cpoParams, $parserOutput );
		$parserOutput->addModules( [ 'ext.simpleBlogPage.render.rootPage' ] );
		$parserOutput->setRawText( $parserOutput->getRawText() . Html::element( 'div', [
			'id' => 'blog-root',
			'data-blog-page' => $title ? $title->getPrefixedText() : '',
			'data-blog' => $title ? $title->getDBkey() : '',
			'data-type' => $title->getNamespace() === NS_USER_BLOG ? 'user' : 'global',
			'data-blog-exists' => $title->exists() ? 'true' : 'false',
			'data-creatable' => RequestContext::getMain()->getUser()->isAllowed( 'createblogpost' ),
		] ) );
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return BlogRootContent::class;
	}
}
