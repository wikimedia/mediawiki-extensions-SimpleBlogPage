<?php

namespace MediaWiki\Extension\SimpleBlogPage\Tests;

use DOMException;
use MediaWiki\Extension\SimpleBlogPage\Util\HtmlSnippetCreator;
use PHPUnit\Framework\TestCase;

class HtmlSnippetCreatorTest extends TestCase {

	/**
	 * @param string $input
	 * @param string $output
	 * @param bool $shouldHaveMore
	 * @return void
	 * @covers \MediaWiki\Extension\SimpleBlogPage\Util\HtmlSnippetCreator::getSnippet
	 * @covers \MediaWiki\Extension\SimpleBlogPage\Util\HtmlSnippetCreator::hasMore
	 * @dataProvider provideData
	 * @throws DOMException
	 */
	public function testGetSnippet( string $input, string $output, bool $shouldHaveMore ) {
		$creator = new HtmlSnippetCreator( $input, 100 );
		$snippet = $creator->getSnippet();
		$this->assertSame( trim( $output ), $snippet );
		// assert $snippet is valid HTML
		try {
			$dom = new \DOMDocument();
			$dom->loadHTML( $snippet );
		} catch ( \Exception $e ) {
			$this->fail( 'Snippet is not valid HTML' );
		}

		// assert hasMore
		$this->assertSame( $shouldHaveMore, $creator->hasMore() );
	}

	public function provideData(): array {
		return [
			'shortHtml' => [
				'<p>hello</p>',
				// Wrapped
				'<div><p>hello </p><div style="clear:both;"></div></div>',
				false
			],
			'longHtml' => [
				file_get_contents( __DIR__ . '/data/dummyHtml.html' ),
				file_get_contents( __DIR__ . '/data/dummySnippet.html' ),
				true
			],
		];
	}
}
