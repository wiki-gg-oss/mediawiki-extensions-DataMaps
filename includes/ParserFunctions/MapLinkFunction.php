<?php
namespace MediaWiki\Extension\DataMaps\ParserFunctions;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;

final class MapLinkFunction extends ParserFunction {
    public function __construct(
        private readonly LinkRenderer $linkRenderer
    ) { }

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
    public function run( Parser $parser, PPFrame $frame, array $args ): array {
        $expandedArgs = $this->getArguments( $frame, $args, [
            'marker' => null
        ] );

        $target = Title::newFromText( $expandedArgs[0] );

        return [
            $this->linkRenderer->makeLink(
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
