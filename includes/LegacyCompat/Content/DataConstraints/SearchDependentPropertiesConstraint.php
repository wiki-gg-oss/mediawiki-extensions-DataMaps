<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\StatusUtils;
use stdClass;

class SearchDependentPropertiesConstraint extends DataConstraint {
    private const MESSAGE = 'datamap-validate-constraint-searchdisabled';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        if ( !isset( $data->settings ) ) {
            return true;
        }

        $hasSearch = ( $data->settings->enableSearch ?? false ) !== false;
        if ( $hasSearch ) {
            return true;
        }

        if ( isset( $data->groups ) ) {
            foreach ( (array)$data->groups as $groupId => $group ) {
                $fields = array_intersect( array_keys( (array)$group ), [
                    'canSearchFor',
                ] );
                if ( count( $fields ) > 0 ) {
                    $this->emitWarning( self::MESSAGE, "/groups/$groupId", StatusUtils::formatArray( $fields ) );
                }
            }
        }

        // TODO: check markers

        return true;
    }
}
