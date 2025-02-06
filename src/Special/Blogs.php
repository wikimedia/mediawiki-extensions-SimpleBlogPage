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
		$forcedBlog = null;
		if ( $subPage ) {
			$forcedBlog = $this->titleFactory->newFromText( $subPage );
			try {
				$this->blogFactory->assertTitleIsBlogRoot( $forcedBlog );
				$roots = $this->blogFactory->getBlogRootNames();
				$blogKey = $forcedBlog->getPrefixedDBkey();
				$root = $forcedBlog->getDBkey();
				$rootType = $forcedBlog->getNamespace() === NS_USER_BLOG ? 'user' : 'global';

				if ( isset( $roots[$blogKey] ) ) {
					$root = $roots[$blogKey]['dbKey'];
					$rootType = $roots[$blogKey]['type'];
				} else {
					$this->getOutput()->enableOOUI();
					$msg = $this->blogFactory->canUserPostInBlog( $this->getUser(), $forcedBlog ) ?
						'simpleblogpage-error-non-existing-root-page' :
						'simpleblogpage-no-blog-no-create';
					$this->getOutput()->addHTML( ( new MessageWidget( [
						'type' => 'warning',
						'label' => $this->msg( $msg )->text()
					] ) )->toString() );
				}
				$this->getOutput()->setPageTitle(
					$this->getOutput()->getPageTitle() . ' - ' . $forcedBlog->getPrefixedText()
				);
			} catch ( Exception $e ) {
				$this->getOutput()->enableOOUI();
				$this->getOutput()->addHTML( ( new MessageWidget( [
					'type' => 'error',
					'label' => $this->msg( 'simpleblogpage-error-invalid-root-page' )->text()
				] ) )->toString() );
				return;
			}
		}

		$blogPage = '';
		if ( $forcedBlog ) {
			if ( $forcedBlog->getNamespace() === NS_USER_BLOG ) {
				$blogPage = $forcedBlog->getPrefixedDBkey();
			} else {
				$blogPage = $forcedBlog->getDBkey();
			}
		}
		$this->getOutput()->addModules( [ 'ext.simpleBlogPage.home.special' ] );
		$this->getOutput()->addHTML( Html::element( 'div', [
			'id' => 'blog-home',
			'data-blog' => $root ?? '',
			'data-type' => $rootType ?? '',
			'data-blog-page' => $blogPage,
			'data-creatable' => $this->blogFactory->canUserPostInBlog( $this->getUser(), $forcedBlog )
		] ) );
	}
}
