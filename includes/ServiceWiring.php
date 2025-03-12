<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'SimpleBlogPage.BlogFactory' => static function ( MediaWikiServices $services ) {
		$context = RequestContext::getMain();
		$factory = new BlogFactory(
			$services->getDBLoadBalancer(),
			$services->getTitleFactory(),
			$context->getLanguage(),
			$services->getRevisionRenderer(),
			$services->getPageProps(),
			$services->getUserFactory()
		);
		$factory->setLogger( LoggerFactory::getInstance( 'SimpleBlogPage' ) );
		return $factory;
	},
];
