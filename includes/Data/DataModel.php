<?php
namespace MediaWiki\Extension\DataMaps\Data;

use stdClass;

class DataModel {
    protected static string $publicName = '???';

    protected stdClass $raw;
    public function __construct( stdClass $raw ) {
        if ( is_array( $raw ) ) {
            $raw = (object)$raw;
        }
        $this->raw = $raw;
    }

    public function unwrap(): stdClass {
        return $this->raw;
    }
}
