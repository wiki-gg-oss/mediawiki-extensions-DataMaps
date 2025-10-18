<?php

namespace MediaWiki\Extension\DataMaps\Api;

use ApiBase;

abstract class ApiMapPropBase extends ApiBase {
	public function __construct(
		protected readonly ApiGetMap $mainModule,
		$moduleName,
		$paramPrefix = ''
	) {
		parent::__construct( $mainModule->getMain(), $moduleName, $paramPrefix );
	}

	/** @inheritDoc */
	public function getParent() {
		return $this->mainModule;
	}
}
