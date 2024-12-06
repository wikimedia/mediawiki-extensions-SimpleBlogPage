<?php

namespace MediaWiki\Extension\SimpleBlogPage;

use MediaWiki\Language\Language;
use MediaWiki\Revision\RevisionRecord;
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
	 * @param string $text
	 * @param RevisionRecord $revision
	 */
	public function __construct( string $text, RevisionRecord $revision ) {
		$this->text = $text;
		$this->revision = $revision;
	}

	/**
	 * @param Language $language
	 * @param UserIdentity $forUser
	 * @return mixed
	 */
	public function outputSerialize( Language $language, UserIdentity $forUser ): mixed {
		return [
			'timestamp' => $this->revision->getTimestamp(),
			'userTimestamp' => $forUser ?
				$language->userTimeAndDate( $this->revision->getTimestamp(), $forUser, [ 'timecorrection' => true ] ) :
				$language->timeanddate( $this->revision->getTimestamp(), true ),
			'author' =>  $this->revision->getUser()->getName(),
			'text' => $this->text
		];
	}
}