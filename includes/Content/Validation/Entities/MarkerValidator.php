<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use stdClass;

class MarkerValidator extends EntityValidator {
    public const OBJECT_SPEC = [ 'is_object', EntityValidator::CHECK_CLASS => MarkerValidator::class ];

    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): bool {
        // TODO: move somewhere sensible
        static $specVec2 = [ 'is_array', EntityValidator::MIN_ITEMS => 2, EntityValidator::MAX_ITEMS => 2,
            EntityValidator::ITEM_SPEC => [ 'is_numeric' ] ];

        $this->expectProperties( $data, [
            'at' => $specVec2,
            // TODO: check if not wikitext
            'title' => [ 'is_string', EntityValidator::NULLABLE => true ],
            // TODO: wikitext
            'description' => [ 'is_string', EntityValidator::NULLABLE => true ],
            // TODO: check if valid title + warn if file missing
            'image' => [ 'is_string', EntityValidator::NULLABLE => true ],
            // TODO: check if valid title
            'link' => [ 'is_string', EntityValidator::NULLABLE => true ],
        ] );

        return true;
    }
}
