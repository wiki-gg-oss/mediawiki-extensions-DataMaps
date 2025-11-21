<?php

namespace MediaWiki\Extension\DataMaps\Api\Props;

use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapColourUtils;
use stdClass;

class PropModuleConfig extends PropModule {
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
		$data = $this->getParent()->fetchContent()->getData()->getValue();

		$results = [];
		if ( isset( $data->markerTypes ) ) {
			$results = $this->transformMarkerTypeArray( $data->markerTypes );
		}

		$this->getResult()->addValue( 'map', 'markerTypes', $results );
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

		$result['name'] = $data->name;

		if ( isset( $data->description ) ) {
			$result['descriptionHtml'] = $parser->parse( $data->description );
		}

		if ( isset( $data->include ) ) {
			$result['subTypes'] = $this->transformMarkerTypeArray( $data->include );
		}

		$result['defaultStyle'] = $this->transformMarkerStyleArray( $data->defaultStyle ?? null );

		return $result;
	}

	private function transformMarkerStyleArray( ?stdClass $data ): array {
		static $dataEmpty = new stdClass();
		$data ??= $dataEmpty;

		$result = [
			'pointForm' => 'pin',
			'size' => 20,
			'outline' => [ 'colour' => '#36cf', 'width' => 2 ],
			'fill' => [ 'colour' => '#36cc' ]
		];

		$result['pointForm'] = $data->pointForm ?? $result['pointForm'];
		$result['size'] = $data->size ?? $result['size'];
		if ( isset( $data->outline ) ) {
			if ( $data->outline === false ) {
				unset( $result['outline'] );
			} else {
				$result['outline']['colour'] = $data->outline->color ?? $result['outline']['colour'];
				$result['outline']['width'] = $data->outline->width ?? $result['outline']['width'];
			}
		}
		if ( isset( $data->fill ) ) {
			if ( $data->fill === false ) {
				unset( $result['fill'] );
			} else {
				$result['fill']['colour'] = $data->fill->color ?? $result['fill']['colour'];
			}
		}

		if ( array_key_exists( 'outline', $result ) ) {
			$rgba = DataMapColourUtils::decode4( $result['outline']['colour'] );
			$a = round( ( $rgba[3] ?? 255 ) / 255, 3 );
			$rgb = [ $rgba[0], $rgba[1], $rgba[2] ];
			$result['outline']['colour'] = DataMapColourUtils::asHex( $rgb );
			$result['outline']['opacity'] = $a;
		}
		if ( array_key_exists( 'fill', $result ) ) {
			$rgba = DataMapColourUtils::decode4( $result['fill']['colour'] );
			$a = round( ( $rgba[3] ?? 255 ) / 255, 3 );
			$rgb = [ $rgba[0], $rgba[1], $rgba[2] ];
			$result['fill']['colour'] = DataMapColourUtils::asHex( $rgb );
			$result['fill']['opacity'] = $a;
		}

		return $result;
	}
}
