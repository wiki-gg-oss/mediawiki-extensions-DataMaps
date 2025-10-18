<?php

namespace MediaWiki\Extension\DataMaps\Api;

use ApiBase;
use ApiModuleManager;
use MediaWiki\Title\Title;
use Wikimedia\ObjectFactory\ObjectFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ApiGetMap extends ApiBase {
	private const PROP_MODULES = [
		'config' => [
			'class' => ApiMapPropConfig::class,
		],
	];

	private readonly ApiModuleManager $moduleMgr;

	public function __construct(
		$query,
		$moduleName,
		private readonly ObjectFactory $objectFactory
	) {
		parent::__construct( $query, $moduleName, '' );

		$this->moduleMgr = new ApiModuleManager(
			$this,
			$objectFactory
		);

		$this->moduleMgr->addModules( self::PROP_MODULES, 'prop' );
	}

	/**
	 * Overrides to return this instance's module manager.
	 * @return ApiModuleManager
	 */
	public function getModuleManager() {
		return $this->moduleMgr;
	}

	protected function getAllowedParams() {
		return [
			'pageid' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'prop' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'submodule',
			],
		];
	}

	public function execute() {
		$params = $this->extractRequestParams();

		$title = Title::newFromID( $params['pageid'] );
		if ( !$title ) {
			$this->dieWithError( [ 'apierror-nosuchpageid', $params['pageid'] ] );
		}

		$this->checkTitleUserPermissions( $title, [ 'read' ] );

		if ( isset( $params['prop'] ) ) {
			$instance = $this->moduleMgr->getModule( $params['prop'], 'prop' );
			if ( $instance === null ) {
				ApiBase::dieDebug( __METHOD__, 'Error instantiating module' );
			}
			
			$instance->execute();
		}
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [];
	}
}
