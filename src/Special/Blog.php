<?php

namespace MediaWiki\Extension\SimpleBlogPage\Special;

use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;

class Blog extends SpecialPage {

	/** @var BlogFactory */
	private $blogFactory;

	/**
	 * @param BlogFactory $blogFactory
	 */
	public function __construct( BlogFactory $blogFactory ) {
		parent::__construct( 'Blog', '', true, false, '', true );
		$this->blogFactory = $blogFactory;
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$roots = $this->blogFactory->getBlogRootNames();
		$root = null;
		if ( $subPage ) {
			if ( isset( $roots[$subPage] ) || in_array( $subPage, array_values( $roots ) ) ) {
				$root = $subPage;
			}
		}

		$this->getOutput()->addModules( [ 'ext.simpleBlogPage.home.special' ] );
		$this->getOutput()->addHTML( Html::element( 'div', [
			'id' => 'blog-home',
			'data-blog' => $root ?? '',
			'data-creatable' => $this->getUser()->isAllowed( 'createblogpost' ),
		] ) );
	}
}
