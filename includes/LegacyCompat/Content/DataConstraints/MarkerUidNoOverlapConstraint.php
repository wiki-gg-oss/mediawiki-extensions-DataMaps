<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use stdClass;

class MarkerUidNoOverlapConstraint extends DataConstraint {
    private const MESSAGE = 'datamap-validate-constraint-muidoverlap';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        if ( !isset( $data->markers ) ) {
            return true;
        }

        $result = true;

        $set = [];
        foreach ( (array)$data->markers as $assocStr => $markers ) {
            foreach ( $markers as $index => $marker ) {
                if ( isset( $marker->id ) ) {
                    if ( isset( $set[$marker->id] ) ) {
                        $this->emitError( self::MESSAGE, "/markers/$assocStr/$index/id", $marker->id );
                        $result = false;
                    }
                    $set[$marker->id] = true;
                }
            }
        }

        return $result;
    }
}
