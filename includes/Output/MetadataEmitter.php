<?php

namespace MediaWiki\Extension\DataMaps\Output;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use stdClass;

interface MetadataEmitter {
    public function run( Parser $parser, ParserOutput $parserOutput, stdClass $object ): void;
}
