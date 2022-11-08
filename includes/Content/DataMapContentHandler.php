<?php
namespace MediaWiki\Extension\Ark\DataMaps\Content;

use Content;
use Html;
use JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Extension\Ark\DataMaps\Data\DataMapSpec;
use MediaWiki\Extension\Ark\DataMaps\ExtensionConfig;
use MediaWiki\Extension\Ark\DataMaps\Rendering\EmbedRenderOptions;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Status;
use stdclass;
use Title;

class DataMapContentHandler extends JsonContentHandler {
    public function __construct( $modelId = ARK_CONTENT_MODEL_DATAMAP ) {
        parent::__construct( $modelId, [ ARK_CONTENT_MODEL_DATAMAP ] );
    }

    protected function getContentClass() {
        return DataMapContent::class;
    }

    /**
     * Only allow this content handler to be used in the configured data namespace
     */
    public function canBeUsedOn( Title $title ) {
        if ( $title->getNamespace() !== ExtensionConfig::getNamespaceId() ) {
            return false;
        }

        return parent::canBeUsedOn( $title );
    }

    public function validateSave( Content $content, ValidationParams $validationParams ) {
        '@phan-var DataMapContent $content';
        return $content->getValidationStatus();
    }

    public static function getDocPage( Title $title ) {
        $docPage = wfMessage( 'datamap-doc-page-suffix' )->inContentLanguage();
        return $docPage->isDisabled() ? null : Title::newFromText( $title->getPrefixedText() . $docPage->plain() );
    }

    public function isParserCacheSupported() {
        return true;
    }

    protected function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$parserOutput ) {
        '@phan-var DataMapContent $content';

        if ( !$content->isValid() ) {
            // FIXME:
            $parserOutput->setText( 'Invalid JSON content' );
            return;
        }

        $pageRef = $cpoParams->getPage();
        $parserOptions = $cpoParams->getParserOptions();

		$shouldGenerateHtml = $cpoParams->getGenerateHtml();
		$isVisualEditor = $parserOptions->getOption( 'isMapVisualEditor' );
		$isEditPreview = $parserOptions->getIsPreview();

		// Get documentation, if any
		$doc = self::getDocPage( $cpoParams->getPage() );
		if ( $shouldGenerateHtml && $doc ) {
			$msg = wfMessage( $doc->exists() ? 'datamap-doc-page-show' : 'datamap-doc-page-does-not-exist',
				$doc->getPrefixedText() )->inContentLanguage();

            if ( !$msg->isDisabled() ) {
                // We need the ParserOutput for categories and such, so we can't use $msg->parse()
                $docViewLang = $doc->getPageViewLanguage();
                $dir = $docViewLang->getDir();

                $docWikitext = Html::rawElement(
                    'div',
                    [
                        'lang' => $docViewLang->getHtmlCode(),
                        'dir' => $dir,
                        'class' => "mw-content-$dir",
                    ],
                    "\n" . $msg->plain() . "\n"
                );

                if ( $parserOptions->getTargetLanguage() === null ) {
                    $parserOptions->setTargetLanguage( $doc->getPageLanguage() );
                }

                $parserOutput = MediaWikiServices::getInstance()->getParser()
                    ->parse( $docWikitext, $pageRef, $parserOptions, true, true, $cpoParams->getRevId() );
            }

            // Mark the doc page as a transclusion, so we get purged when it changes
            $parserOutput->addTemplate( $doc, $doc->getArticleID(), $doc->getLatestRevID() );
        }

		// Render the map if it isn't a mix-in
		if ( !$content->isMixin() ) {
			// If previewing an edit, run validation and end early on failure
			if ( $shouldGenerateHtml && $isEditPreview ) {
				$status = $content->getValidationStatus();
				if ( !$status->isOK() ) {
					$parserOutput->setText( $parserOutput->getRawText() . Html::errorBox(
						wfMessage(
							'datamap-error-cannot-' . ( $isVisualEditor ? 'open-ve' : 'preview' ) . '-validation-errors',
							$status->getMessage( false, false )
						)
					) );
					return;
				}
			}

			// Initialise the embed renderer
			$parser = MediaWikiServices::getInstance()->getParser();
			$embed = $content->getEmbedRenderer( $pageRef, $parser, $parserOutput, $isEditPreview, $isVisualEditor );
			// Add metadata
			$embed->prepareOutput( $parserOutput );

			// Generate HTML if requested
			if ( $shouldGenerateHtml ) {
				$parserOutput->setText( $parserOutput->getRawText() . $embed->getHtml( new EmbedRenderOptions() ) );
			}
		} else {
			$output->setProperty( 'ext.datamaps.isMapMixin', true );
			$output->setProperty( 'ext.datamaps.isIneligibleForVE', true );
		}
	}
}
