<?php

namespace MediaWiki\Extension\DataMaps\Output;

use Error;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use stdClass;

class MapRenderer {
    public function __construct(
        private readonly PageReference $page
    ) { }

    public function getHtmlForContent( ParserOutput $parserOutput, MapContent $content ): string {
        $dataResult = $content->getData();
        if ( !$dataResult->isOK() ) {
            throw new Error( 'MapMetadataEmitter received a Content object with broken data.' );
        }

        return $this->getHtml( $parserOutput, $dataResult->getValue() );
    }

    public function getHtml( ParserOutput $parserOutput, stdClass $object ): string {
        return '';
    }
}
