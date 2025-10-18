<?php

namespace MediaWiki\Extension\DataMaps\Output;

use Error;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use stdClass;

class MapMetadataEmitter implements MetadataEmitter {
    public function __construct(
        private readonly Parser $parser,
        private readonly PageIdentity $page
    ) { }

    public function runForContent( ParserOutput $parserOutput, MapContent $content ): void {
        $dataResult = $content->getData();
        if ( !$dataResult->isOK() ) {
            throw new Error( 'MapMetadataEmitter received a Content object with broken data.' );
        }

        $this->run( $parserOutput, $dataResult->getValue() );
    }

    public function run( ParserOutput $parserOutput, stdClass $object ): void {
        
    }
}
