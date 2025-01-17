<?php

namespace SMT;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;

/**
 * @license GPL-2.0-or-later
 * @since 1.0
 *
 * @author mwjames
 */
class PropertyValuesContentAggregator {

	/**
	 * @var LazySemanticDataLookup
	 */
	private $lazySemanticDataLookup;

	/**
	 * @var \OutputPage
	 */
	private $mOutputPage;

	/**
	 * Whether multiple properties should be used through a fallback chain where
	 * the first available property with content will determine the end of the
	 * processing or content being simply concatenated
	 *
	 * @var bool
	 */
	private $useFallbackChainForMultipleProperties = false;

	/**
	 * @since 1.0
	 *
	 * @param LazySemanticDataLookup $lazySemanticDataLookup
	 * @param OutputPage $outputPage
	 */
	public function __construct( LazySemanticDataLookup $lazySemanticDataLookup, \OutputPage $outputPage ) {
		$this->lazySemanticDataLookup = $lazySemanticDataLookup;
		$this->mOutputPage = $outputPage;
	}

	/**
	 * @since  1.0
	 *
	 * @param bool $useFallbackChainForMultipleProperties
	 */
	public function useFallbackChainForMultipleProperties( $useFallbackChainForMultipleProperties ) {
		$this->useFallbackChainForMultipleProperties = $useFallbackChainForMultipleProperties;
	}

	/**
	 * @since  1.0
	 *
	 * @param string[] $propertyNames
	 *
	 * @return string
	 */
	public function doAggregateFor( array $propertyNames ) {
		$values = [];

		foreach ( $propertyNames as $property ) {

			// If content is already present and the fallback mode is enabled
			// stop requesting additional content
			if ( $this->useFallbackChainForMultipleProperties && $values !== [] ) {
				break;
			}

			if ( is_string( $property ) ) {
				$property = trim( $property );
			}
			$this->fetchContentForProperty( $property, $values );
		}

		return implode( ',', $values );
	}

	private function fetchContentForProperty( $property, array &$values ) {
		if ( is_callable( $property ) ) {
			// This is actually a callback function.
			$result = $property( $this->mOutputPage );
			if ( $result ) {
				foreach ( (array)$result as $value ) {
					$values[$value] = (string)$value;
				}
			}
		} else {
			// This is a real property.
			$property = DIProperty::newFromUserLabel( $property );
			$semanticData = $this->lazySemanticDataLookup->getSemanticData();

			$this->iterateToCollectPropertyValues(
				$semanticData->getPropertyValues( $property ),
				$values
			);

			foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
				$this->iterateToCollectPropertyValues(
					$subSemanticData->getPropertyValues( $property ),
					$values
				);
			}
		}
	}

	private function iterateToCollectPropertyValues( array $propertyValues, &$values ) {
		foreach ( $propertyValues as $value ) {

			// Content escaping (htmlspecialchars) is being carried out
			// by the instance that adds the content
			if ( $value instanceof DIBlob ) {
				$values[$value->getHash()] = $value->getString();
			} elseif ( $value instanceof DIWikiPage || $value instanceof DIUri ) {
				$values[$value->getHash()] = $value->getSortKey();
			}
		}
	}

}
