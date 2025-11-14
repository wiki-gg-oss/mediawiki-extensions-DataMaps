<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use stdClass;

class PolymorphicMarkerTypeValidator extends EntityValidator {
    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): bool {
        $common = [
            'name' => [ 'is_string' ],
            'description' => [ 'is_string', EntityValidator::NULLABLE => true ],
            'include' => [ 'is_array', EntityValidator::NULLABLE => true,
                EntityValidator::ITEM_SPEC => [ 'is_object',
                    EntityValidator::CHECK_CLASS => PolymorphicMarkerTypeValidator::class ] ],
        ];

        if ( $this->hasAnyProperty( $data, [ 'id' ] ) ) {
            // This is a usable marker type that can be used for display or grouping
            $this->expectProperties( $data, [
                'id' => [ 'is_string' ],
                ...$common,
            ] );
        } else {
            // This is only a marker type grouping that cannot be used for markers
            $this->expectProperties( $data, [
                ...$common,
            ] );
        }

        return true;
    }
}
