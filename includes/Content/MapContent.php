<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Content\JsonContent;
use MediaWiki\Json\FormatJson;
use MediaWiki\Status\Status;

class MapContent extends JsonContent {
    public function __construct( $text, $modelId = CONTENT_MODEL_NAVIGATOR_MAP ) {
        parent::__construct( $text, $modelId );
    }

    /**
     * Decodes the JSON string.
     *
     * @return Status
     */
    public function getData() {
        // TODO: try to preserve comments
		$this->jsonParse ??= FormatJson::parse( $this->getText(), FormatJson::TRY_FIXING );
		return $this->jsonParse;
    }

    public function beautifyJSON() {
        return MapJsonFormatter::serialiseObject( $this->getData()->getValue() );
    }
}
