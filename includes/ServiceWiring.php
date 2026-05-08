<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Extension\SimpleBlogPage\BlogPermissionChecker;
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
			$services->getUserFactory(),
			$services->getService( 'SimpleBlogPage.PermissionChecker' ),
			$services->getService( 'MWStakeCommonUtilsFactory' )
		);
		$factory->setLogger( LoggerFactory::getInstance( 'SimpleBlogPage' ) );
		return $factory;
	},
	'SimpleBlogPage.PermissionChecker' => static function ( MediaWikiServices $services ) {
		return new BlogPermissionChecker(
			$services->getTitleFactory(),
			$services->getPermissionManager()
		);
	},
];
