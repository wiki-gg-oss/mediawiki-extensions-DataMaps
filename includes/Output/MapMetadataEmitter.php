<?php

namespace MediaWiki\Extension\DataMaps\Output;

use Error;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use stdClass;

class MapMetadataEmitter implements MetadataEmitter {
    public function __construct(
        private readonly PageReference $page
    ) { }

    public function runForContent( Parser $parser, ParserOutput $parserOutput, MapContent $content ): void {
        $dataResult = $content->getData();
        if ( !$dataResult->isOK() ) {
            throw new Error( 'MapMetadataEmitter received a Content object with broken data.' );
        }

        $this->run( $parser, $parserOutput, $dataResult->getValue() );
    }

    public function run( Parser $parser, ParserOutput $parserOutput, stdClass $object ): void {
        
    }
}
