<?php

namespace MediaWiki\Extension\DataMaps\Api\Props;

use stdClass;

class PropModuleFeatures extends PropModule {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'f' );
	}

	protected function getAllowedParams() {
		return [];
	}

	public function execute() {
		$this->getParent()->fetchContent();
		$this->outputFeatures();
	}

	private function outputFeatures(): void {
		$data = $this->getParent()->fetchContent()->getData()->getValue();

		$results = [];
		if ( isset( $data->features ) ) {
			$results = $this->transformFeaturesArray( $data->features );
		}

		$this->getResult()->addValue( 'map', 'features', $results );
	}

	private function transformFeaturesArray( array $items ): array {
		// TODO: paging
		return array_values( array_filter( array_map( fn ( $item ) => $this->transformFeature( $item ), $items ) ) );
	}

	private function transformFeature( stdClass $data ): ?array {
		$wtParser = $this->getWikitextParser();
		$fileExport = $this->getFileExportUtils();

		static $pos00 = [ 0, 0 ];

		$typeName = null;
		$location = $pos00;
		$props = null;
		$childNodes = null;

		switch ( $data->type ) {
			case 'FeatureCollection':
				// TODO: child nodes should be emitted into the parent's list
				$typeName = $data->type;
				$childNodes = $this->transformFeaturesArray( $data->attachFeatures ?? [] );
				break;

			case 'BackgroundImage':
				$typeName = $data->type;
				$location = $data->at ?? $pos00;

				// TODO: should use batching!!!
				$fileObj = $fileExport->findFile( $data->image );
				$props = [
					'dimens' => $fileExport->getDimensionsVec( $fileObj, $data->dimensions ?? 'same-as-file' ),
					'imageUrl' => $fileExport->getFullResImageUrl( $fileObj ),
				];
				if ( isset( $data->attachFeatures ) ) {
					$childNodes = $this->transformFeaturesArray( $data->attachFeatures );
				}
				break;
			
			case 'Text':
				$typeName = $data->type;
				$location = $data->at ?? $pos00;
				$props = [
					'html' => $wtParser->parse( $data->content, true ),
				];
				break;

			case 'MarkerCollection':
				$typeName = $data->type;
				$props = [
					'markerType' => $data->attachType,
				];
				$childNodes = $this->transformMarkersArray( $data->attachFeatures ?? [] );
				break;
		}

		// Bail if the type name has not been copied
		if ( $typeName === null ) {
			return null;
		}

		// Turn location vector compact if the axis are equal
		if ( $location[0] === $location[1] ) {
			$location = $location[0];
		}

		// Turn properties object into a zero if it's empty but we have child nodes
		$hasProps = $props !== null && !empty( $props );
		$hasChildNodes = $childNodes !== null && !empty( $childNodes );
		if ( $hasChildNodes && !$hasProps ) {
			$props = 0;
		}

		// Use a specialised slot format depending on available data
		if ( $hasChildNodes ) {
			return [ $typeName, $location, $props, $childNodes ];
		} elseif ( $hasProps ) {
			return [ $typeName, $location, $props ];
		} else {
			return [ $typeName, $location ];
		}
	}

	private function transformMarkersArray( array $items ): array {
		// TODO: paging
		return array_values( array_filter( array_map( fn ( $item ) => $this->transformMarker( $item ), $items ) ) );
	}

	private function transformMarker( stdClass $data ): ?array {
		$wtParser = $this->getWikitextParser();
		$fileExport = $this->getFileExportUtils();

		static $pos00 = [ 0, 0 ];

		$location = $data->at ?? $pos00;
		$props = [];

		if ( isset( $data->title ) ) {
			$props['titleHtml'] = $wtParser->parse( $data->title );
		}
		if ( isset( $data->description ) ) {
			$props['descHtml'] = $wtParser->parse( $data->description );
		}
		if ( isset( $data->image ) ) {
			// TODO: should use batching!!!
			$fileObj = $fileExport->findFile( $data->image );
			$props['imgDimens'] = $fileExport->getDimensionsVec( $fileObj, 'same-as-file' );
			$props['imgUrl'] = $fileExport->getFullResImageUrl( $fileObj );
		}

		// Turn location vector compact if the axis are equal
		if ( $location[0] === $location[1] ) {
			$location = $location[0];
		}

		// Use a specialised slot format depending on available data
		$hasProps = !empty( $props );
		if ( $hasProps ) {
			return [ $location, $props ];
		} else {
			return [ $location ];
		}
	}
}
