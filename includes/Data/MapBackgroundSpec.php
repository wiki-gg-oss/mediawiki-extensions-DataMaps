<?php
namespace MediaWiki\Extension\DataMaps\Data;

class MapBackgroundSpec extends DataModel {
    protected static string $publicName = 'MapBackgroundSpec';

    public static function fromImageName( string $file ) {
        $fake = new \stdClass();
        $fake->image = $file;
        return new MapBackgroundSpec( $fake );
    }

    public function getImageName(): string {
        return $this->raw->image;
    }

    public function getPlacementLocation(): ?array {
        return isset( $this->raw->at ) ? $this->raw->at : null;
    }

    public function getTilePlacementOffset(): ?array {
        return isset( $this->raw->at ) ? $this->raw->at : null;
    }

    public function getName(): ?string {
        return isset( $this->raw->name ) ? $this->raw->name : null;
    }

    public function isPixelated(): bool {
        return $this->raw->pixelated ?? false;
    }

    public function getBackgroundLayerName(): ?string {
        return isset( $this->raw->associatedLayer ) ? $this->raw->associatedLayer : null;
    }

    public function hasTiles(): bool {
        return isset( $this->raw->tiles );
    }

    public function getTileSize(): array {
        return $this->raw->tileSize;
    }

    public function iterateTiles( callable $callback ) {
        foreach ( $this->raw->tiles as &$raw ) {
            $callback( new MapBackgroundTileSpec( $raw ) );
        }
    }

    public function hasOverlays(): bool {
        return isset( $this->raw->overlays );
    }

    public function iterateOverlays( callable $callback ) {
        foreach ( $this->raw->overlays as &$raw ) {
            $callback( new MapBackgroundOverlaySpec( $raw ) );
        }
    }
}
