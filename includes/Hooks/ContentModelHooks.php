<?php
namespace MediaWiki\Extension\DataMaps\Hooks;

use MediaWiki\Extension\DataMaps\Content\MapContentFactory;
use MediaWiki\Extension\DataMaps\Content\MapContentHandler;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Migration\Fandom\FandomMapContentHandler;
use MediaWiki\Title\Title;

// @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

final class ContentModelHooks implements
    \MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook
{
    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly MapContentFactory $mapContentFactory
    ) { }

    public static function onRegistration(): bool {
        global $wgContentHandlers;
        global $wgDataMapsNamespaceId, $wgDataMapsAllowExperimentalFeatures, $wgDataMapsEnableFandomPortingTools,
            $wgDataMapsEnableNavigatorRefactor;

        define( 'CONTENT_MODEL_DATAMAPS', 'datamap' );
        define( 'CONTENT_MODEL_NAVIGATOR_MAP', 'navigatormap' );
        define( 'CONTENT_FORMAT_NAVIGATOR_MAP', 'application/json+navigator' );
        define( 'CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT', 'interactivemap' );

        if ( $wgDataMapsAllowExperimentalFeatures && $wgDataMapsEnableFandomPortingTools && $wgDataMapsNamespaceId === 2900 ) {
            $wgContentHandlers[CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT] = FandomMapContentHandler::class;
        }

        if ( $wgDataMapsAllowExperimentalFeatures && $wgDataMapsEnableNavigatorRefactor ) {
            $wgContentHandlers[CONTENT_MODEL_NAVIGATOR_MAP] = [
                'class' => MapContentHandler::class,
                'services' => [
                    ExtensionConfig::SERVICE_NAME,
                    MapContentFactory::SERVICE_NAME,
                ],
            ];
        }

        return true;
    }

    /**
     * Promotes map content model as default for pages in the Map namespace, optionally checking if the title prefix is
     * satisfied.
     */
    public function onContentHandlerDefaultModelFor( $title, &$model ) {
        if ( $title->getNamespace() !== $this->config->getNamespaceId() ) {
            return;
        }

        if ( $this->mapContentFactory->isDocumentationTitle( $title ) ) {
            return;
        }

        $prefixMsg = wfMessage( 'datamap-standard-title-prefix' )->inContentLanguage()->plain();
        if ( $prefixMsg !== '-' && !str_starts_with( $title->getText(), $prefixMsg ) ) {
            return;
        }

        $model = $this->config->hasNavigatorRefactorEnabled() ? CONTENT_MODEL_NAVIGATOR_MAP
            : CONTENT_MODEL_DATAMAPS;
    }

    /**
     * Informs Extension:CodeEditor that map pages should use JSON highlighting.
     *
     * @param Title $title
     * @param string &$languageCode
     * @return void
     */
    public static function onCodeEditorGetPageLanguage( Title $title, &$languageCode ) {
        if (
            $title->hasContentModel( CONTENT_MODEL_DATAMAPS )
            || $title->hasContentModel( CONTENT_MODEL_NAVIGATOR_MAP )
        ) {
            $languageCode = 'json';
        }

        return true;
    }
}
