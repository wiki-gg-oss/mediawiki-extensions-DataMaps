<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Content\Content;
use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Extension\DataMaps\Constants;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapContentFactory;
use MediaWiki\Extension\DataMaps\Content\MapJsonFormatter;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderOptions;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use StatusValue;
use stdClass;

class MapContentHandler extends JsonContentHandler {
    public function __construct(
        $modelId = CONTENT_MODEL_NAVIGATOR_MAP,
        private readonly ExtensionConfig $config,
        private readonly MapContentFactory $mapContentFactory
    ) {
        parent::__construct( $modelId );
		$this->mSupportedFormats = [ CONTENT_FORMAT_NAVIGATOR_MAP ];
    }

    /**
     * @inheritDoc
     */
    protected function getContentClass() {
        return MapContent::class;
    }

    /**
     * @inheritDoc
     */
    public function getActionOverrides() {
        return [
            // 'editmap' => EditMapAction::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function makeEmptyContent(): MapContent {
        return new MapContent( MapJsonFormatter::serialiseObject( $this->mapContentFactory->createDefaultData() ) );
    }

    /**
     * @inheritDoc
     */
    public function canBeUsedOn( Title $title ) {
        return $title->getNamespace() === $this->config->getNamespaceId() && parent::canBeUsedOn( $title );
    }

    /**
     * @inheritDoc
     */
    public function isParserCacheSupported() {
        return true;
    }

    /**
     * @inheritDoc
     */
	public function supportsPreloadContent(): bool {
		return true;
	}

    /**
     * @inheritDoc
     */
    public function validateSave( Content $content, ValidationParams $validationParams ): StatusValue {
        return $this->validate( $content, $validationParams->getPageIdentity() );
    }

    /**
     * Checks whether the map is valid.
     */
    public function validate( Content $content, PageIdentity $page ): StatusValue {
        // TODO: wire things together
        return StatusValue::newGood();
    }

    /**
     * @inheritDoc
     */
    protected function fillParserOutput(
        Content $content,
        ContentParseParams $cpoParams,
        ParserOutput &$parserOutput
    ): void {
        /** @var MapContent $content */

        $page = $cpoParams->getPage();
        $revId = $cpoParams->getRevId();
        $parserOptions = $cpoParams->getParserOptions();
        $generateHtml = $cpoParams->getGenerateHtml();
        $parser = MediaWikiServices::getInstance()->getParserFactory()->getInstance();
        $sourceCode = $content->getData();

        $docTitle = $this->mapContentFactory->getDocumentationTitle( $page );
        $docMsg = $docTitle ? wfMessage(
            $docTitle->exists() ? 'datamap-doc-page-show' : 'datamap-doc-page-does-not-exist',
            $docTitle->getPrefixedText()
        )->inContentLanguage() : null;

        // Accumulate the following output:
        // - documentation (if any)
        // - validation notices (if any)
        // - a rendered embed of the map (if content is valid)
        $html = '';

        if ( $docMsg && !$docMsg->isDisabled() ) {
            // We need the documentation's ParserOutput to let it emit metadata without risking cache poisoning with
            // the OutputTransform pipeline.
            if ( $parserOptions->getTargetLanguage() === null ) {
                $parserOptions->setTargetLanguage( $docTitle->getPageLanguage() );
            }
            $parserOutput = $parser->parse( $docMsg->plain(), $page, $parserOptions, true, true, $revId );

            $html .= $parserOutput->getRawText();
        } else {
			$parserOutput = new ParserOutput();
			$parserOutput->setLanguage( $parserOptions->getTargetLanguage() ?? $docTitle->getPageLanguage() );
        }

        if ( $docTitle ) {
            // Mark the documentation as transcluded to enable change propagation.
            $parserOutput->addTemplate( $docTitle, $docTitle->getArticleID(), $docTitle->getLatestRevID() );
        }

        $validateStatus = $this->validate( $content, $page );
        if ( !$validateStatus->isOK() ) {
            // TODO: emit the validation notice
            MediaWikiServices::getInstance()->getTrackingCategories()
                ->addTrackingCategory( $parserOutput, 'datamap-category-maps-failing-validation', $page );
        }

        // TODO: emit metadata from the map's source code

        if ( !$generateHtml ) {
            // We've done everything which emits metadata and absolutely has to be done.
            $parserOutput->setRawText( '' );
            return;
        }

        if ( $validateStatus->isOK() ) {
            // TODO: render the map embed
        }

        $parserOutput->setRawText( $html );
    }
}
