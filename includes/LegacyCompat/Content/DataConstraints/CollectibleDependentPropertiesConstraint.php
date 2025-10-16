<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\StatusUtils;
use stdClass;

class CollectibleDependentPropertiesConstraint extends DataConstraint {
    private const MESSAGE = 'datamap-validate-constraint-notcollectible';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        if ( !isset( $data->groups ) ) {
            return true;
        }

        if ( isset( $data->groups ) ) {
            foreach ( (array)$data->groups as $groupId => $group ) {
                $isCollectible = ( $group->isCollectible ?? false ) !== false;
                if ( $isCollectible ) {
                    continue;
                }

                $fields = array_intersect( array_keys( (array)$group ), [
                    'autoNumberInChecklist',
                ] );
                if ( count( $fields ) > 0 ) {
                    $this->emitWarning( self::MESSAGE, "/groups/$groupId", StatusUtils::formatArray( $fields ) );
                }
            }
        }

        return true;
    }
}
