<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use stdClass;

class AssociationStringGroupExistsConstraint extends DataConstraint {
    private const MESSAGE = 'datamap-validate-constraint-groupexists';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        if ( !isset( $data->markers ) ) {
            return true;
        }

        $result = true;

        $groupIds = array_keys( (array)( $data->groups ?? new stdClass() ) );
        foreach ( array_keys( (array)$data->markers ) as $assocStr ) {
            $assocGroup = explode( ' ', $assocStr, 2 )[0];
            if ( !in_array( $assocGroup, $groupIds ) ) {
                $this->emitErrorPermissive( self::MESSAGE, "/markers/$assocStr", $assocGroup );
                $result = false;
            }
        }

        return $result;
    }
}
