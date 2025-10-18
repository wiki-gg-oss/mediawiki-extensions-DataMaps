<?php
namespace MediaWiki\Extension\DataMaps\Output;

enum MapInitMetadataVersion: int {
    case REV_1 = 1;

    public const Latest = self::REV_1;
}
