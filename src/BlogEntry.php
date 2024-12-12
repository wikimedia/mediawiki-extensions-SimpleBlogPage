<?php

namespace MediaWiki\Extension\SimpleBlogPage;

use MediaWiki\Language\Language;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;

class BlogEntry {

	/**
	 * @var string
	 */
	private string $text;

	/**
	 * @var RevisionRecord
	 */
	private RevisionRecord $revision;

	/**
	 * @var Title
	 */
	private Title $entryTitle;

	/**
	 * @var Title
	 */
	private Title $root;

	/**
	 * @param Title $entryTitle
	 * @param string $text
	 * @param RevisionRecord $revision
	 * @param Title $root
	 */
	public function __construct(
		Title $entryTitle, string $text, RevisionRecord $revision, Title $root
	) {
		$this->text = $text;
		$this->revision = $revision;
		$this->entryTitle = $entryTitle;
		$this->root = $root;
	}

	/**
	 * @return RevisionRecord
	 */
	public function getRevision(): RevisionRecord {
		return $this->revision;
	}

	/**
	 * @return Title
	 */
	public function getTitle(): Title {
		return $this->entryTitle;
	}

	/**
	 * @return Title
	 */
	public function getRoot(): Title {
		return $this->root;
	}

	/**
	 * @return string
	 */
	public function getText(): string {
		return $this->text;
	}

	/**
	 * @param Language $language
	 * @param Authority $forUser
	 * @return array
	 */
	public function getMeta( Language $language, Authority $forUser ): array {
		return [
			'timestamp' => $this->revision->getTimestamp(),
			'userTimestamp' => $language->userTimeAndDate(
				$this->revision->getTimestamp(), $forUser->getUser(), [ 'timecorrection' => true ]
			),
			'revisionId' => $this->revision->getId(),
			'author' => $this->revision->getUser()->getName(),
			'root' => $this->getRoot()->getText(),
			'rootPage' => $this->getRoot()->getPrefixedDBkey(),
			'entryPage' => $this->entryTitle->getPrefixedDBkey()
		];
	}
}
