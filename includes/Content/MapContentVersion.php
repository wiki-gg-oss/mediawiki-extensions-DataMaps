<?php
namespace MediaWiki\Extension\DataMaps\Content;

enum MapContentVersion: int {
    case REV_18 = 18;

    public const Latest = self::REV_18;
}
