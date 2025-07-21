<?php
namespace MediaWiki\Extension\DataMaps\Rendering;

use InvalidArgumentException;
use MediaWiki\Extension\DataMaps\Data\CoordinateSystem;
use MediaWiki\Extension\DataMaps\Data\DataMapSpec;
use MediaWiki\Extension\DataMaps\Data\MapBackgroundOverlaySpec;
use MediaWiki\Extension\DataMaps\Data\MapBackgroundSpec;
use MediaWiki\Extension\DataMaps\Data\MapBackgroundTileSpec;
use MediaWiki\Extension\DataMaps\Data\MapSettingsSpec;
use MediaWiki\Extension\DataMaps\Data\MarkerGroupSpec;
use MediaWiki\Extension\DataMaps\Data\MarkerLayerSpec;
use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapColourUtils;
use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapFileUtils;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\Title\Title;

class EmbedConfigGenerator {
    public const MARKER_ICON_WIDTH = MarkerGroupSpec::DEFAULT_ICON_SIZE[0];
    public const LEGEND_ICON_WIDTH = 24;
    public const NUMBER_OF_MARKERS_FOR_CANVAS = 500;

    public DataMapSpec $data;
    private Title $title;
    private bool $useInlineData;
    private bool $forVisualEditor;
    private ?array $requireLayers;

    public function __construct( Title $title, DataMapSpec $data, array $options ) {
        $this->title = $title;
        $this->data = $data;
        $this->useInlineData = $options['inlineData'] ?? false;
        $this->forVisualEditor = $options['ve'] ?? false;

        $this->requireLayers = null;
        if ( is_array( $options['layers'] ) && count( $options['layers'] ) > 0 ) {
            $this->requireLayers = $options['layers'];
        }
    }

    public function getId(): int {
        return $this->title->getArticleID();
    }

    public function makeElement(): string {
        return Html::element(
            'script',
            [
                'type' => 'application/datamap+json',
                'data-purpose' => 'config'
            ],
            FormatJson::encode( $this->makeArray(), false, FormatJson::UTF8_OK )
        );
    }

    public function makeArray(): array {
        $out = [];

        // Required to query the API for marker clusters
        if ( !$this->useInlineData && !$this->forVisualEditor ) {
            $out['version'] = $this->title->getLatestRevID();
            $out['lastPurgeTimestamp'] = $this->title->getTouched();
        }
        // Coordinate transformation
        $out['cOrigin'] = $coordOrder = $this->data->getCoordinateSystem()->getOrigin();
        $out['cOrder'] = $coordOrder = $this->data->getCoordinateSystem()->getOrder();
        $out['cRot'] = $this->data->getCoordinateSystem()->getRotation();
        if ( $this->data->getCoordinateSystem()->isLegacy() ) {
            $out['crs'] = $this->data->getCoordinateSystem()->getNormalisedBox();
        }
        // Feature management
        $bitmask = $this->getPublicFeatureBitMask();
        if ( $bitmask != 0 ) {
            $out['flags'] = $bitmask;
        }
        // Backdrop colour
        if ( $this->data->getSettings()->getBackdropColour() !== null ) {
            $out['backdrop'] = DataMapColourUtils::asHex( $this->data->getSettings()->getBackdropColour() );
        }
        // Zoom settings
        $zoom = $this->data->getSettings()->getZoomSettings();
        $out['zoom'] = [
            'lock' => $zoom->isLocked(),
            'auto' => $zoom->isMinimumAutomatic(),
            'min' => $zoom->getMinimum(),
            'max' => $zoom->getMaximum(),
            'scrollSpeed' => $zoom->getScrollSpeed(),
        ];
        // Backgrounds
        $out['backgrounds'] = array_map( function ( MapBackgroundSpec $spec ) use ( $coordOrder ) {
            return $this->getBackgroundConfig( $spec, $coordOrder );
        }, $this->data->getBackgrounds() );
        // Marker groups
        $out['groups'] = [];
        $this->data->iterateGroups( function ( MarkerGroupSpec $spec ) use ( &$out ) {
            if ( $this->requireLayers && !in_array( $spec->getId(), $this->requireLayers ) ) {
                return;
            }

            $out['groups'][$spec->getId()] = $this->getMarkerGroupConfig( $spec );
        } );
        // Marker layers
        $out['layers'] = [];
        $out['layerIds'] = $this->data->getLayerNames();
        $this->data->iterateDefinedLayers( function ( MarkerLayerSpec $spec ) use ( &$out ) {
            $out['layers'][$spec->getId()] = $this->getMarkerLayerConfig( $spec );
        } );
        // Disclaimer in the filters panel
        if ( $this->data->getDisclaimerText() !== null ) {
            // TODO: No parser support yet; after GH#165
            $out['disclaimer'] = $this->data->getDisclaimerText();
        }
        // Custom Leaflet settings
        if ( $this->data->getSettings()->getCustomLeafletConfig() != null ) {
            $out['leafletSettings'] = $this->data->getSettings()->getCustomLeafletConfig();
        }
        // Custom embeddable data
        if ( $this->data->getCustomData() != null ) {
            $out['custom'] = $this->data->getCustomData();
        }

        return $out;
    }

