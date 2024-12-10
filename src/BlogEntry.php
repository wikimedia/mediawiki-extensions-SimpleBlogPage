<?php

namespace MediaWiki\Extension\SimpleBlogPage;

use MediaWiki\Language\Language;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

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
	 * @var string
	 */
	private string $name;

	/**
	 * @param string $name
	 * @param Title $entryTitle
	 * @param string $text
	 * @param RevisionRecord $revision
	 * @param Title $root
	 */
	public function __construct(
		string $name, Title $entryTitle, string $text, RevisionRecord $revision, Title $root
	) {
		$this->text = $text;
		$this->revision = $revision;
		$this->entryTitle = $entryTitle;
		$this->root = $root;
		$this->name = $name;
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
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
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
			'name' => $this->name,
			'revisionId' => $this->revision->getId(),
			'author' =>  $this->revision->getUser()->getName(),
			'root' => $this->getRoot()->getText(),
			'entryPage' => $this->entryTitle->getPrefixedDBkey()
		];
	}
}