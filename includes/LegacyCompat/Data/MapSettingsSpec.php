<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Data;

use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapColourUtils;
use stdClass;

class MapSettingsSpec extends DataModel {
    protected static string $publicName = 'MapSettingsSpec';

    public const SM_NONE = 0;
    public const SM_SELF = 1;
    public const SM_TABBER = 2;

    public const SC_GROUP_DECLARATION_ORDER = 0;
    public const SC_AMOUNT = 1;

    public const IRT_AUTO = 0;
    public const IRT_DOM = 1;
    public const IRT_CANVAS = 2;

    public const LM_DEFAULT = 0;
    public const LM_HIDDEN = 1;
    public const LM_COLLAPSED = 2;

    public function allowsFullscreen(): bool {
        return $this->raw->allowFullscreen ?? true;
    }

    public function areTooltipPopupsEnabled(): bool {
        return $this->raw->enableTooltipPopups ?? false;
    }

    public function getRawBackdropColour() /*: ?array|string*/ {
        return isset( $this->raw->backdropColor ) ? $this->raw->backdropColor : null;
    }

    public function getBackdropColour(): ?array {
        return DataMapColourUtils::decode( $this->getRawBackdropColour() );
    }

    public function getIconRendererType(): int {
        $value = $this->raw->iconRenderer ?? 'auto';
        switch ( $value ) {
            case 'DOM':
                return self::IRT_DOM;
            case 'canvas':
                return self::IRT_CANVAS;
        }
        return self::IRT_AUTO;
    }

    public function isZoomDisabled(): bool {
        return $this->getZoomSettings()->isLocked();
    }

    public function getZoomSettings(): ?ZoomSettingsSpec {
        return new ZoomSettingsSpec( $this->raw->zoom ?? new stdClass() );
    }

    public function getSearchMode(): int {
        $value = $this->raw->enableSearch ?? false;
        if ( $value === true ) {
            return self::SM_SELF;
        } elseif ( $value === 'tabberWide' ) {
            return self::SM_TABBER;
        }
        return self::SM_NONE;
    }

    public function isSleepBasedInteractionModel(): int {
        return ( $this->raw->interactionModel ?? 'keybinds' ) === 'sleep';
    }

    public function getLegendVisibilityMode(): int {
        $value = $this->raw->hideLegend ?? false;
        if ( $value === true ) {
            return self::LM_HIDDEN;
        } elseif ( $value === 'collapsed' ) {
            return self::LM_COLLAPSED;
        }
        return self::LM_DEFAULT;
    }

    public function isLegendDisabled(): bool {
        return $this->getLegendVisibilityMode() === self::LM_HIDDEN;
    }

    public function requiresMarkerIDs(): bool {
        return $this->raw->requireCustomMarkerIDs ?? false;
    }

    public function shouldShowCoordinates(): bool {
        return $this->raw->showCoordinates ?? true;
    }

    public function getChecklistSortMode(): int {
        switch ( $this->raw->sortChecklistsBy ?? 'groupDeclaration' ) {
            case 'amount':
                return self::SC_AMOUNT;
            default:
                return self::SC_GROUP_DECLARATION_ORDER;
        }
    }

    public function getCustomLeafletConfig(): ?object {
        return isset( $this->raw->leaflet ) ? $this->raw->leaflet : null;
    }
}
