<?php
namespace MediaWiki\Extension\DataMaps\Rendering;

use MediaWiki\Extension\DataMaps\LegacyCompat\Data\DataMapSpec;
use MediaWiki\Extension\DataMaps\LegacyCompat\Data\MapBackgroundOverlaySpec;
use MediaWiki\Extension\DataMaps\LegacyCompat\Data\MapBackgroundTileSpec;
use MediaWiki\Extension\DataMaps\LegacyCompat\Data\MarkerGroupSpec;
use MediaWiki\Extension\DataMaps\LegacyCompat\Data\MarkerSpec;
use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapFileUtils;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Title\Title;
use OOUI\HtmlSnippet;
use OOUI\PanelLayout;

class EmbedRenderer {
    private bool $useInlineData;
    private bool $forVisualEditor;

    /** @var MarkerProcessorFactory */
    private MarkerProcessorFactory $markerProcessorFactory;

    public function __construct(
        private readonly Title $title,
        private readonly DataMapSpec $data,
        private readonly Parser $linkageParser,
        private readonly ParserOutput $parserOutput,
        array $options = []
    ) {
        $this->useInlineData = $options['inlineData'] ?? false;
        $this->forVisualEditor = $options['ve'] ?? false;
        $this->markerProcessorFactory = MediaWikiServices::getInstance()->getService(
            MarkerProcessorFactory::SERVICE_NAME );
    }

    public function getId(): int {
        return $this->title->getArticleID();
    }

    public function prepareOutput() {
        $this->enableOOUI();
        $this->addModules();
        $this->updateLinks();
    }

    public function enableOOUI(): void {
        $this->parserOutput->setEnableOOUI( true );
        \OOUI\Theme::setSingleton( new \OOUI\WikimediaUITheme() );
        \OOUI\Element::setDefaultDir( 'ltr' );
    }

    public function addModules(): void {
        $this->parserOutput->addModuleStyles( [
            'ext.datamaps.core.styles',
            // Wiki-provided CSS
            'ext.datamaps.site.styles'
        ] );
        $this->parserOutput->addModules( [
            // ext.datamaps.leaflet is loaded on demand (when a DataMap is initialised) in a separate request to not delay the
            // site module
            'ext.datamaps.core',
            // Initialiser module to boot the maps
            'ext.datamaps.bootstrap',
            // Wiki-provided JS
            'ext.datamaps.site'
        ] );
    }

    public function updateLinks(): void {
        // Mix-ins
        $fragments = $this->data->getRequiredFragments();
        if ( $fragments !== null ) {
            foreach ( $fragments as &$fragment ) {
                $this->parserOutput->addTemplate( $fragment, $fragment->getArticleId(),
                    $this->linkageParser->fetchCurrentRevisionRecordOfTitle( $fragment )->getId() );
            }
        }

        // Backgrounds
        foreach ( $this->data->getBackgrounds() as &$background ) {
            // Main image
            if ( !$background->hasTiles() ) {
                DataMapFileUtils::registerImageDependency( $this->parserOutput, $background->getImageName() );
            }
            // Tiles
            if ( $background->hasTiles() ) {
                $background->iterateTiles( function ( MapBackgroundTileSpec $spec ) {
                    DataMapFileUtils::registerImageDependency( $this->parserOutput, $spec->getImageName() );
                } );
            }
            // Image overlays
            if ( $background->hasOverlays() ) {
                $background->iterateOverlays( function ( MapBackgroundOverlaySpec $spec ) {
                    if ( $spec->getImageName() != null ) {
                        DataMapFileUtils::registerImageDependency( $this->parserOutput, $spec->getImageName() );
                    }
                } );
            }
        }

        // Groups
        $this->data->iterateGroups( function ( MarkerGroupSpec $spec ) {
            // Icon
            if ( $spec->getIcon() !== null ) {
                DataMapFileUtils::registerImageDependency( $this->parserOutput, $spec->getIcon() );
            }
            // Article link
            if ( $spec->getSharedRelatedArticle() !== null ) {
                $linkTarget = Title::newFromText( $spec->getSharedRelatedArticleTarget() );
                if ( $linkTarget ) {
                    $this->parserOutput->addLink( $linkTarget );
                }
            }
        } );

        // Markers
        $marker = new MarkerSpec( new \stdclass() );
        $this->data->iterateRawMarkerMap( function ( string $layers, array $rawCollection ) use ( &$marker ) {
            foreach ( $rawCollection as &$rawMarker ) {
                $marker->reassignTo( $rawMarker );
                // Popup image
                if ( $marker->getPopupImage() !== null ) {
                    DataMapFileUtils::registerImageDependency( $this->parserOutput, $marker->getPopupImage() );
                }
                // Icon overrides
                if ( $marker->getCustomIcon() !== null ) {
                    DataMapFileUtils::registerImageDependency( $this->parserOutput, $marker->getCustomIcon() );
                }
                // Article link
                if ( $marker->getRelatedArticle() !== null ) {
                    $linkTarget = Title::newFromText( $marker->getRelatedArticleTarget() );
                    if ( $linkTarget ) {
                        $this->parserOutput->addLink( $linkTarget );
                    }
                }
            }
        } );
    }

