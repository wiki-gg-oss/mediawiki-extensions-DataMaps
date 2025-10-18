<?php

namespace MediaWiki\Extension\DataMaps\Api;

class ApiMapPropConfig extends ApiMapPropBase {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'mc' );
	}

	protected function getAllowedParams() {
		return [];
	}

	public function execute() {
		$result = $this->getResult();
		$result->addValue( 'map', 'test', 'Hello world' );
	}
}
