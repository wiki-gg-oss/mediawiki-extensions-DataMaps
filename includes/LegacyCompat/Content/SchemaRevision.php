<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content;

// Sub-revision constants should be indented below the latest constant.
// phpcs:disable Generic.WhiteSpace.ScopeIndent

final class SchemaRevision {
    public const REV_17 = 'v17';
        public const REV_17_0 = 'v17';
        public const REV_17_1 = 'v17.1';
        public const REV_17_2 = 'v17.2';
        public const REV_17_3 = 'v17.3';
        public const REV_17_4 = 'v17.4';

    public const SUPPORTED_REVISIONS = [
        self::REV_17_0,
        self::REV_17_1,
        self::REV_17_2,
        self::REV_17_3,
        self::REV_17_4,
    ];
    public const DEPRECATED_REVISIONS = [
        // schema revision => extension version to be removed in
    ];
    public const RECOMMENDED_REVISION = self::REV_17_4;
}
