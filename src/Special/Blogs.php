<?php

namespace MediaWiki\Extension\SimpleBlogPage\Special;

use Exception;
use MediaWiki\Extension\SimpleBlogPage\BlogFactory;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\TitleFactory;
use OOUI\MessageWidget;

class Blogs extends SpecialPage {

	/** @var BlogFactory */
	private $blogFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param BlogFactory $blogFactory
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( BlogFactory $blogFactory, TitleFactory $titleFactory ) {
		parent::__construct( 'Blogs', '', true, false, '', true );
		$this->blogFactory = $blogFactory;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$root = null;
		$rootType = null;
		if ( $subPage ) {
			$forcedBlog = $this->titleFactory->newFromText( $subPage );
			try {
				$this->blogFactory->assertTitleIsBlogRoot( $forcedBlog );
				$roots = $this->blogFactory->getBlogRootNames();
				$blogKey = $forcedBlog->getPrefixedDBkey();
				if ( isset( $roots[$blogKey] ) ) {
					$root = $roots[$blogKey]['dbKey'];
					$rootType = $roots[$blogKey]['type'];
					$this->getOutput()->setPageTitle(
						$this->getOutput()->getPageTitle() . ' - ' . $forcedBlog->getPrefixedText()
					);
				} else {
					throw new Exception();
				}
			} catch ( Exception $e ) {
				$this->getOutput()->enableOOUI();
				$this->getOutput()->addHTML( ( new MessageWidget( [
					'type' => 'warning',
					'label' => $this->msg( 'simpleblogpage-error-invalid-root-page' )->text()
				] ) )->toString() );
			}
		}

		$this->getOutput()->addModules( [ 'ext.simpleBlogPage.home.special' ] );
		$this->getOutput()->addHTML( Html::element( 'div', [
			'id' => 'blog-home',
			'data-blog' => $root ?? '',
			'data-type' => $rootType ?? '',
			'data-creatable' => $this->getUser()->isAllowed( 'createblogpost' ),
		] ) );
	}
}
