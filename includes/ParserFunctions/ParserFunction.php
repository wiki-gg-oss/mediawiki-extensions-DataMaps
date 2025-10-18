<?php
namespace MediaWiki\Extension\DataMaps\ParserFunctions;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

abstract class ParserFunction {
    /**
     * Executes the function.
     *
     * @param Parser $parser
     * @param PPFrame $frame
     * @param PPNode[] $args
     * @return string
     */
    abstract public function run( Parser $parser, PPFrame $frame, array $args ): array;

    /**
     * Returns a callable specification for the ParserFirstCallInit hook.
     */
    public function asCallable(): array {
        return [ $this, 'run' ];
    }

    /**
     * Wraps text as a commonly recognised wikitext error pattern.
     *
     * @param string $message
     * @param mixed ...$params
     * @return array
     */
    protected function wrapError( string $message, ...$params ): array {
        return [
            '<strong class="error">' . wfMessage( $message )->inContentLanguage()->params( $params ) . '</strong>',
            'noparse' => false,
            'isHTML' => false,
        ];
    }

    /**
     * Expands all argument nodes and parses named parameters.
     *
     * @param PPFrame $frame
     * @param PPNode[] $argNodes
     * @param ?array $defaults
     * @return array
     */
    protected function getArguments( PPFrame $frame, array $argNodes, ?array $defaults = null ): array {
        $expanded = $defaults ?? [];

        foreach ( $argNodes as $argNode ) {
            $arg = $frame->expand( $argNode );

            $pair = explode( '=', $arg, 2 );
            if ( count( $pair ) === 2 ) {
                $pair = array_map( 'trim', $pair );
                $expanded[$pair[0]] = $pair[1];
            } else {
                $expanded[] = $arg;
            }
        }

        return $expanded;
    }
}
