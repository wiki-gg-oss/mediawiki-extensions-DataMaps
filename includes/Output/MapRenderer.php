<?php

namespace MediaWiki\Extension\DataMaps\Output;

use Error;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapInitFlag;
use MediaWiki\Extension\DataMaps\Content\MapInitMetadataVersion;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use stdClass;

class MapRenderer {
    public function __construct(
        private readonly Parser $parser,
        private readonly PageIdentity $page
    ) { }

    public function getHtmlForContent( ParserOutput $parserOutput, MapContent $content ): string {
        $dataResult = $content->getData();
        if ( !$dataResult->isOK() ) {
            throw new Error( 'MapMetadataEmitter received a Content object with broken data.' );
        }

        return $this->getHtml( $parserOutput, $dataResult->getValue() );
    }

    public function getHtml( ParserOutput $parserOutput, stdClass $object ): string {
        return Html::rawElement( 'div', [
                'class' => 'ext-navigator-map',
                'data-mw-navigator' => FormatJson::encode( $this->getInitMetadataArray() ),
            ],
            Html::element( 'noscript', [
                    'class' => 'ext-navigator-statusmsg ext-navigator-statusmsg--error',
                ],
                $this->parser->msg( 'datamap-javascript-required' )->plain() ) .
            Html::element( 'div', [
                    'class' => 'ext-navigator-statusmsg',
                ],
                $this->parser->msg( 'datamap-loading-js' ) )
        );
    }

    private function getInitMetadataArray(): array {
        $title = Title::castFromPageIdentity( $this->page );
        $initFlags = MapInitFlag::None->value;
        return [
            '$version' => MapInitMetadataVersion::Latest->value,
            'pageId' => $title->getId(),
            'revId' => $title->getLatestRevID(),
            'flags' => $initFlags,
        ];
    }
}
