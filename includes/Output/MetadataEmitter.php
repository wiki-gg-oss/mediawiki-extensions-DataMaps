<?php

namespace MediaWiki\Extension\DataMaps\Output;

use MediaWiki\Parser\ParserOutput;
use stdClass;

interface MetadataEmitter {
    public function run( ParserOutput $parserOutput, stdClass $object ): void;
}
