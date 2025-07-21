<?php
namespace MediaWiki\Extension\DataMaps\Data;

use MediaWiki\Extension\DataMaps\Rendering\Utils\DataMapColourUtils;

// TODO: this is kind of a mess, needs a rewrite

class MapBackgroundOverlaySpec extends DataModel {
    protected static string $publicName = 'MapBackgroundOverlaySpec';

    public const TYPE_RECT = 1;
    public const TYPE_POLYLINE = 2;
    public const TYPE_IMAGE = 3;

    public function getName(): ?string {
        return isset( $this->raw->name ) ? $this->raw->name : null;
    }

    public function getType(): int {
        if ( $this->getImageName() != null ) {
            return self::TYPE_IMAGE;
        } elseif ( $this->getPath() != null ) {
            return self::TYPE_POLYLINE;
        }
        return self::TYPE_RECT;
    }

    public function getImageName(): ?string {
        return isset( $this->raw->image ) ? $this->raw->image : null;
    }

    public function getPlacementLocation(): array {
        return $this->raw->at;
    }

    public function getPath(): ?array {
        return isset( $this->raw->path ) ? $this->raw->path : null;
    }

    public function wantsImageGapWorkaround(): bool {
        return $this->raw->reduceGaps ?? false;
    }

    public function isImagePixelated(): bool {
        return $this->raw->pixelated ?? false;
    }

    public function supportsDrawProperties(): bool {
        return $this->getType() != self::TYPE_IMAGE;
    }

    public function getRawFillColour() /*: ?array|string*/ {
        return isset( $this->raw->color ) ? $this->raw->color : null;
    }

    public function getFillColour(): ?array {
        return DataMapColourUtils::decode4( $this->getRawFillColour() );
    }

    public function getPolylineThickness(): ?float {
        return isset( $this->raw->thickness ) ? $this->raw->thickness : null;
    }

    public function getRawRectStrokeColour() /*: ?array|string*/ {
        return isset( $this->raw->borderColor ) ? $this->raw->borderColor : null;
    }

    public function getRectStrokeColour(): ?array {
        return DataMapColourUtils::decode( $this->getRawRectStrokeColour() );
    }
}