    private function makeEmbedConfig( EmbedRenderOptions $options ): string {
        $data = [
            'id' => $this->getId(),
            'presentationFlags' => 0,
        ];

        if ( $options->markerIdToCentreOn || $options->markerIdToOpen ) {
            $data['focusedMarker'] = $options->markerIdToCentreOn ?? $options->markerIdToOpen;
            $data['presentationFlags'] |= $options->markerIdToCentreOn ? 1 << 30 : 0;
        }

        return FormatJson::encode( $data, false, FormatJson::UTF8_OK );
    }

    public function getHtml( EmbedRenderOptions $options ): string {
        $titleFormatter = MediaWikiServices::getInstance()->getTitleFormatter();

        // Primary slots
        $containerMain = new PanelLayout( [
            'classes' => [
                'ext-datamaps-container',
                Sanitizer::escapeClass( 'map-' . $titleFormatter->getText( $this->title ) ),
            ],
            'framed' => true,
            'expanded' => false,
            'padded' => false
        ] );
        $containerTop = new PanelLayout( [
            'classes' => [ 'ext-datamaps-container-top' ],
            'framed' => false,
            'expanded' => false,
            'padded' => false
        ] );
        $containerContent = new PanelLayout( [
            'classes' => [ 'ext-datamaps-container-content' ],
            'framed' => false,
            'expanded' => false,
            'padded' => false
        ] );
        $containerBottom = new PanelLayout( [
            'classes' => [ 'ext-datamaps-container-bottom' ],
            'framed' => false,
            'expanded' => false,
            'padded' => false
        ] );

        // Push page ID onto the container, and mark the element as generated by the server
        $containerMain->setAttributes( [
            'data-datamap-id' => $this->getId(),
            'data-mw-datamaps' => 'map',
            'data-mw-datamaps-configure' => $this->makeEmbedConfig( $options ),
        ] );

        // Set width CSS
        if ( $options->maxWidthPx !== null ) {
            $containerMain->setAttributes( [
                'style' => 'max-width: ' . $options->maxWidthPx . 'px'
            ] );
        }

        // Add custom classes
        if ( $options->classes !== null ) {
            $containerMain->addClasses( $options->classes );
        }

        // Set up the 'mini' layout
        if ( $options->miniStyle ) {
            $containerMain->addClasses( [ 'ext-datamaps-mini-layout' ] );
        }

        // Pass the focused marker ID to the client
        if ( $options->markerIdToCentreOn || $options->markerIdToOpen ) {
            $containerMain->setAttributes( [
                'data-focused-marker' => $options->markerIdToCentreOn ?? $options->markerIdToOpen,
            ] );
        }

        // Stack the containers
        $containerMain->appendContent( $containerTop );
        $containerMain->appendContent( $containerContent );
        $containerMain->appendContent( $containerBottom );

        // Add legend to the output if it's enabled
        if ( $this->data->getSettings()->isLegendDisabled() && !$this->forVisualEditor ) {
            $containerMain->addClasses( [ 'ext-datamaps-legend-is-hidden' ] );
        }

        // Set data attribute with filters if they are specified
        if ( $options !== null && $options->displayGroups != null ) {
            $containerMain->setAttributes( [ 'data-filter-groups' => implode( '|', $options->displayGroups ) ] );
        }

        // Leaflet area
        $containerContent->appendContent( new HtmlSnippet( $this->getLeafletContainerHtml() ) );

        // Deliver map configuration via a <script> tag. Prior to v0.13.0 this was delivered via mw.config, but that has the
        // downside of slowing down the page load (config is delivered via head, but not actually used until the script is
        // loaded), and can no longer be done in MW 1.39 without heavily polluting the store.
        // This configuration is used to set up the map before any data sets are downloaded. It allows for environment to be
        // prepared.
        // TODO: possibly deliver some of this stuff via API? tho dunno if there's any real benefits to that
        $config = new EmbedConfigGenerator( $this->title, $this->data, [
            'inlineData' => $this->useInlineData,
            've' => $this->forVisualEditor,
            'layers' => $options ? $options->displayGroups : null
        ] );
        $containerMain->appendContent( new HtmlSnippet( $config->makeElement() ) );

        if ( $this->useInlineData ) {
            $processor = $this->markerProcessorFactory->create( $this->title, $this->data, null );
            $containerMain->appendContent( new HtmlSnippet( Html::element(
                'script',
                [
                    'type' => 'application/datamap+json',
                    'data-purpose' => 'markers'
                ],
                FormatJson::encode( $processor->processAll(), false, FormatJson::UTF8_OK )
            ) ) );
        }

        $containerMain->prependContent( new HtmlSnippet( Html::rawElement(
            'noscript',
            [],
            ( new \OOUI\MessageWidget( [
                'type' => 'error',
                'label' => wfMessage( 'datamap-javascript-required' )->inContentLanguage()
            ] ) )->toString() . Html::rawElement(
                'style',
                [
                    'type' => 'text/css'
                ],
                '.ext-datamaps-container{display:none}'
            )
        ) ) );

        return $containerMain->toString();
    }

    public function getLeafletContainerHtml(): string {
        return Html::rawElement(
            'div',
            [
                'class' => 'ext-datamaps-container-leaflet'
            ],
            Html::rawElement(
                'div',
                [
                    'class' => 'ext-datamaps-container-status'
                ],
                ( new \OOUI\ProgressBarWidget( [
                    'progress' => false,
                    'infusable' => true
                ] ) )->toString() . Html::rawElement(
                    'div',
                    [],
                    wfMessage( 'datamap-loading-js' )->inContentLanguage()
                )
            )
        );
    }
}
