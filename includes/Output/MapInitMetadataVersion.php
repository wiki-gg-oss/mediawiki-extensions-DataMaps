<?php
namespace MediaWiki\Extension\DataMaps\Output;

/**
 * Metadata versions for maps embedded in articles, which could be cached in the ParserCache and therefore stale to us.
 */
enum MapInitMetadataVersion: int {
    case REV_1 = 1;

    public const Latest = self::REV_1;
}
