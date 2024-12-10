<?php

namespace MediaWiki\Extension\SimpleBlogPage\ContentHandler;

use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\WikitextContentHandler;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogPostContent;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\MagicWordFactory;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\Parsoid\ParsoidParserFactory;
use MediaWiki\Title\TitleFactory;
use OOUI\MessageWidget;
use Throwable;
use Wikimedia\UUID\GlobalIdGenerator;

class BlogPostHandler extends WikitextContentHandler {

	/**
	 * @var BlogFactory
	 */
	private $blogFactory;

	/**
	 * @param string $modelId
	 * @param TitleFactory $titleFactory
	 * @param ParserFactory $parserFactory
	 * @param GlobalIdGenerator $globalIdGenerator
	 * @param LanguageNameUtils $languageNameUtils
	 * @param LinkRenderer $linkRenderer
	 * @param MagicWordFactory $magicWordFactory
	 * @param ParsoidParserFactory $parsoidParserFactory
	 * @param BlogFactory $blogFactory
	 */
	public function __construct(
		string $modelId, TitleFactory $titleFactory, ParserFactory $parserFactory, GlobalIdGenerator $globalIdGenerator,
		LanguageNameUtils $languageNameUtils, LinkRenderer $linkRenderer, MagicWordFactory $magicWordFactory,
		ParsoidParserFactory $parsoidParserFactory, BlogFactory $blogFactory ) {
		parent::__construct( $modelId, $titleFactory, $parserFactory, $globalIdGenerator, $languageNameUtils, $linkRenderer, $magicWordFactory, $parsoidParserFactory );
		$this->blogFactory = $blogFactory;
	}

	/**
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput $parserOutput
	 * @return void
	 */
	protected function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$parserOutput ) {
		parent::fillParserOutput( $content, $cpoParams, $parserOutput );
		OutputPage::setupOOUI();
		$parserOutput->setEnableOOUI( true );
		try {
			$blogEntry = $this->blogFactory->getEntryFromContent( $content, $cpoParams->getPage(), $cpoParams->getRevId() );
			$parserOutput->setRawText(
				$this->blogFactory->getOutputForContentHandler( $blogEntry, RequestContext::getMain()->getUser(), $parserOutput->getRawText() )
			);
			$parserOutput->addModuleStyles( [ 'ext.simpleBlogPage.entry.styles' ] );
			$parserOutput->addModules( [ 'ext.simpleBlogPage.render.entry.native' ] );
		} catch ( Throwable $e ) {
			$parserOutput->setRawText( new MessageWidget( [
				'type' => 'error',
				'label' => $e->getMessage()
			] ) );
		}
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return BlogPostContent::class;
	}
}