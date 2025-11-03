<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use stdClass;

class SettingsValidator extends EntityValidator {
    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): bool {
        $this->expectProperties( $data, [
            'displayCoordinates' => [ 'is_bool', EntityValidator::NULLABLE => true ],
        ] );

        return true;
    }
}
