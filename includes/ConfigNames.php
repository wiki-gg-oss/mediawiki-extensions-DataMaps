<?php
namespace MediaWiki\Extension\DataMaps;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

/**
 * A class containing constants representing the names of DataMaps' configuration variables. These constants can be used
 * with ServiceOptions or via ExtensionConfig to protect against typos.
 *
 * @since 0.16.7
 */
class ConfigNames {
    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const NamespaceId = 'DataMapsNamespaceId';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const ApiCacheSettings = 'DataMapsApiCaching';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const ReportTimingInfo = 'DataMapsReportTimingInfo';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const DefaultApiMarkerBatchSize = 'DataMapsDefaultApiMarkerBatch';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const MaxApiMarkerBatchSize = 'DataMapsMaxApiMarkerBatch';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const ParserExpansionLimit = 'DataMapsMarkerParserExpansionLimit';

    /**
     * Name constant. For use in ExtensionConfig.
     *
     * TODO: in v0.17, rename to DataMapsParserCacheInProcess
     */
    public const UseInProcessParserCache = 'DataMapsUseInProcessParserCache';

    /**
     * Name constant. For use in ExtensionConfig.
     *
     * TODO: in v0.17, rename to DataMapsLinksUpdateBudget
     */
    public const LinksUpdateBudget = 'DataMapsFullLinksUpdateBudget';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const PublicSchemaPath = 'DataMapsPublicSchemaPath';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableTransclusionAlias = 'DataMapsEnableTransclusionAlias';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableVisualEditor = 'DataMapsEnableVisualEditor';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableCreateMap = 'DataMapsEnableCreateMap';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableMapLazyLoading = 'DataMapsEnableMapLazyLoading';

    /**
     * Name constant. For use in ExtensionConfig.
     *
     * @unstable to be finalised in GH#98.
     */
    public const EnablePortingTools = 'DataMapsEnableFandomPortingTools';

    /**
     * Name constant. For use in ExtensionConfig.
     *
     * TODO: in v0.17, rename to DataMapsExperimentalFeatures
     */
    public const EnableExperimentalFeatures = 'DataMapsAllowExperimentalFeatures';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableLoadMapButton = 'DataMapsEnableLoadMapButton';

    /**
     * Name constant. For use in ExtensionConfig.
     */
    public const EnableNavigatorRefactor = 'DataMapsEnableNavigatorRefactor';
}
