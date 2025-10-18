<?php

namespace MediaWiki\Extension\DataMaps\Output;

use Error;
use MediaWiki\Extension\DataMaps\Content\MapContent;
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

    public function getHtmlForContent(
        ParserOutput $parserOutput,
        MapRenderOptions $opts,
        MapContent $content
    ): string {
        $dataResult = $content->getData();
        if ( !$dataResult->isOK() ) {
            throw new Error( 'MapMetadataEmitter received a Content object with broken data.' );
        }

        return $this->getHtml( $parserOutput, $opts, $dataResult->getValue() );
    }

    public function getHtml(
        ParserOutput $parserOutput,
        MapRenderOptions $opts,
        stdClass $object
    ): string {
        // Register required modules
        $parserOutput->addModuleStyles( [
            'ext.navigator.map.styles',
        ] );
        if ( $opts->isLazyLoadingAllowed() ) {
            $parserOutput->addModules( [
                'ext.navigator.map.lazyload',
            ] );
        } else {
            $parserOutput->addModules( [
                'ext.navigator.map.app',
            ] );
        }

        // Initialisation placeholder
        if ( $opts->isLazyLoadingAllowed() ) {
            $initPlHtml =
                Html::rawElement( 'div', [
                        'class' => 'ext-navigator-statusmsg',
                    ],
                    $this->parser->msg( 'navigator-loading-lazy' )->escaped()
                    . Html::rawElement( 'button', [
                            'class' => 'cdx-button cdx-button--action-progressive cdx-button--weight-primary',
                            'disabled' => 'true',
                        ],
                        $this->parser->msg( 'navigator-loading-lazy-btn' )->escaped() )
                );
        } else {
            $initPlHtml =
                Html::element( 'div', [
                        'class' => 'ext-navigator-statusmsg',
                    ],
                    $this->parser->msg( 'navigator-loading-eager' )->plain() );
        }

        // Main outer container and the no-JS message
        return Html::rawElement( 'div', [
                'class' => 'ext-navigator-map',
                'data-mw-navigator' => FormatJson::encode( $this->getInitMetadataArray( $opts ) ),
            ],
            Html::element( 'noscript', [
                    'class' => 'ext-navigator-statusmsg ext-navigator-statusmsg--error',
                ],
                $this->parser->msg( 'datamap-javascript-required' )->plain() ) .
            $initPlHtml
        );
    }

    private function getInitMetadataArray( MapRenderOptions $opts ): array {
        $title = Title::castFromPageIdentity( $this->page );
        return [
            '$version' => MapInitMetadataVersion::Latest->value,
            'pageId' => $title->getId(),
            'revId' => $title->getLatestRevID(),
            'flags' => $opts->toInitFlags(),
        ];
    }
}
