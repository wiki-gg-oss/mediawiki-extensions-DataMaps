<?php

namespace MediaWiki\Extension\DataMaps\Api;

use ApiBase;
use ApiModuleManager;
use MediaWiki\Extension\DataMaps\Api\Props\PropModuleConfig;
use MediaWiki\Extension\DataMaps\Api\WikitextParser\CoreWikitextParser;
use MediaWiki\Extension\DataMaps\Api\WikitextParser\IWikitextParser;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapContentFactory;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Title\Title;
use Wikimedia\ObjectFactory\ObjectFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ApiGetMap extends ApiBase {
	private const PROP_MODULES = [
		'config' => [
			'class' => PropModuleConfig::class,
		],
	];

	private readonly ApiModuleManager $moduleMgr;

	private ?IWikitextParser $parser = null;
	private ?Title $title = null;
	private ?RevisionRecord $rev = null;
	private ?MapContent $contentObj = null;

	public function __construct(
		$query,
		$moduleName,
		private readonly ObjectFactory $objectFactory,
		private readonly ParserFactory $parserFactory,
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
				ParamValidator::PARAM_ISMULTI => true,
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

		$this->getResult()->addValue( 'map', 'title', $this->title->getFullText() );
		$this->getResult()->addValue( 'map', 'pageid', $this->title->getId() );
		$this->getResult()->addValue( 'map', 'revid', $this->rev->getId() );

		if ( isset( $params['prop'] ) ) {
			foreach ( $params['prop'] as $prop ) {
				$instance = $this->moduleMgr->getModule( $prop, 'prop' );
				if ( $instance === null ) {
					ApiBase::dieDebug( __METHOD__, 'Error instantiating module' );
				}
			
				$instance->execute();
			}
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

	public function getWikitextParser(): IWikitextParser {
		$this->parser ??= new CoreWikitextParser(
			$this->title,
			$this->rev->getId(),
			$this->parserFactory
		);
		return $this->parser;
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
