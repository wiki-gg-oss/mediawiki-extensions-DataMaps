<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Data;

class MapBackgroundTileSpec extends DataModel {
    protected static string $publicName = 'MapBackgroundTileSpec';

    public function getImageName(): string {
        return $this->raw->image;
    }

    public function getPlacementLocation(): ?array {
        return isset( $this->raw->position ) ? $this->raw->position : null;
    }
}
