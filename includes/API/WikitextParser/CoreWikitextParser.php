<?php

namespace MediaWiki\Extension\DataMaps\Api\WikitextParser;

use MediaWiki\Page\PageReference;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Parser\ParserOptions;

class CoreWikitextParser implements IWikitextParser {
    private readonly Parser $parser;
    private readonly ParserOptions $options;

    public function __construct(
        private readonly PageReference $page,
        private readonly int $revId,
        ParserFactory $parserFactory
    ) {
        $this->parser = $parserFactory->create();
        $this->options = ParserOptions::newFromAnon();
    }

    public function parse( string|array $value, bool $stripOuterParagraph = false ): string {
        if ( is_array( $value ) ) {
            $value = implode( "\n", $value );
        }

        $value = trim( $value );

        // TODO: ParserCache

        $text = $this->parser->parse( $value, $this->page, $this->options, true, true, $this->revId )
            // Well, this should be invoking the output transform pipeline... which is expensive as hell
            ->getContentHolderText();
        if ( $stripOuterParagraph ) {
            $text = Parser::stripOuterParagraph( $text );
        }
        return $text;
    }
}
