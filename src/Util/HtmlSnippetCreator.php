<?php

namespace MediaWiki\Extension\SimpleBlogPage\Util;

use DOMDocument;
use DOMElement;
use DOMException;
use MediaWiki\Html\Html;

class HtmlSnippetCreator {
	/** @var string */
	private string $html;
	/** @var int */
	private int $wordLimit;

	/** @var bool */
	private bool $hasMore = false;

	/**
	 * @param string $html
	 * @param int $wordLimit
	 */
	public function __construct( string $html, int $wordLimit = 100 ) {
		$this->html = $html;
		$this->wordLimit = $wordLimit;
	}

	/**
	 * @return string
	 * @throws DOMException
	 */
	public function getSnippet(): string {
		$this->preprocess();
		// Load the HTML into a DOMDocument
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$html = mb_convert_encoding( $this->html, 'HTML-ENTITIES', 'UTF-8' );
		$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$wordCount = 0;
		$snippetDom = new DOMDocument();
		$rootFragment = $this->extractContent( $dom->documentElement, $snippetDom, $wordCount, $this->wordLimit );

		if ( $rootFragment ) {
			$snippetDom->appendChild( $snippetDom->importNode( $rootFragment, true ) );
		}

		return $this->postProcess( $snippetDom->saveHTML() );
	}

	/**
	 * @return bool
	 */
	public function hasMore(): bool {
		return $this->hasMore;
	}

	/**
	 * @param DOMElement|null $node
	 * @param DOMDocument $snippetDom
	 * @param int &$wordCount
	 * @param int $wordLimit
	 * @return DOMElement|false|null
	 * @throws DOMException
	 */
	private function extractContent( ?DOMElement $node, DOMDocument $snippetDom, int &$wordCount, int $wordLimit ) {
		$newNode = $snippetDom->createElement( $node->nodeName );

		// Copy attributes for element nodes
		if ( $node->nodeType === XML_ELEMENT_NODE ) {
			foreach ( $node->attributes as $attribute ) {
				$newNode->setAttribute( $attribute->name, $attribute->value );
			}
		}

		foreach ( $node->childNodes as $child ) {
			if ( $wordCount >= $wordLimit ) {
				$this->hasMore = true;
				break;
			}

			if ( $child->nodeType === XML_TEXT_NODE ) {
				// Process text nodes
				$text = trim( $child->textContent );
				$words = explode( ' ', $text );

				foreach ( $words as $word ) {
					if ( $wordCount < $wordLimit ) {
						$wordCount++;
						$newNode->appendChild( $snippetDom->createTextNode( $word . ' ' ) );
					} else {
						$this->hasMore = true;
						break 2;
					}
				}
			} elseif ( $child->nodeType === XML_ELEMENT_NODE ) {
				// Recursively process child elements
				$childFragment = $this->extractContent( $child, $snippetDom, $wordCount, $wordLimit );
				if ( $childFragment ) {
					$newNode->appendChild( $childFragment );
				}
			} else {
				// Preserve non-text nodes (e.g., <img>)
				$newNode->appendChild( $snippetDom->importNode( $child, true ) );
			}
		}

		// Only return the new node if it has child nodes or attributes
		return $newNode->hasChildNodes() || $newNode->hasAttributes() ? $newNode : null;
	}

	/**
	 * @return void
	 */
	private function preprocess() {
		// Wrap all in a `<div>` tag and add clear div
		$clearDiv = Html::element( 'div', [ 'style' => 'clear:both;' ] );
		$this->html = Html::rawElement( 'div', [], $this->html . $clearDiv );
	}

	/**
	 * @param string $snippet
	 * @return string
	 */
	private function postProcess( string $snippet ): string {
		// Strip `<editsection>` tags
		$snippet = preg_replace( '/<editsection[^>]*>.*?<\/editsection>/is', '', $snippet );
		return trim( $snippet );
	}
}
