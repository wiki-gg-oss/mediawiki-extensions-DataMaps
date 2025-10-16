<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Json\FormatJson;
use stdClass;

class MapJsonFormatter {
    # Reduce 2-12 numbers in an array onto a single line
    private const JOIN_MULTIPLE_NUMBERS_RE = '/(\n\s+)([+-]?\d+(\.\d+)?([eE][-+]?\d+)?|true|false),(?:\n\s+(?:[+-]?\d+(\.\d+)?([eE][-+]?\d+)?|true|false|null|"[^"\n\t]*"),?){1,12}/';
    # Reduce short arrays of strings onto a single line
    private const JOIN_MULTIPLE_STRINGS_RE = '/\[((?:\n\s+".{1,30}",?\s*$){1,4})\n\s+\]/';
    # Reduces dict fields with only a single line of content (including previously joined multiple fields) to a single line
    private const COLLAPSE_SINGLE_LINE_DICT_RE = '/\{\n\s+("\w+": [^}\n\]]{1,120})\n\s+\}/';
    # Reduce arrays with only a single line of content (including previously joined multiple fields) to a single line
    private const COLLAPSE_SINGLE_LINE_ARRAY_RE = '/\[\s+(.+)\s+\]/';
    # Sets of named fields that should be combined onto a single line
    private const JOIN_LINE_FIELDS = [
        'left|right|top|bottom',
        // Backgrounds
        // Marker groups
        'fillColor|size',
        'pinColor|size',
        'icon|size',
        'borderColor|borderWidth',
        // Categories
        'name|subtleText',
        // Markers
        'id|lat|lon',
        'id|x|y',
        'id|y|x',
        'lat|lon',
        'x|y',
        'y|x',
    ];

    /**
     * Serialise an object to a formatted JSON string.
     *
     * @param array|stdClass $object
     * @return string
     */
    public static function serialiseObject( array|stdClass $object ): string {
        return self::formatJsonString( FormatJson::encode( $object, "\t", FormatJson::ALL_OK ) );
    }

    /**
     * Format a JSON string.
     *
     * @param string $text
     * @return string
     */
    public static function formatJsonString( string $text ): string {
        $retval = $text;

        foreach ( self::JOIN_LINE_FIELDS as $term ) {
            $part = '(?:("(?:' . $term . ')": [^,\n]+,?))';
            $fieldCount = substr_count( $term, '|' ) + 1;
            $full = '/' . implode( '\s+', array_fill( 0, $fieldCount, $part ) ) . '(\s+)/';
            $subs = implode( ' ', array_map( fn ( $n ) => '$' . $n, range( 1, $fieldCount ) ) ) . "$" . ( $fieldCount + 1 );
            $retval = preg_replace( $full, $subs, $retval );
        }

        $retval = preg_replace_callback( self::JOIN_MULTIPLE_NUMBERS_RE, static function ( array $matches ) {
            $txt = $matches[0];
            $txt = preg_replace( '/\s*\n\s+/', '', $txt );
            $txt = str_replace( ',', ', ', $txt );
            return $matches[1] . $txt;
        }, $retval );
        $retval = preg_replace( self::COLLAPSE_SINGLE_LINE_DICT_RE, '{ $1 }', $retval );
        $retval = preg_replace( self::COLLAPSE_SINGLE_LINE_ARRAY_RE, '[ $1 ]', $retval );

        return $retval;
    }
}
