<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\StatusUtils;
use stdClass;

class BackgroundLayerExistsConstraint extends DataConstraint {
    private const MESSAGE = 'datamap-validate-constraint-bglayerexists';

    public function getDependencies(): array {
        return [];
    }

    public function run( MapVersionInfo $version, stdClass $data ): bool {
        if ( !isset( $data->markers ) ) {
            return true;
        }

        $validLayers = [];
        if ( isset( $data->background ) && is_string( $data->background ) ) {
            $validLayers[] = 'bg:0';
        } elseif ( isset( $data->background ) ) {
            $validLayers[] = 'bg:' . ( $data->associatedLayer ?? '0' );
        } elseif ( isset( $data->backgrounds ) ) {
            foreach ( $data->backgrounds as $index => $background ) {
                $validLayers[] = 'bg:' . ( $background->associatedLayer ?? $index );
            }
        }

        $result = true;

        if ( isset( $data->markers ) ) {
            foreach ( array_keys( (array)$data->markers ) as $assocStr ) {
                $badAssocLayers = array_filter(
                    array_filter(
                        explode( ' ', $assocStr ),
                        fn ( $item ) => str_starts_with( $item, 'bg:' )
                    ),
                    fn ( $item ) => !in_array( $item, $validLayers )
                );

                if ( count( $badAssocLayers ) > 0 ) {
                    $formatted = StatusUtils::formatArray( $badAssocLayers );
                    $this->emitErrorPermissive( self::MESSAGE, "/markers/$assocStr", $formatted );
                    $result = false;
                }
            }
        }

        return $result;
    }
}