    public function getPublicFeatureBitMask(): int {
        $settings = $this->data->getSettings();
        $out = 0;
        $out |= $settings->shouldShowCoordinates() ? 1 << 0 : 0;
        $out |= ( $settings->getLegendVisibilityMode() === MapSettingsSpec::LM_HIDDEN ) ? 1 << 1 : 0;
        // TODO: legacy option, drop in v0.17
        $out |= $settings->isZoomDisabled() ? 1 << 2 : 0;
        $out |= $settings->getSearchMode() ? 1 << 3 : 0;
        $out |= $settings->getChecklistSortMode() ? 1 << 4 : 0;
        $out |= $settings->getSearchMode() === MapSettingsSpec::SM_TABBER ? 1 << 5 : 0;
        $out |= $this->forVisualEditor ? 1 << 6 : 0;
        $out |= ( $this->useInlineData || $this->forVisualEditor ) ? 1 << 7 : 0;
        $out |= $settings->allowsFullscreen() ? 1 << 9 : 0;
        $out |= $settings->isSleepBasedInteractionModel() ? 1 << 10 : 0;
        $out |= $settings->areTooltipPopupsEnabled() ? 1 << 11 : 0;
        $out |= ( $settings->getLegendVisibilityMode() === MapSettingsSpec::LM_COLLAPSED ) ? 1 << 12 : 0;
        $out |= ( !$this->data->getCoordinateSystem()->isLegacy() ) ? 1 << 13 : 0;

        $markerCount = 0;
        $this->data->iterateRawMarkerMap( static function ( string $_, array $rawCollection ) use ( &$markerCount ) {
            $markerCount += count( $rawCollection );
        } );
        if ( self::isCanvasPreferred( $settings->getIconRendererType(), $markerCount ) ) {
            $out |= 1 << 8;
        }

        return $out;
    }

    private static function isCanvasPreferred( int $irt, int $markerCount ): bool {
        if ( $irt === MapSettingsSpec::IRT_AUTO ) {
            return $markerCount >= self::NUMBER_OF_MARKERS_FOR_CANVAS;
        } elseif ( $irt === MapSettingsSpec::IRT_CANVAS ) {
            return true;
        }
        return false;
    }

    private function getBackgroundConfig( MapBackgroundSpec $spec, int $coordOrder ): array {
        $out = [];
        if ( !$spec->hasTiles() ) {
            $out['image'] = DataMapFileUtils::getRequiredFile( $spec->getImageName() )->getURL();
        }
        $name = $spec->getName();
        if ( $name ) {
            $out['name'] = $name;
        }
        if ( $spec->getPlacementLocation() !== null ) {
            $out['at'] = CoordinateSystem::normaliseBox( $spec->getPlacementLocation(), $coordOrder );
        }
        if ( $spec->getBackgroundLayerName() !== null ) {
            $out['layer'] = $spec->getBackgroundLayerName();
        }
        if ( $spec->isPixelated() ) {
            $out['pixelated'] = true;
        }
        if ( $spec->hasOverlays() || $spec->hasTiles() ) {
            $out['overlays'] = [];
        }
        if ( $spec->hasTiles() ) {
            // Anti-aliasing opt-in used by the frontend
            $out['aa'] = 1;
            // Translate all tiles into overlays
            $tileOffset = CoordinateSystem::normalisePoint( $spec->getTilePlacementOffset() ?? [ 0, 0 ], $coordOrder );
            $out['tileOffset'] = $tileOffset;
            $tileSize = CoordinateSystem::normalisePoint( $spec->getTileSize(), $coordOrder );
            $out['tileSize'] = $tileSize;
            $pixelated = $spec->isPixelated();
            $spec->iterateTiles( function ( MapBackgroundTileSpec $tile ) use (
                &$out, $coordOrder, $pixelated
            ) {
                $out['tiles'][] = $this->convertTile( $tile, $coordOrder, $pixelated );
            } );
        }
        if ( $spec->hasOverlays() ) {
            $spec->iterateOverlays( function ( MapBackgroundOverlaySpec $overlay ) use ( &$out, $coordOrder ) {
                $out['overlays'][] = $this->convertBackgroundOverlay( $overlay, $coordOrder );
            } );
        }

        return $out;
    }

