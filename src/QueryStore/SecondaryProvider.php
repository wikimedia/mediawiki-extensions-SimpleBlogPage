<?php

namespace MediaWiki\Extension\SimpleBlogPage\QueryStore;

use MediaWiki\Context\RequestContext;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Watchlist\WatchedItemStore;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\Record;

class SecondaryProvider implements ISecondaryDataProvider {

	/** @var WatchedItemStore */
	private $watchedItemStore;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param WatchedItemStore $watchedItemStore
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( WatchedItemStore $watchedItemStore, TitleFactory $titleFactory ) {
		$this->watchedItemStore = $watchedItemStore;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param array $dataSets
	 * @return Record[]
	 */
	public function extend( $dataSets ) {
		$currentUser = RequestContext::getMain()->getUser();
		foreach ( $dataSets as &$dataSet ) {
			$title = $this->titleFactory->makeTitle(
				$dataSet->get( BlogEntryQueryRecord::BLOG_ENTRY_NAMESPACE ),
				$dataSet->get( BlogEntryQueryRecord::BLOG_ENTRY_WIKI_PAGE )
			);
			$isAuthor = $dataSet->get( BlogEntryQueryRecord::BLOG_ENTRY_AUTHOR ) === $currentUser->getName();
			if ( $isAuthor ) {
				/*$dataSet->set( BlogEntryQueryRecord::META_CAN_WATCH, false );
				continue;*/
			}
			$dataSet->set( BlogEntryQueryRecord::META_CAN_WATCH, true );
			$dataSet->set(
				BlogEntryQueryRecord::META_IS_WATCHING,
				$this->watchedItemStore->isWatched( $currentUser, $title )
			);
		}
		return $dataSets;
	}
}
