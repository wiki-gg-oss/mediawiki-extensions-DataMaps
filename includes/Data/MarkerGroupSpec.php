<?php
namespace MediaWiki\Extension\DataMaps\Data;

use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapColourUtils;
use Status;
use stdclass;

class MarkerGroupSpec extends DataModel {
    protected static string $publicName = 'MarkerGroupSpec';

    public const DEFAULT_CIRCLE_SIZE = 12.5;
    public const DEFAULT_VECTOR_STROKE_WIDTH = 1;
    public const DEFAULT_ICON_SIZE = [ 32, 32 ];

    // Display modes
    public const DM_CIRCLE = 1;
    public const DM_ICON = 2;
    public const DM_PIN = 3;
    public const DM_UNKNOWN = -1;

    // Collectible modes
    public const CM_INDIVIDUAL = 1;
    public const CM_AS_ONE = 2;
    public const CM_AS_ONE_GLOBAL = 3;
    public const CM_UNKNOWN = -1;

    private string $id;

    public function __construct( string $id, stdclass $raw ) {
        parent::__construct( $raw );
        $this->id = $id;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->raw->name;
    }

    public function getDescription(): ?string {
        return $this->raw->description ?? null;
    }

    private function getSizePropertyInternal() {
        return isset( $this->raw->size ) ? $this->raw->size : null;
    }

    public function getSize() {
        switch ( $this->getDisplayMode() ) {
            case self::DM_CIRCLE:
                return $this->getSizePropertyInternal() ?? self::DEFAULT_CIRCLE_SIZE;
            case self::DM_PIN:
                $out = $this->getSizePropertyInternal() ?? self::DEFAULT_ICON_SIZE[0];
                return [ $out, $out ];
            case self::DM_ICON:
                $out = $this->getSizePropertyInternal() ?? self::DEFAULT_ICON_SIZE;
                // Ensure 2D
                if ( is_numeric( $out ) ) {
                    $out = [ $out, $out ];
                }
                return $out;
            default:
                return null;
        }
    }

    public function getExtraMinZoomSize() {
        return isset( $this->raw->extraMinZoomSize ) ? $this->raw->extraMinZoomSize : null;
    }

    public function getRawFillColour() /*: ?array|string*/ {
        return isset( $this->raw->fillColor ) ? $this->raw->fillColor : null;
    }

    public function getRawPinColour() /*: ?array|string*/ {
        return isset( $this->raw->pinColor ) ? $this->raw->pinColor : null;
    }

    public function getRawStrokeColour() /*: ?array|string*/ {
        return $this->raw->strokeColor ?? null;
    }

    public function getFillColour(): array {
        return DataMapColourUtils::decode4( $this->getRawFillColour() );
    }

    public function getPinColour(): array {
        return DataMapColourUtils::decode4( $this->getRawPinColour() );
    }

    public function getStrokeColour(): array {
        if ( $this->getRawStrokeColour() != null ) {
            return DataMapColourUtils::decode4( $this->getRawStrokeColour() );
        }

        return $this->getFillColour();
    }

    public function getStrokeWidth() /*: ?int|float */ {
        return $this->raw->strokeWidth ?? self::DEFAULT_VECTOR_STROKE_WIDTH;
    }

    public function getIcon(): ?string {
        return isset( $this->raw->icon ) ? $this->raw->icon : null;
    }

    public function getDisplayMode(): int {
        if ( $this->getRawPinColour() !== null ) {
            return self::DM_PIN;
        } elseif ( $this->getRawFillColour() !== null ) {
            return self::DM_CIRCLE;
        } elseif ( $this->getIcon() !== null ) {
            return self::DM_ICON;
        }
        return self::DM_UNKNOWN;
    }

    public function getSharedRelatedArticle(): ?string {
        if ( isset( $this->raw->article ) && $this->raw->article ) {
            return $this->raw->article;
        }
        return null;
    }

    public function getSharedRelatedArticleTarget(): ?string {
        $value = $this->getSharedRelatedArticle();
        if ( !$value ) {
            return null;
        }

        if ( str_contains( $value, '|' ) ) {
            return explode( '|', $value, 2 )[ 0 ];
        }

        return $value;
    }

    public function getCollectibleMode(): ?int {
        if ( isset( $this->raw->isCollectible ) ) {
            if ( $this->raw->isCollectible === true ) {
                $this->raw->isCollectible = 'individual';
            }

            switch ( $this->raw->isCollectible ) {
                case "individual":
                    return self::CM_INDIVIDUAL;
                case "group":
                    return self::CM_AS_ONE;
                case "globalGroup":
                    return self::CM_AS_ONE_GLOBAL;
            }
        }
        return null;
    }

    public function isSwitchable(): bool {
        return $this->raw->isSwitchable ?? true;
    }

    public function wantsChecklistNumbering(): bool {
        return isset( $this->raw->autoNumberInChecklist ) ? $this->raw->autoNumberInChecklist : false;
    }

    public function isIncludedInSearch(): bool {
        return isset( $this->raw->canSearchFor ) ? $this->raw->canSearchFor : true;
    }

    public function isDefault(): bool {
        return isset( $this->raw->isDefault ) ? $this->raw->isDefault : true;
    }

    /**
     * @since 0.17.10
     * @return bool
     */
    public function isStaticallySized(): bool {
        return $this->raw->static ?? false;
    }
}
