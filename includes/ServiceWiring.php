<?php

use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'SimpleBlogPage.BlogFactory' => static function ( MediaWikiServices $services ) {
		$factory = new BlogFactory(
			$services->getDBLoadBalancer(),
			$services->getTitleFactory(),
			$services->getContentLanguage(),
			$services->getRevisionRenderer(),
			$services->getPageProps(),
			$services->getUserFactory()
		);
		$factory->setLogger( LoggerFactory::getInstance( 'SimpleBlogPage' ) );
		return $factory;
	},
];
