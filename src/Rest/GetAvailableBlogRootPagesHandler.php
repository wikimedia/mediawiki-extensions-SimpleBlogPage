<?php

namespace MediaWiki\Extension\SimpleBlogPage\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

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
		$params = $this->getValidatedParams();
		if ( $params['forCreation'] ) {
			return $this->getResponseFactory()->createJson(
				$this->blogFactory->getBlogRootNames( RequestContext::getMain()->getUser() )
			);
		}
		return $this->getResponseFactory()->createJson(
			$this->blogFactory->getBlogRootNames()
		);
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'forCreation' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false
			]
		];
	}
}
