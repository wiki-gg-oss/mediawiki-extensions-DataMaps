<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use stdClass;

class PolymorphicMarkerTypeValidator extends EntityValidator {
    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): bool {
        if ( isset( $data->include ) ) {
            $this->expectProperties( $data, [
                'group' => [ 'is_string' ],
                'description' => [ 'is_string', EntityValidator::NULLABLE => true ],
                'include' => [ 'is_array', EntityValidator::ITEM_SPEC => [ 'is_object',
                    EntityValidator::CHECK_CLASS => PolymorphicMarkerTypeValidator::class ] ],
            ] );
        } else {
            $this->expectProperties( $data, [
                'id' => [ 'is_string' ],
                'name' => [ 'is_string' ],
                'description' => [ 'is_string', EntityValidator::NULLABLE => true ],
            ] );
        }

        return true;
    }
}
