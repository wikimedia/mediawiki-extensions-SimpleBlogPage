<?php

namespace MediaWiki\Extension\SimpleBlogPage\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class CreateBlogHandler extends SimpleHandler {

	/** @var BlogFactory */
	private $blogFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param BlogFactory $blogFactory
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( BlogFactory $blogFactory, TitleFactory $titleFactory ) {
		$this->blogFactory = $blogFactory;
		$this->titleFactory = $titleFactory;
	}

	public function execute() {
		$params = $this->getValidatedBody();
		$targetTitle = $this->titleFactory->newFromText( $params['target_page'] );
		try {
			$this->blogFactory->assertTargetTitleValid( $targetTitle );
		} catch ( Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
		}
		try {
			$authority = RequestContext::getMain()->getUser();
			$this->blogFactory->createBlogEntry( $targetTitle, $params['content'], $authority, $params['meta'] );
		} catch ( Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
		}
		return $this->getResponseFactory()->createJson( [
			'success' => true,
			'page' => $targetTitle->getPrefixedDBkey(),
			'url' => $targetTitle->getFullURL()
		] );
	}

	public function getBodyParamSettings(): array {
		return [
			'target_page' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'content' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'meta' => [
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => []
			]
		];
	}

	/**
	 * @return true
	 */
	public function needsWriteAccess() {
		return true;
	}
}
