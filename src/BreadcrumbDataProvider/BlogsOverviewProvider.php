<?php

namespace MediaWiki\Extension\SimpleBlogPage\BreadcrumbDataProvider;

use BlueSpice\Discovery\BreadcrumbDataProvider\BaseBreadcrumbDataProvider;
use MediaWiki\Title\Title;

class BlogsOverviewProvider extends BaseBreadcrumbDataProvider {

	/**
	 * @param Title $title
	 * @return Title
	 */
	public function getRelevantTitle( $title ): Title {
		if ( isset( $this->webRequestValues['target'] ) ) {
			$pagename = $this->webRequestValues['target'];
		} elseif ( isset( $this->webRequestValues['page'] ) ) {
			$pagename = $this->webRequestValues['page'];
		} else {
			$bits = explode( '/', $title->getText() );
			array_shift( $bits );
			$pagename = implode( '/', $bits );
		}

		$newTitle = $this->titleFactory->newFromText( $pagename );
		if ( $newTitle === null ) {
			return $title;
		}

		if ( $newTitle->isTalkPage() ) {
			$this->talkName = true;
		}

		return $newTitle;
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	public function applies( Title $title ): bool {
		return $title->isSpecial( 'Blogs' );
	}

	/**
	 * @inheritDoc
	 */
	public function isSelfLink( $node ): bool {
		return false;
	}
}
