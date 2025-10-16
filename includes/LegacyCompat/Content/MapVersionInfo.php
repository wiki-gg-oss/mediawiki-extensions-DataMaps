<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content;

final class MapVersionInfo {
    public function __construct(
        public readonly string $revision,
        public readonly bool $isFragment
    ) { }
}
