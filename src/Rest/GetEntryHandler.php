<?php

namespace MediaWiki\Extension\SimpleBlogPage\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class GetEntryHandler extends SimpleHandler {

	/** @var RevisionLookup */
	private $revisionLookup;
	/** @var BlogFactory */
	private $blogFactory;
	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param BlogFactory $blogFactory
	 * @param TitleFactory $titleFactory
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		BlogFactory $blogFactory, TitleFactory $titleFactory, RevisionLookup $revisionLookup
	) {
		$this->blogFactory = $blogFactory;
		$this->titleFactory = $titleFactory;
		$this->revisionLookup = $revisionLookup;
	}

	public function execute() {
		$params = $this->getValidatedParams();
		try {
			$title = $this->titleFactory->newFromText( $params['title'] );
			$this->blogFactory->assertTitleIsBlog( $title );
			if ( $params['revision'] && $params['revision'] > 0 ) {
				$revision = $this->revisionLookup->getRevisionByTitle( $title, $params['revision'] );
				if ( !$revision->getPage()->isSamePageAs( $title ) ) {
					// Edge case, someone trying to get revision from another page
					throw new HttpException( 'invalid-revision', 500 );
				}
			} else {
				$revision = $this->revisionLookup->getRevisionByTitle( $title );
			}
			if ( !$revision ) {
				throw new HttpException( 'invalid-revision', 404 );
			}
			$blog = $this->blogFactory->getEntryFromRevision( $revision );
			$serialized = $this->blogFactory->serializeForOutput( $blog, RequestContext::getMain()->getUser() );
			return $this->getResponseFactory()->createJson( $serialized );
		} catch ( Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
		}
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'title' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string'
			],
			'revision' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_TYPE => 'integer'
			]
		];
	}
}
