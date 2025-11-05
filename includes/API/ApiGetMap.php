<?php

namespace MediaWiki\Extension\DataMaps\Api;

use ApiBase;
use ApiModuleManager;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapContentFactory;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
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

	private ?Title $title = null;
	private ?RevisionRecord $rev = null;
	private ?MapContent $contentObj = null;

	public function __construct(
		$query,
		$moduleName,
		private readonly ObjectFactory $objectFactory,
		private readonly RevisionStore $revisionStore,
		private readonly MapContentFactory $mapContentFactory
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
			'revid' => [
				ParamValidator::PARAM_REQUIRED => false,
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

		$this->title = Title::newFromID( $params['pageid'] );
		if ( !$this->title ) {
			$this->dieWithError( [ 'apierror-nosuchpageid', $params['pageid'] ] );
		}

		$this->checkTitleUserPermissions( $this->title, [ 'read' ] );

		if ( isset( $params['revid'] ) ) {
			$this->rev = $this->revisionStore->getRevisionById( $params['revid'], page: $this->title );
			if ( !$this->rev ) {
				$this->dieWithError( [ 'apierror-nosuchrevid', $params['revid'] ] );
			}
		} else {
			$this->rev = $this->revisionStore->getRevisionByTitle( $this->title );
		}

		if ( isset( $params['prop'] ) ) {
			$instance = $this->moduleMgr->getModule( $params['prop'], 'prop' );
			if ( $instance === null ) {
				ApiBase::dieDebug( __METHOD__, 'Error instantiating module' );
			}
			
			$instance->execute();
		}
	}

	public function getTitle(): Title {
		if ( $this->title === null ) {
			ApiBase::dieDebug( __METHOD__, 'Title is null but a submodule is being executed' );
		}

		return $this->title;
	}

	public function getRevisionRecord(): RevisionRecord {
		if ( $this->rev === null ) {
			ApiBase::dieDebug( __METHOD__, 'RevisionRecord is null but a submodule is being executed' );
		}

		return $this->rev;
	}

	public function fetchContent(): MapContent {
		if ( $this->contentObj === null ) {
			$contentStatus = $this->mapContentFactory->loadPageContentByRevision( $this->getRevisionRecord() );
			if ( !$contentStatus->isGood() ) {
				$this->dieStatus( $contentStatus );
			}

			$this->contentObj = $contentStatus->getValue();
		}

		return $this->contentObj;
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [];
	}
}
