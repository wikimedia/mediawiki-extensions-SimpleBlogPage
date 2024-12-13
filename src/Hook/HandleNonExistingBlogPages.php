<?php

namespace MediaWiki\Extension\SimpleBlogPage\Hook;

use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\Content\BlogRootContent;
use MediaWiki\Extension\SimpleBlogPage\ContentHandler\BlogRootHandler;
use MediaWiki\Page\Hook\BeforeDisplayNoArticleTextHook;
use MediaWiki\Parser\ParserOutput;

class HandleNonExistingBlogPages implements BeforeDisplayNoArticleTextHook {

	/**
	 * @var BlogFactory
	 */
	private $blogFactory;

	/**
	 * @param BlogFactory $blogFactory
	 */
	public function __construct( BlogFactory $blogFactory ) {
		$this->blogFactory = $blogFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforeDisplayNoArticleText( $article ) {
		if (
			$article->getPage()->getNamespace() !== NS_BLOG && $article->getPage()->getNamespace() !== NS_USER_BLOG
		) {
			return true;
		}
		if ( !$article->getContext()->getUser()->isAllowed( 'createblogpost' ) ) {
			$article->getContext()->getOutput()->addWikiTextAsContent(
				'simpleblogpage-no-blog-no-create'
			);
			return false;
		}
		$isRoot = !$article->getContext()->getTitle()->isSubpage();
		if ( $isRoot ) {
			$contentHandler = $article->getPage()->getContentHandler();
			if ( $contentHandler instanceof BlogRootHandler ) {
				$po = new ParserOutput();
				$contentHandler->fillParserOutputInternal(
					new BlogRootContent( '' ),
					new ContentParseParams( $article->getPage() ),
					$po
				);
				$article->getContext()->getOutput()->addParserOutput( $po );
				return false;
			}
			return false;
		}

		return true;
	}
}
