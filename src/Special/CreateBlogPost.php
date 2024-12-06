<?php

namespace MediaWiki\Extension\SimpleBlogPage\Special;

use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;

class CreateBlogPost extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CreateBlogPost', 'createblogpost' );
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$blog = $subPage;

		$this->getOutput()->addModules( [ 'ext.simpleBlogPage.create.special' ] );
		$this->getOutput()->addHTML(
			Html::rawElement( 'div', [
				'id' => 'createblogpost',
				'data-blog' => $blog
			], Html::element( 'div', [ 'id' => 'error' ] ) . Html::element( 'div', [ 'id' => 'form' ] ) )
		);
	}
}