<?php
namespace MediaWiki\Extension\DataMaps\Content;

enum MapInitMetadataVersion: int {
    case REV_1 = 1;

    public const Latest = self::REV_1;
}
