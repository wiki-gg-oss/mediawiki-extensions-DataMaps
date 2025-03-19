<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Content\Content;
use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Extension\DataMaps\Constants;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderOptions;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use stdClass;

class DataMapContentHandler extends JsonContentHandler {
    public function __construct( $modelId = CONTENT_MODEL_DATAMAPS ) {
        parent::__construct( $modelId, [ CONTENT_MODEL_DATAMAPS ] );
    }

    protected function getContentClass() {
        return DataMapContent::class;
    }

    public function getActionOverrides() {
        return [
            'editmap' => EditMapAction::class,
        ];
    }

    public function makeEmptyContent() {
        /** @var SchemaProvider $schemaProvider */
        $schemaProvider = MediaWikiServices::getInstance()->getService( SchemaProvider::SERVICE_NAME );

        $raw = new stdClass();
        $raw->{'$schema'} = $schemaProvider->makePublicRecommendedUrl( true );
        return new DataMapContent( DataMapContent::toJSON( $raw ) );
    }

    /**
     * Only allow this content handler to be used in the configured data namespace
     */
    public function canBeUsedOn( Title $title ) {
        $config = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );

        if ( $title->getNamespace() !== $config->getNamespaceId() ) {
            return false;
        }

        return parent::canBeUsedOn( $title );
    }

    public function validateSave( Content $content, ValidationParams $validationParams ) {
        '@phan-var DataMapContent $content';
        return $content->getValidationStatus();
    }

    public static function getDocPage( PageReference $page ) {
        $docPage = wfMessage( 'datamap-doc-page-suffix' )->inContentLanguage();
        return $docPage->isDisabled() ? null : Title::newFromPageReference( $page )->getSubpage( $docPage->plain() );
    }

    public function isParserCacheSupported() {
        return true;
    }

    protected function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$parserOutput ) {
        /** @var DataMapContent $content */

        $pageRef = $cpoParams->getPage();
        $parserOptions = $cpoParams->getParserOptions();
        $parserOutput = new ParserOutput();

        // Render the prelude box
        if ( $cpoParams->getGenerateHtml() ) {
            $box = Html::noticeBox( wfMessage( 'datamap-mapsrcinfo-internal-page' )->inContentLanguage(), [] );
            $parserOutput->setText( $box );
        }

        // Generate the validation info box
        $info = $this->getSourceValidationInfo( $content );
        $isGood = $info['method'] !== 'errorBox';
        if ( $cpoParams->getGenerateHtml() ) {
            $boxText = implode( '', array_map( fn ( $value ) => "<p>$value</p>", $info['messages'] ) );
            // Methods used here:
            // - successBox
            // - warningBox
            // - errorBox
            $box = Html::{$info['method']}( $boxText );
            $parserOutput->setText( $parserOutput->getRawText() . $box );
        }

        if ( !$isGood ) {
            MediaWikiServices::getInstance()->getTrackingCategories()
                ->addTrackingCategory( $parserOutput, 'datamap-category-maps-failing-validation', $pageRef );
        }

        // If this is a fragment, record so in the page properties. This branch must also not run if the JSON is not
        // valid.
        if ( $content->getData()->isGood() && $content->isFragment() ) {
            $parserOutput->setPageProperty( Constants::PAGEPROP_IS_MIXIN, true );
            $parserOutput->setPageProperty( Constants::PAGEPROP_DISABLE_VE, true );
        }

        // Render documentation. We retain the parser output for later - per GH#145 we'll add the metadata after
        // rendering the map, so primary background is listed before anything else.
        $docOutput = null;
        $doc = self::getDocPage( $cpoParams->getPage() );
        if ( $doc ) {
            $msg = wfMessage( $doc->exists() ? 'datamap-doc-page-show' : 'datamap-doc-page-does-not-exist',
                $doc->getPrefixedText() )->inContentLanguage();

            if ( !$msg->isDisabled() ) {
                // We cannot use ->parse() on the message as we need the ParserOutput
                $docViewLang = $doc->getPageViewLanguage();
                $dir = $docViewLang->getDir();

                if ( $parserOptions->getTargetLanguage() === null ) {
                    $parserOptions->setTargetLanguage( $doc->getPageLanguage() );
                }

                $docWikitext = Html::rawElement(
                    'div',
                    [
                        'lang' => $docViewLang->getHtmlCode(),
                        'dir' => $dir,
                        'class' => "mw-content-$dir",
                    ],
                    "\n" . $msg->plain() . "\n"
                );
                $docOutput = MediaWikiServices::getInstance()->getParser()
                    ->parse( $docWikitext, $pageRef, $parserOptions, true, true, $cpoParams->getRevId() );

                if ( $cpoParams->getGenerateHtml() ) {
                    $parserOutput->setText( $parserOutput->getRawText() . $docOutput->getRawText() );
                }

                // Mark the doc page as a transclusion, so we get purged when it changes
                $parserOutput->addTemplate( $doc, $doc->getArticleID(), $doc->getLatestRevID() );
            }
        }

        // Render the map if this is not a fragment
        if ( $isGood && !$content->isFragment() ) {
            $parser = MediaWikiServices::getInstance()->getParser();
            $embed = $content->getEmbedRenderer( $pageRef, $parser, $parserOutput, [
                'inlineData' => $parserOptions->getIsPreview(),
            ] );
            $embed->prepareOutput( $parserOutput );
            if ( $cpoParams->getGenerateHtml() ) {
                $parserOutput->setText( $parserOutput->getRawText() . $embed->getHtml( new EmbedRenderOptions() ) );
            }
        }

        // Add documentation page metadata (GH#145)
        if ( $docOutput ) {
            $parserOutput->mergeHtmlMetaDataFrom( $docOutput );
            $parserOutput->mergeInternalMetaDataFrom( $docOutput );
            $parserOutput->mergeTrackingMetaDataFrom( $docOutput );
        }
    }

    /**
     * Packs validation results into a human-readable form.
     *
     * @param DataMapContent $content
     * @return array 'method' (successBox, warningBox, errorBox) and 'messages'
     */
    private function getSourceValidationInfo( DataMapContent $content ): array {
        $validationStatus = $content->getValidationStatus();
        [ $errors, $warnings ] = $validationStatus->splitByErrorType();

        $msgs = [
            '',
        ];

        // Render errors
        if ( $errors->getErrors() ) {
            $msgs[] = $errors->getMessage( false, 'datamap-mapsrcinfo-heading-errors' )->parse();
        }

        // Render warnings
        if ( $warnings->getErrors() ) {
            $msgs[] = $warnings->getMessage( false, 'datamap-mapsrcinfo-heading-warnings' )->parse();
        }

        // Determine box type
        $method = 'successBox';
        $leadMessage = 'datamap-mapsrcinfo-status-ok';
        if ( $errors->getErrors() ) {
            $method = 'errorBox';
            $leadMessage = 'datamap-mapsrcinfo-status-errors';
        } elseif ( $warnings->getErrors() ) {
            $method = 'warningBox';
            $leadMessage = 'datamap-mapsrcinfo-status-meh';
        }

        // Fill in the lead message
        $msgs[0] = wfMessage( $leadMessage )->inContentLanguage();

        // Add a message inviting to check out the documentation if there were issues
        if ( $method !== 'successBox' ) {
            $msgs[] = wfMessage(
                'datamap-mapsrcinfo-docslink',
                Constants::DOCUMENTATION_LINK,
                Constants::ISSUE_TRACKER_LINK
            )->inContentLanguage();
        }

        return [
            'method' => $method,
            'messages' => $msgs,
        ];
    }
}
