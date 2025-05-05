<?php
namespace MediaWiki\Extension\DataMaps\Hooks;

use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Migration\Fandom\FandomMapContentHandler;
use Title;

// @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

final class ContentModelHooks implements
    \MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook
{
    /** @var ExtensionConfig */
    private ExtensionConfig $config;

    /**
     * @param ExtensionConfig $config
     */
    public function __construct( ExtensionConfig $config ) {
        $this->config = $config;
    }

    public static function onRegistration(): bool {
        define( 'CONTENT_MODEL_DATAMAPS', 'datamap' );
        define( 'CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT', 'interactivemap' );

        global $wgContentHandlers, $wgDataMapsNamespaceId, $wgDataMapsAllowExperimentalFeatures, $wgDataMapsEnableFandomPortingTools;
        if ( $wgDataMapsAllowExperimentalFeatures && $wgDataMapsEnableFandomPortingTools && $wgDataMapsNamespaceId === 2900 ) {
            $wgContentHandlers[CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT] = FandomMapContentHandler::class;
        }

        return true;
    }

    private static function isDocPage( Title $title ) {
        $docPage = wfMessage( 'datamap-doc-page-suffix' )->inContentLanguage();
        return !$docPage->isDisabled() && str_ends_with( $title->getPrefixedText(), $docPage->plain() );
    }

    /**
     * Promotes map content model as default for pages in the Map namespace, optionally checking if the title prefix is
     * satisfied.
     *
     * @param Title $title
     * @param string &$model
     * @return void
     */
    public function onContentHandlerDefaultModelFor( $title, &$model ) {
        if ( $title->getNamespace() === $this->config->getNamespaceId() && !self::isDocPage( $title ) ) {
            $prefix = wfMessage( 'datamap-standard-title-prefix' )->inContentLanguage();
            if ( $prefix !== '-' && str_starts_with( $title->getText(), $prefix->plain() ) ) {
                $model = CONTENT_MODEL_DATAMAPS;
            }
        }

        return true;
    }

    /**
     * Informs Extension:CodeEditor that map pages should use JSON highlighting.
     *
     * @param Title $title
     * @param string &$languageCode
     * @return void
     */
    public static function onCodeEditorGetPageLanguage( Title $title, &$languageCode ) {
        if ( $title->hasContentModel( CONTENT_MODEL_DATAMAPS ) ) {
            $languageCode = 'json';
        }

        return true;
    }
}
