<?php
namespace MediaWiki\Extension\DataMaps\Migration\Fandom;

use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Extension\DataMaps\Migration\ForeignMapContentHandler;

class FandomMapContentHandler extends JsonContentHandler {
    use ForeignMapContentHandler;

    public function __construct( $modelId = CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT ) {
        parent::__construct( $modelId, [ CONTENT_MODEL_DATAMAPS_FANDOM_COMPAT ] );
    }

    protected function getContentClass() {
        return FandomMapContent::class;
    }
}
