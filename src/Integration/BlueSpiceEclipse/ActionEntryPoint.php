<?php

namespace MediaWiki\Extension\SimpleBlogPage\Integration\BlueSpiceEclipse;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\SimpleBlogPage\BlogPermissionChecker;
use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\ActionLink;

class ActionEntryPoint extends ActionLink {

	/**
	 * @param SpecialPageFactory $spf
	 * @param BlogPermissionChecker $permissionChecker
	 */
	public function __construct( private readonly SpecialPageFactory $spf,
		private readonly BlogPermissionChecker $permissionChecker
	) {
		parent::__construct( [] );
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return 'n-simpleblogpage-blog-overview';
	}

	/**
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

	/**
	 * @inheritDoc
	 */
	public function showAction(): bool {
		$user = RequestContext::getMain()->getUser();
		if ( $this->permissionChecker->canCreateBlogs( $user ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getActionClass(): string {
		return 'ca-simpleblogpage-create';
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'bi-bs-create-page';
	}

	/**
	 * @inheritDoc
	 */
	public function getActionAriaLabel(): Message {
		return Message::newFromKey( 'simpleblogpage-entrypoints-action-blog-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getActionTitle(): Message {
		return Message::newFromKey( 'simpleblogpage-entrypoints-action-blog-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getActionLabel(): Message {
		return new RawMessage( '' );
	}

	/**
	 * @inheritDoc
	 */
	public function showActionLabel(): bool {
		return false;
	}
}
