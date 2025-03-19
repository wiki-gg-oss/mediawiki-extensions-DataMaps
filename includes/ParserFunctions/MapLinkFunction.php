<?php
namespace MediaWiki\Extension\DataMaps\ParserFunctions;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;

final class MapLinkFunction {
    /**
     * Renders a link to a page with a map.
     *
     * {{#MapLink:Location maps#Arbury-0|marker=100}}
     *
     * @param Parser $parser
     * @param PPFrame $frame
     * @param PPNode[] $args
     * @return string
     */
    public static function run( Parser $parser, PPFrame $frame, array $args ): array {
        $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

        $expandedArgs = CommonUtilities::getArguments( $frame, $args, [
            'marker' => null
        ] );

        $target = Title::newFromText( $expandedArgs[0] );

        return [
            $linkRenderer->makeLink(
                $target,
                $expandedArgs[1] ?? null,
                [],
                [
                    'marker' => $expandedArgs['marker']
                ]
            ),
            'noparse' => true,
            'isHTML' => true
        ];
    }
}
