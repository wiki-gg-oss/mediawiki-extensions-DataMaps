<?php
namespace MediaWiki\Extension\DataMaps\Migration;

use MediaWiki\Content\JsonContent;
use MediaWiki\Status\Status;

interface ForeignMapConverter {
    public function validate( JsonContent $content ): Status;
    public function convert( JsonContent $content ): object;
}
