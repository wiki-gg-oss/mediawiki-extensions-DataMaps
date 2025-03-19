<?php
namespace MediaWiki\Extension\DataMaps\Rendering\Utils;

class DataMapColourUtils {
    public static function decode( /*string|array*/ $input ): ?array {
        if (
            is_array( $input ) && count( $input ) == 3 && is_numeric( $input[0] ) && is_numeric( $input[1] )
            && is_numeric( $input[2] )
        ) {
            return $input;
        } elseif ( is_string( $input ) ) {
            $input = ltrim( $input, '#' );
            $len = strlen( $input );
            if ( $len === 3 ) {
                $input = str_split( $input, 1 );
            } elseif ( $len === 6 ) {
                $input = str_split( $input, 2 );
            } else {
                return null;
            }

            [ $r, $g, $b ] = array_map( fn ( $c ) => hexdec( str_pad( $c, 2, $c ) ), $input );
            return [ $r, $g, $b ];
        }
        return null;
    }

    public static function decode4( /*string|array*/ $input ): ?array {
        if ( is_array( $input ) && count( $input ) == 4
            && is_numeric( $input[0] ) && is_numeric( $input[1] ) && is_numeric( $input[2] ) && is_numeric( $input[3] ) ) {
            $input[3] *= 255;
            return $input;
        } elseif ( is_string( $input ) ) {
            $input = ltrim( $input, '#' );
            $len = strlen( $input );
            if ( $len === 4 ) {
                $input = str_split( $input, 1 );
            } elseif ( $len === 8 ) {
                $input = str_split( $input, 2 );
            } else {
                return self::decode( $input );
            }

            [ $r, $g, $b, $a ] = array_map( fn ( $c ) => hexdec( str_pad( $c, 2, $c ) ), $input );
            return [ $r, $g, $b, $a ];
        }
        return self::decode( $input );
    }

    public static function asHex( array $input ): string {
        if ( count( $input ) === 4 ) {
            return sprintf( '#%02x%02x%02x%02x', $input[0], $input[1], $input[2], $input[3] );
        }
        return sprintf( '#%02x%02x%02x', $input[0], $input[1], $input[2] );
    }
}
