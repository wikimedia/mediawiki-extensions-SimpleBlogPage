<?php

namespace MediaWiki\Extension\SimpleBlogPage\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Rest\SimpleHandler;

class GetAvailableBlogRootPagesHandler extends SimpleHandler {

	/** @var BlogFactory */
	private $blogFactory;

	/**
	 * @param BlogFactory $blogFactory
	 */
	public function __construct( BlogFactory $blogFactory ) {
		$this->blogFactory = $blogFactory;
	}

	public function execute() {
		return $this->getResponseFactory()->createJson( $this->blogFactory->getBlogRootNames(
			RequestContext::getMain()->getUser()
		) );
	}
}
