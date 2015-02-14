<?php

namespace SMT;

use SMW\SemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;
use OutputPage;

/**
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class OutputPageMetaTagsModifier {

	/**
	 * @var PropertyValueContentFinder
	 */
	private $propertyValueContentFinder;

	/**
	 * @var array
	 */
	private $metaTagsContentPropertySelector = array();

	/**
	 * @since 1.0
	 *
	 * @param emanticData $propertyValueContentFinder
	 */
	public function __construct( PropertyValueContentFinder $propertyValueContentFinder ) {
		$this->propertyValueContentFinder = $propertyValueContentFinder;
	}

	/**
	 * @since 1.0
	 *
	 * @param  array $metaTagsContentPropertySelector
	 */
	public function setMetaTagsContentPropertySelector( array $metaTagsContentPropertySelector ) {
		$this->metaTagsContentPropertySelector = $metaTagsContentPropertySelector;
	}

	/**
	 * @since  1.0
	 *
	 * @param OutputPage &$outputPage
	 */
	public function modifyOutputPage( OutputPage &$outputPage ) {

		if ( $this->metaTagsContentPropertySelector === array() ) {
			return true;
		}

		foreach ( $this->metaTagsContentPropertySelector as $tag => $propertySelector ) {

			if ( $propertySelector === '' ) {
				continue;
			}

			$properties = explode( ',', $propertySelector );

			$outputPage->addMeta(
				$tag,
				$this->propertyValueContentFinder->findContentForProperties( $properties )
			);
		}
	}

}
