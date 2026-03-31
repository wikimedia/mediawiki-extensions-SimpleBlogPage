<?php

namespace MediaWiki\Extension\SimpleBlogPage\Component;

use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\SimpleBlogPage\BlogPermissionChecker;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleLink;

class CreateBlogButton extends SimpleLink {

	/**
	 * @param BlogPermissionChecker $permissionChecker
	 */
	public function __construct( private readonly BlogPermissionChecker $permissionChecker ) {
		return parent::__construct( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'create-blog-btn';
	}

	/**
	 * @inheritDoc
	 */
	public function getSubComponents(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getClasses(): array {
		return [ 'ca-simpleblogpage-create', 'ico-btn', 'bi-bs-create-page' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getRole(): string {
		return 'button';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'simpleblogpage-create-entry' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'simpleblogpage-create-entry' );
	}

	/**
	 * @inheritDoc
	 */
	public function getHref(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function shouldRender( IContextSource $context ): bool {
		$user = $context->getUser();
		if ( $this->permissionChecker->canCreateBlogs( $user ) ) {
			return true;
		}
		return false;
	}
}
