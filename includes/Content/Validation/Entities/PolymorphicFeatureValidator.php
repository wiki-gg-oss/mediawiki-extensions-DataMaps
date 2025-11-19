<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use stdClass;

class PolymorphicFeatureValidator extends EntityValidator {
    public const ARRAY_SPEC = [ 'is_array', EntityValidator::NULLABLE => true,
        EntityValidator::ITEM_SPEC => [ 'is_object',
            EntityValidator::CHECK_CLASS => PolymorphicFeatureValidator::class ] ];

    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): bool {
        $type = $data->type ?? null;
        switch ( $type ) {
            case 'FeatureCollection':
                $this->expectProperties( $data, [
                    'attachFeatures' => self::ARRAY_SPEC,
                ] );
                break;

            case 'BackgroundImage':
                $this->expectProperties( $data, [
                    'image' => [ 'is_string' ],
                    // TODO: dimensions may be a vec2 too
                    'dimensions' => [ 'is_string' ],
                    'attachFeatures' => self::ARRAY_SPEC,
                ] );
                break;
            
            case 'Text':
                $this->expectProperties( $data, [
                    'content' => [ 'is_string' ],
                ] );
                break;

            case 'MarkerCollection':
                $this->expectProperties( $data, [
                    'attachType' => [ 'is_string' ],
                    // TODO: must expect only markers here
                    // 'attachFeatures' => self::ARRAY_SPEC,
                ] );
                break;
            
            default:
                $this->status->fatal( 'navigator-validate-unexpected-type', $this->trace->toString() );
                return false;
        }

        return true;
    }
}
