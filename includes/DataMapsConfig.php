<?php
namespace MediaWiki\Extension\Ark\DataMaps;

class DataMapsConfig {
    public static function getParserExpansionLimit(): int {
        global $wgDataMapsMarkerParserExpansionLimit;
        return $wgDataMapsMarkerParserExpansionLimit;
    }

    public static function getNamespace(): int {
        global $wgDataMapsNamespaceId;
        return $wgDataMapsNamespaceId;
    }

    public static function getApiCacheType() {
        global $wgDataMapsCacheType;
        return $wgDataMapsCacheType;
    }

    public static function getApiCacheTTL(): int {
        global $wgDataMapsCacheTTL;
        return $wgDataMapsCacheTTL;
    }

    public static function shouldExtendApiCacheTTL(): bool {
        global $wgDataMapsExtendCacheTTL;
        return $wgDataMapsExtendCacheTTL != false;
    }

    public static function getApiCacheTTLExtensionThreshold(): int {
        global $wgDataMapsExtendCacheTTL;
        return $wgDataMapsExtendCacheTTL['threshold'];
    }

    public static function getApiCacheTTLExtensionValue(): int {
        global $wgDataMapsExtendCacheTTL;
        return $wgDataMapsExtendCacheTTL['override'];
    }

    public static function shouldApiReturnProcessingTime(): bool {
        global $wgDataMapsReportTimingInfo;
        return $wgDataMapsReportTimingInfo;
    }

    public static function shouldShowCoordinates(): bool {
        global $wgDataMapsShowCoordinatesDefault;
        return $wgDataMapsShowCoordinatesDefault;
    }

    public static function shouldCacheWikitextInProcess(): bool {
        global $wgDataMapsUseInProcessParserCache;
        return $wgDataMapsUseInProcessParserCache;
    }

    public static function isBleedingEdge(): bool {
        global $wgDataMapsAllowExperimentalFeatures;
        return $wgDataMapsAllowExperimentalFeatures;
    }
}