<?php

namespace MediaWiki\Extension\DataMaps\Api\WikitextParser;

interface IWikitextParser {
    public function parse( string|array $value, bool $stripOuterParagraph = false ): string;
}
