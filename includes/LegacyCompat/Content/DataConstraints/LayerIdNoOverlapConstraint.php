<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\StatusUtils;
use stdClass;

class LayerIdNoOverlapConstraint extends DataConstraint {
    private const DECLARATION_OVERLAP_MESSAGE = 'datamap-validate-constraint-layerdecloverlap';
    private const MARKER_OVERLAP_MESSAGE = 'datamap-validate-constraint-assocgroupoverlap';
    private const MARKER_NONUNIQUE_MESSAGE = 'datamap-validate-constraint-assocnonuniqoverlap';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        $result = true;

        if ( isset( $data->groups ) && isset( $data->categories ) ) {
            $overlap = array_intersect(
                array_keys( (array)$data->groups ),
                array_keys( (array)$data->categories )
            );
            foreach ( $overlap as $badLayer ) {
                $this->emitError( self::DECLARATION_OVERLAP_MESSAGE, "/groups/$badLayer", "/categories/$badLayer" );
                $result = false;
            }
        }

        if ( isset( $data->markers ) ) {
            $groupIds = array_keys( (array)( $data->groups ?? new stdClass() ) );

            foreach ( array_keys( (array)$data->markers ) as $assocStr ) {
                $assocLayers = explode( ' ', $assocStr );

                if ( count( array_unique( $assocLayers ) ) !== count( $assocLayers ) ) {
                    $this->emitError( self::MARKER_NONUNIQUE_MESSAGE, "/markers/$assocStr" );
                    $result = false;
                }

                $assocGroups = array_filter( $assocLayers, fn ( $item ) => in_array( $item, $groupIds ) );
                if ( count( $assocGroups ) > 1 ) {
                    $formatted = StatusUtils::formatArray( $assocGroups );
                    $this->emitError( self::MARKER_OVERLAP_MESSAGE, "/markers/$assocStr", $formatted );
                    $result = false;
                }
            }
        }

        return $result;
    }
}
