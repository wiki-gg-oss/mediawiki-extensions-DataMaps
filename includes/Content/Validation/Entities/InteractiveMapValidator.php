<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use stdClass;

class InteractiveMapValidator extends EntityValidator {
    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): bool {
        $this->expectProperties( $data, [
            'version' => [ 'is_int' ],
            'settings' => [ 'is_object', EntityValidator::NULLABLE => true,
                EntityValidator::CHECK_CLASS => SettingsValidator::class ],
            'markerTypes' => [ 'is_array', EntityValidator::ITEM_SPEC => [ 'is_object',
                EntityValidator::CHECK_CLASS => PolymorphicMarkerTypeValidator::class ] ],
            'features' => [ 'is_array' ],
        ] );

        return true;
    }
}
