<?php
namespace MediaWiki\Extension\DataMaps;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;

class ExtensionConfig {
    public const SERVICE_NAME = 'DataMaps.Config';

    /**
     * @internal Use only in ServiceWiring
     */
    public const CONSTRUCTOR_OPTIONS = [
        // MW
        MainConfigNames::ExtensionAssetsPath,
        // DataMaps
        ConfigNames::NamespaceId,
        ConfigNames::ApiCacheSettings,
        ConfigNames::ReportTimingInfo,
        ConfigNames::DefaultApiMarkerBatchSize,
        ConfigNames::MaxApiMarkerBatchSize,
        ConfigNames::ParserExpansionLimit,
        ConfigNames::UseInProcessParserCache,
        ConfigNames::LinksUpdateBudget,
        ConfigNames::PublicSchemaPath,
        ConfigNames::EnableMapLazyLoading,
        ConfigNames::EnableTransclusionAlias,
        ConfigNames::EnableVisualEditor,
        ConfigNames::EnableCreateMap,
        ConfigNames::EnablePortingTools,
        ConfigNames::EnableExperimentalFeatures,
    ];

    public function __construct(
        private readonly ServiceOptions $options
    ) {
        $this->options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
    }

    public function getParserExpansionLimit(): int {
        return $this->options->get( ConfigNames::ParserExpansionLimit );
    }

    public function isNamespaceManaged(): bool {
        return $this->options->get( ConfigNames::NamespaceId ) === 'managed';
    }

    public function getNamespaceId(): int {
        if ( $this->isNamespaceManaged() ) {
            return NS_MAP;
        }
        return $this->options->get( ConfigNames::NamespaceId );
    }

    public function getApiCacheSettings() {
        return $this->options->get( ConfigNames::ApiCacheSettings );
    }

    public function getApiCacheType() {
        return $this->getApiCacheSettings()['type'];
    }

    public function getApiCacheTTL(): int {
        return $this->getApiCacheSettings()['ttl'];
    }

    public function shouldExtendApiCacheTTL(): bool {
        $settings = $this->getApiCacheSettings();
        return $settings['ttlExtensionThreshold'] === false || $settings['ttlExtensionValue'] === false;
    }

    public function getApiCacheTTLExtensionThreshold(): int {
        return $this->getApiCacheSettings()['ttlExtensionThreshold'];
    }

    public function getApiCacheTTLExtensionValue(): int {
        return $this->getApiCacheSettings()['ttlExtensionValue'];
    }

    public function shouldApiReturnProcessingTime(): bool {
        return $this->options->get( ConfigNames::ReportTimingInfo );
    }

    public function getApiDefaultMarkerLimit(): int {
        return $this->options->get( ConfigNames::DefaultApiMarkerBatchSize );
    }

    public function getApiMaxMarkerLimit(): int {
        return $this->options->get( ConfigNames::MaxApiMarkerBatchSize );
    }

    public function shouldCacheWikitextInProcess(): bool {
        return $this->options->get( ConfigNames::UseInProcessParserCache );
    }

    public function shouldLinksUpdatesUseMarkers() {
        return $this->getLinksUpdateBudget() > 0;
    }

    public function getLinksUpdateBudget() {
        return $this->options->get( ConfigNames::LinksUpdateBudget );
    }

    public function getPublicSchemaPath(): string {
        $retval = $this->options->get( ConfigNames::PublicSchemaPath );
        if ( !$retval ) {
            $wgExtensionAssetsPath = $this->options->get( MainConfigNames::ExtensionAssetsPath );
            $retval = "$wgExtensionAssetsPath/DataMaps";
        }
        return $retval;
    }

    public function isTransclusionAliasEnabled(): bool {
        return $this->options->get( ConfigNames::EnableTransclusionAlias );
    }

    public function isVisualEditorEnabled(): bool {
        return $this->hasExperimentalFeatures() && $this->options->get( ConfigNames::EnableVisualEditor );
    }

    public function isMapLazyLoadingEnabled(): bool {
        return $this->options->get( ConfigNames::EnableMapLazyLoading );
    }

    public function isCreateMapEnabled(): bool {
        return $this->options->get( ConfigNames::EnableCreateMap );
    }

    public function areFandomPortingToolsEnabled(): bool {
        return $this->hasExperimentalFeatures() && $this->options->get( ConfigNames::EnablePortingTools );
    }

    public function hasExperimentalFeatures(): bool {
        return $this->options->get( ConfigNames::EnableExperimentalFeatures );
    }
}
