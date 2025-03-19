<?php
namespace MediaWiki\Extension\DataMaps\Migration;

use MediaWiki\Content\JsonContent;

class ForeignMapContent extends JsonContent {
    public function __construct( $text, $modelId = CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT ) {
        parent::__construct( $text, $modelId );
    }
}
