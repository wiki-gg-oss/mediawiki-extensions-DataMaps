<?php

namespace MediaWiki\Extension\DataMaps\Api;

use stdClass;

class ApiMapPropConfig extends ApiMapPropBase {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'c' );
	}

	protected function getAllowedParams() {
		return [];
	}

	public function execute() {
		$this->getParent()->fetchContent();
		$this->outputSettings();
		$this->outputMarkerTypes();
	}

	private function outputSettings(): void {
		$data = $this->getParent()->fetchContent()->getData()->getValue();

		if ( isset( $data->subtitle ) ) {
			$this->getResult()->addValue( 'map', 'subtitleHtml', $this->getWikitextParser()->parse( $data->subtitle,
				stripOuterParagraph: true ) );
		}

		$settings = $data->settings ?? new stdClass();
		$this->getResult()->addValue( 'map', 'displayCoordinates', $settings->displayCoordinates ?? true );
	}

	private function outputMarkerTypes(): void {
		$this->getResult()->addValue( 'map', 'markerTypes', $this->transcribeMarkerTypes() );
	}

	private function transcribeMarkerTypes(): array {
		$data = $this->getParent()->fetchContent()->getData()->getValue();
		if ( !isset( $data->markerTypes ) ) {
			return [];
		}

		return $this->transformMarkerTypeArray( $data->markerTypes );
	}

	private function transformMarkerTypeArray( array $items ): array {
		return array_map( fn ( $item ) => $this->transformMarkerType( $item ), $items );
	}

	private function transformMarkerType( stdClass $data ): array {
		$parser = $this->getWikitextParser();
		$result = [];

		if ( isset( $data->id ) ) {
			$result['type'] = 'MarkerType';
			$result['id'] = $data->id;
		} else {
			$result['type'] = 'Group';
		}

		$result['nameHtml'] = $parser->parse( $data->name, stripOuterParagraph: true );

		if ( isset( $data->description ) ) {
			$result['descriptionHtml'] = $parser->parse( $data->description );
		}

		if ( isset( $data->include ) ) {
			$result['subTypes'] = $this->transformMarkerTypeArray( $data->include );
		}

		return $result;
	}
}
