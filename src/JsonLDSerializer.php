<?php

/**
 * @see  https://www.mediawiki.org/wiki/Extension:PageProperties
 * @author thomas-topway-it for KM-A
 */

namespace SMT;

use Exception;
use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use SMW\Exporter\ExporterFactory;

class JsonLDSerializer {
	/**
	 * @param Title $title
	 * @param OutputPage $outputPage
	 */
	public function __construct( $title, $outputPage ) {
		if ( $this->isKnownArticle( $title ) ) {
			$this->setJsonLD( $title, $outputPage );
		}
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/PageProperties/+/548d30609c512a79e202dfa7c02a298c66ca34fa/includes/PageProperties.php
	 * @param Title $title
	 * @return bool
	 */
	private function isKnownArticle( $title ) {
		return ( $title && $title->canExist() && $title->getArticleID() > 0
			&& $title->isKnown() );
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/PageProperties/+/548d30609c512a79e202dfa7c02a298c66ca34fa/includes/PageProperties.php
	 * @param Title $title
	 * @param OutputPage $outputPage
	 * @return void
	 */
	public static function setJsonLD( $title, $outputPage ) {
		if ( !class_exists( '\EasyRdf\Graph' ) || !class_exists( '\ML\JsonLD\JsonLD' ) ) {
			return;
		}
		// @see SMW\Query\ResultPrinters\RdfResultPrinter
		$exporterFactory = new ExporterFactory();
		$serializer = $exporterFactory->newRDFXMLSerializer();
		$export_controller = $exporterFactory->newExportController( $serializer );

		$outputPage->disable();
		ob_start();

		try {
			$recursive = true;
			$export_controller->enableBacklinks( false );
			$revisionDate = false;
			$pages = [ $title->getFullText() ];
			$export_controller->printPages( $pages, $recursive, $revisionDate );

		} catch ( Exception $e ) {
			ob_end_clean();
			LoggerFactory::getInstance( 'smt' )->error( 'SMW ExporterFactory error: ' . $e->getMessage() );
			return;
		}

		$data = ob_get_clean();

		try {
			$foaf = new \EasyRdf\Graph( $title->getFullUrl(), $data );
			$format = \EasyRdf\Format::getFormat( 'jsonld' );
			$output = $foaf->serialise( $format, [
				'compact' => true,
			] );

		} catch ( Exception $e ) {
			LoggerFactory::getInstance( 'smt' )->error( 'EasyRdf error: ' . $e->getMessage() );
			return;
		}

		// https://hotexamples.com/examples/-/EasyRdf_Graph/serialise/php-easyrdf_graph-serialise-method-examples.html
		if ( is_scalar( $output ) ) {
			$outputPage->addHeadItem( 'json-ld', Html::Element(
					'script', [ 'type' => 'application/ld+json' ], $output
				)
			);
		}
	}
}
