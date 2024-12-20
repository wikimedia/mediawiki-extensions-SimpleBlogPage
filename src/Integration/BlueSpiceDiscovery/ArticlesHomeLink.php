<?php

namespace MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceDiscovery;

use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;

class ArticlesHomeLink extends RestrictedTextLink {

	/** @var SpecialPageFactory */
	private $spf;

	/**
	 * @param SpecialPageFactory $spf
	 * @param array $options
	 */
	public function __construct( SpecialPageFactory $spf, $options ) {
		parent::__construct( [] );
		$this->spf = $spf;
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'n-simpleblogpage-blog-overview';
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPermissions(): array {
		return [ 'read' ];
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$sp = $this->spf->getPage( 'Blogs' );
		if ( !$sp ) {
			return '';
		}
		return $sp->getPageTitle()->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'simpleblogpage-articles-home-links' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'simpleblogpage-articles-home-links' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'blogs' );
	}
}
