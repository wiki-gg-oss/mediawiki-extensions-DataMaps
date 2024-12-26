<?php
namespace MediaWiki\Extension\DataMaps;

use Config;
use ExtensionRegistry;
use MediaWiki\Extension\DataMaps\Content\DataMapContent;
use MediaWiki\Extension\DataMaps\Content\SchemaProvider;
use MediaWiki\Extension\DataMaps\Content\SchemaRevision;
use MediaWiki\Extension\DataMaps\Migration\Fandom\FandomMapContentHandler;
use MediaWiki\Extension\DataMaps\Rendering\MarkerProcessor;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use Parser;
use RequestContext;
use Title;
use User;

// @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

final class HookHandler implements
    \MediaWiki\Hook\ParserFirstCallInitHook,
    \MediaWiki\Preferences\Hook\GetPreferencesHook,
    \MediaWiki\Hook\SkinTemplateNavigation__UniversalHook,
    \MediaWiki\ChangeTags\Hook\ChangeTagsListActiveHook,
    \MediaWiki\ChangeTags\Hook\ListDefinedTagsHook,
    \MediaWiki\Hook\RecentChange_saveHook,
    \MediaWiki\Storage\Hook\RevisionDataUpdatesHook
{
    /** @var ExtensionConfig */
    private ExtensionConfig $config;

    /**
     * @param ExtensionConfig $config
     */
    public function __construct( ExtensionConfig $config ) {
        $this->config = $config;
    }

    /**
     * Registers parser functions when a parser is initialised.
     *
     * @param Parser $parser
     * @return void
     */
    public function onParserFirstCallInit( $parser ) {
        $parser->setFunctionHook(
            'displaydatamap', [ ParserFunctions\EmbedMapFunction::class, 'run' ],
            Parser::SFH_NO_HASH | Parser::SFH_OBJECT_ARGS
        );
        if ( $this->config->isTransclusionAliasEnabled() ) {
            $parser->setFunctionHook(
                'displaydatamap_short', [ ParserFunctions\EmbedMapFunction::class, 'run' ],
                Parser::SFH_NO_HASH | Parser::SFH_OBJECT_ARGS
            );
        }
        $parser->setFunctionHook(
            'datamaplink', [ ParserFunctions\MapLinkFunction::class, 'run' ],
            Parser::SFH_OBJECT_ARGS
        );
    }

    /**
     * Defines our tags.
     *
     * @param string[] &$tags
     * @return void
     */
    public function onListDefinedTags( &$tags ) {
        $tags[] = 'datamaps-visualeditor';
    }

    /**
     * Registers our currently used tags.
     *
     * @param string[] &$tags
     * @return void
     */
    public function onChangeTagsListActive( &$tags ) {
        $tags[] = 'datamaps-visualeditor';
    }

    /**
     * Adds the "edited with visual map editor" tag to edits done over API [likely] by our visual editor.
     *
     * @param RecentChange $rc The new RC entry.
     * @return void
     */
    public function onRecentChange_save( $rc ) {
        $request = RequestContext::getMain()->getRequest();
        if ( $request->getBool( 'isdatamapsve' ) ) {
            $rc->addTags( 'datamaps-visualeditor' );
        }
    }

    /**
     * Returns available user preferences related to the visual editor.
     *
     * @param User $user
     * @param array &$preferences
     * @return void
     */
    public function onGetPreferences( $user, &$preferences ) {
        if ( $this->config->isVisualEditorEnabled() ) {
            $preferences[Constants::PREFERENCE_ENABLE_VE__FUTURE] = [
                'type' => 'toggle',
                'label-message' => 'datamap-userpref-enable-ve',
                'section' => 'editing/editor'
            ];
            $preferences[Constants::PREFERENCE_ENABLE_VE] = [
                'type' => 'toggle',
                'label-message' => 'datamap-userpref-enable-ve-beta',
                'section' => 'editing/editor'
            ];
        }
    }

    /**
     * @internal
     */
    public static function canUseVE( ?User $user, Title $title ): bool {
        $prefsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
        $pageProps = MediaWikiServices::getInstance()->getPageProps();
        $config = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );

        return $config->isVisualEditorEnabled()
            && $title->getNamespace() === $config->getNamespaceId()
            && $title->hasContentModel( CONTENT_MODEL_DATAMAPS )
            && $title->exists()
            && ( $user === null || $prefsLookup->getBoolOption( $user, Constants::PREFERENCE_ENABLE_VE ) )
            && count( $pageProps->getProperties( $title, Constants::PAGEPROP_DISABLE_VE ) ) <= 0;
    }

    private function canCreateMapWithGui( Title $title ): bool {
        return $this->config->isCreateMapEnabled()
            && $title->getNamespace() === $this->config->getNamespaceId()
            && $title->hasContentModel( CONTENT_MODEL_DATAMAPS )
            && !$title->exists();
    }

    /**
     * Configures article navigation links for maps:
     *
     * - Non-existent maps that can be created: "Create Map" wizard lazy-loader is scheduled;
     * - Existing maps that can be edited: the "Edit" link is hijacked to use the visual editor.
     *
     * @param SkinTemplate $skinTemplate
     * @param array &$links
     * @return void
     */
    public function onSkinTemplateNavigation__Universal( $skinTemplate, &$links ): void {
        if ( !isset( $links['views']['edit'] ) ) {
            return;
        }

        $title = $skinTemplate->getRelevantTitle();

        // If this page does not exist yet and we can use the visual map creation workflow, offer it.
        //
        // Otherwise if the page exists, the instance has visual editor enabled, and the user is opted into it, inject
        // the visual=1 query parameter into the "Edit" link, and add an "Edit source" link right after it.
        if ( self::canCreateMapWithGui( $title ) ) {
            $skinTemplate->getOutput()->addModules( [
                'ext.datamaps.createMapLazy'
            ] );
        } elseif ( self::canUseVE( $skinTemplate->getAuthority()->getUser(), $title ) ) {
            $links['views']['edit']['href'] = $title->getLocalURL( [ 'action' => 'editmap' ] + $skinTemplate->editUrlOptions() );
            $links['views'] = array_slice( $links['views'], 0, 2, true ) + [
                'editsource' => [
                    'text' => wfMessage( 'datamap-ve-edit-source-action' )->text(),
                    'href' => $title->getLocalURL( $skinTemplate->editUrlOptions() )
                ]
            ] + array_slice( $links['views'], 2, null, true );
        }
    }

    /**
     * On maps, expands ParserOutput's metadata to include markers, as a way of deferring parsing those markers. This
     * is expensive as many parse calls will be invoked.
     *
     * @param Title $title
     * @param RenderedRevision $renderedRevision
     * @param DeferrableUpdate[] &$updates
     * @return void
     */
    public function onRevisionDataUpdates( $title, $renderedRevision, &$updates ) {
        if ( $this->config->shouldLinksUpdatesUseMarkers()
            && $title->getNamespace() === $this->config->getNamespaceId()
            && $title->hasContentModel( CONTENT_MODEL_DATAMAPS ) ) {
            foreach ( $updates as &$updater ) {
                if ( $updater instanceof \MediaWiki\Deferred\LinksUpdate\LinksUpdate ) {
                    $parserOutput = $updater->getParserOutput();
                    $revision = $renderedRevision->getRevision();
                    $content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC, null );
                    // Cast content to a data model
                    $dataMap = $content->asModel();
                    // Prepare a parser
                    $parser = MediaWikiServices::getInstance()->getParser();
                    $parserOptions = \ParserOptions::newFromAnon();
                    $parser->setOptions( $parserOptions );
                    $parser->parse( '', $title, $parserOptions, false, true );
                    // Creating a marker model backed by an empty object, as it will later get reassigned to actual data to avoid
                    // creating thousands of small, very short-lived (only one at a time) objects
                    $marker = new Data\MarkerSpec( new \stdclass() );
                    // The budget controls remaining time we may spend on parsing wikitext in the markers
                    $budget = $this->config->getLinksUpdateBudget();
                    $startTime = microtime( true );

                    $dataMap->iterateRawMarkerMap( static function ( string $_, array $rawCollection )
                        use ( &$parser, &$title, &$parserOptions, &$marker, &$budget, &$startTime ) {
                        // Parse labels and descriptions of each marker, and drop the text. We only care about the metadata here.
                        foreach ( $rawCollection as &$rawMarker ) {
                            $marker->reassignTo( $rawMarker );
                            if ( $marker->getLabel() !== null
                                && MarkerProcessor::shouldParseString( $marker, $marker->getLabel() ) ) {
                                $parser->parse( $marker->getLabel(), $title, $parserOptions, false, false );
                            }
                            if ( $marker->getDescription() !== null
                                && MarkerProcessor::shouldParseString( $marker, $marker->getDescription() ) ) {
                                $parser->parse( $marker->getDescription(), $title, $parserOptions, false, false );
                            }
                            $parser->getOutput()->setText( '' );

                            // Subtract the budget and stop iteration
                            $budget -= microtime( true ) - $startTime;
                            if ( $budget <= 0 ) {
                                return false;
                            }
                        }
                    } );

                    // Merge the metadata gathered after parsing all the markers
                    $parserOutput->mergeTrackingMetaDataFrom( $parser->getOutput() );
                    break;
                }
            }
        }
    }

    public static function getJsConfig( \MediaWiki\ResourceLoader\Context $context, Config $config ): array {
        $extConfig = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );
        return [
            'IsBleedingEdge' => $extConfig->hasExperimentalFeatures(),
            'IsVisualEditorEnabled' => $extConfig->isVisualEditorEnabled(),
            'IsMapLazyLoadingEnabled' => $extConfig->isMapLazyLoadingEnabled(),
            'TabberNeueModule' => 'ext.tabberNeue',
            // TODO: not the brightest way
            'CanAnonsEdit' => array_key_exists( 'edit', $config->get( MainConfigNames::GroupPermissions )[ '*' ] )
        ];
    }

    public static function getCreateMapConfig( \MediaWiki\ResourceLoader\Context $context, Config $config ): array {
        return [
            'PREFERRED_SCHEMA_VERSION' => SchemaRevision::RECOMMENDED_REVISION
        ];
    }
}