    private function convertBackgroundOverlay( MapBackgroundOverlaySpec $spec, int $coordOrder ) {
        $result = [];
        if ( $spec->getName() != null ) {
            $result['name'] = $spec->getName();
        }
        if ( $spec->getImageName() != null ) {
            $image = DataMapFileUtils::getRequiredFile( $spec->getImageName() );
            $result['image'] = $image->getURL();

            if ( $spec->wantsImageGapWorkaround() ) {
                $result['aa'] = 1;
            }
            if ( $spec->isImagePixelated() ) {
                $result['pixelated'] = true;
            }
        }
        if ( $spec->getPath() != null ) {
            $result['path'] = $spec->getPath();
        } else {
            $result['at'] = CoordinateSystem::normaliseBox( $spec->getPlacementLocation(), $coordOrder );
        }

        if ( $spec->supportsDrawProperties() ) {
            if ( $spec->getRawFillColour() !== null ) {
                $result['colour'] = DataMapColourUtils::asHex( $spec->getFillColour() );
            }
            if ( $spec->getPolylineThickness() !== null ) {
                $result['thickness'] = $spec->getPolylineThickness();
            }
            if ( $spec->getRawRectStrokeColour() !== null ) {
                $result['strokeColour'] = DataMapColourUtils::asHex( $spec->getRectStrokeColour() );
            }
        }

        return $result;
    }

    private function convertTile(
        MapBackgroundTileSpec $spec,
        int $coordOrder,
        bool $pixelated
    ) {
        $result = [];

        $result['image'] = DataMapFileUtils::getRequiredFile( $spec->getImageName() )->getURL();
        $result['position'] =CoordinateSystem::normalisePoint( $spec->getPlacementLocation(), $coordOrder );
        $result['aa'] = 1;
        if ( $pixelated ) {
            $result['pixelated'] = true;
        }
        return $result;
    }

    public function getPublicGroupFeatureBitMask( MarkerGroupSpec $spec ): int {
        $out = 0;
        $out |= $spec->wantsChecklistNumbering() ? 1 << 0 : 0;
        $out |= !$spec->isIncludedInSearch() ? 1 << 1 : 0;
        $out |= !$spec->isDefault() ? 1 << 2 : 0;
        switch ( $spec->getCollectibleMode() ) {
            case MarkerGroupSpec::CM_INDIVIDUAL:
                $out |= 1 << 3;
                break;
            case MarkerGroupSpec::CM_AS_ONE:
                $out |= 1 << 4;
                break;
            case MarkerGroupSpec::CM_AS_ONE_GLOBAL:
                $out |= 1 << 5;
                break;
        }
        $out |= !$spec->isSwitchable() ? 1 << 6 : 0;
        $out |= $spec->isStaticallySized() ? 1 << 7 : 0;
        return $out;
    }

    public function getMarkerGroupConfig( MarkerGroupSpec $spec ): array {
        $out = [
            'name' => $spec->getName(),
            'size' => $spec->getSize(),
        ];

        $flags = $this->getPublicGroupFeatureBitMask( $spec );
        if ( $flags !== 0 ) {
            $out['flags'] = $flags;
        }

        if ( $spec->getDescription() !== null ) {
            // TODO: No parser support yet; after GH#165
            $out['description'] = $spec->getDescription();
        }

        switch ( $spec->getDisplayMode() ) {
            case MarkerGroupSpec::DM_CIRCLE:
                $out['fillColor'] = DataMapColourUtils::asHex( $spec->getFillColour() );

                if ( $spec->getExtraMinZoomSize() != null ) {
                    $out['zoomScaleFactor'] = $spec->getExtraMinZoomSize();
                }
                break;
            case MarkerGroupSpec::DM_ICON:
                $out['markerIcon'] = DataMapFileUtils::getMarkerIconUrl( $spec->getIcon(), $out['size'][0] );
                break;
            case MarkerGroupSpec::DM_PIN:
                $out['pinColor'] = DataMapColourUtils::asHex( $spec->getPinColour() );
                break;
            default:
                throw new InvalidArgumentException( wfMessage( 'datamap-error-render-unsupported-displaymode',
                    $spec->getDisplayMode() ) );
        }

        if ( in_array( $spec->getDisplayMode(), [ MarkerGroupSpec::DM_PIN, MarkerGroupSpec::DM_CIRCLE ] ) ) {
            if ( $spec->getRawStrokeColour() != null ) {
                $out['strokeColor'] = DataMapColourUtils::asHex( $spec->getStrokeColour() );
            }

            if ( $spec->getStrokeWidth() != MarkerGroupSpec::DEFAULT_VECTOR_STROKE_WIDTH ) {
                $out['strokeWidth'] = $spec->getStrokeWidth();
            }
        }

        if ( $spec->getIcon() !== null ) {
            $out['legendIcon'] = DataMapFileUtils::getFileUrl( $spec->getIcon(), self::LEGEND_ICON_WIDTH );
        }

        if ( $spec->getSharedRelatedArticle() !== null ) {
            $out['article'] = $spec->getSharedRelatedArticle();
        }

        return $out;
    }

    public function getMarkerLayerConfig( MarkerLayerSpec $spec ): array {
        $out = [];

        if ( $spec->getName() !== null ) {
            $out['name'] = $spec->getName();
        }

        if ( $spec->getPopupDiscriminator() !== null ) {
            $out['discrim'] = $spec->getPopupDiscriminator();
        }

        if ( $spec->getIconOverride() !== null ) {
            $out['markerIcon'] = DataMapFileUtils::getMarkerIconUrl( $spec->getIconOverride(),
                MarkerGroupSpec::DEFAULT_ICON_SIZE[0] );
        }

        return $out;
    }
}
