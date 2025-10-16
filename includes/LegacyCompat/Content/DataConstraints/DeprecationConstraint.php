<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use stdClass;

class DeprecationConstraint extends DataConstraint {
    private const INSTEAD_MESSAGE = 'datamap-validate-constraint-deprecated-instead';
    private const NO_ALTERNATIVES_MESSAGE = 'datamap-validate-constraint-deprecated-noalt';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        // REV17 - REV18: settings.leaflet - no replacement
        if ( isset( $data->settings ) && isset( $data->settings->leaflet ) ) {
            $this->emitWarning( self::NO_ALTERNATIVES_MESSAGE, 'v17', 'v18', '/data/settings/leaflet' );
        }

        return true;
    }
}
