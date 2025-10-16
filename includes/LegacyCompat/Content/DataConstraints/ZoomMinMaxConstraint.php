<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use MediaWiki\Extension\DataMaps\LegacyCompat\Data\ZoomSettingsSpec;
use stdClass;

class ZoomMinMaxConstraint extends DataConstraint {
    private const MESSAGE = 'datamap-validate-constraint-zoomminmax';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        if ( !isset( $data->settings ) || !isset( $data->settings->zoom ) ) {
            return true;
        }

        $zoomMin = $data->settings->zoom->min ?? ZoomSettingsSpec::DEFAULT_MINIMUM;
        $zoomMax = $data->settings->zoom->max ?? ZoomSettingsSpec::DEFAULT_MAXIMUM;

        if ( $zoomMax < $zoomMin ) {
            $this->emitError( self::MESSAGE, '/settings/zoom/max', '/settings/zoom/min' );
            return false;
        }

        return true;
    }
}
