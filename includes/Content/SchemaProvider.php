<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\MainConfigNames;
use MediaWiki\Utils\UrlUtils;

class SchemaProvider {
    public const SERVICE_NAME = 'DataMaps.SchemaProvider';

    public const MAX_VALIDATION_ERROR_COUNT = 40;

    /**
     * @internal Use only in ServiceWiring
     */
    public const CONSTRUCTOR_OPTIONS = [
        MainConfigNames::ExtensionAssetsPath,
        MainConfigNames::CanonicalServer,
    ];

    public function __construct(
        private readonly ServiceOptions $options,
        private readonly UrlUtils $urlUtils,
        private readonly ExtensionConfig $extConfig
    ) {
        $this->options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
    }

    /**
     * Returns whether the revision is supported.
     *
     * @param string $id A SchemaRevision constant.
     * @return bool
     */
    public function isRevisionSupported( string $id ): bool {
        return in_array( $id, SchemaRevision::SUPPORTED_REVISIONS );
    }

    /**
     * Returns the extension version the revision is planned to be dropped in, if any.
     *
     * @param string $id A SchemaRevision constant.
     * @return ?string
     */
    public function getRevisionDeprecationTarget( string $id ): ?string {
        return SchemaRevision::DEPRECATED_REVISIONS[ $id ] ?? null;
    }

    /**
     * Returns base path to the local directory containing schemas.
     *
     * @return string
     */
    private function getLocalStorePath(): string {
        return __DIR__ . '/../../schemas/';
    }

    /**
     * Returns base path to the directory containing schemas for URLs.
     *
     * @return string
     */
    private function getBaseExternalPath(): string {
        $publicPath = $this->extConfig->getPublicSchemaPath();
        return "$publicPath/schemas/";
    }

    /**
     * Returns a URL to a JSON schema revision.
     *
     * @param string $id A SchemaRevision constant.
     * @param bool $pathOnly Whether the URL should only consist of a path.
     * @return string
     */
    public function makePublicUrl( string $id, bool $pathOnly = false ): string {
        return $this->urlUtils->expand(
            $this->getBaseExternalPath() . "$id.json",
            $pathOnly ? PROTO_INTERNAL : PROTO_CANONICAL
        );
    }

    /**
     * Returns a URL to the recommended JSON schema revision.
     *
     * @param bool $pathOnly Whether the URL should only consist of a path.
     * @return string
     */
    public function makePublicRecommendedUrl( bool $pathOnly = false ): string {
        return $this->makePublicUrl( SchemaRevision::RECOMMENDED_REVISION, $pathOnly );
    }

    /**
     * Dissects the URL and extracts a schema revision from it.
     *
     * @param string $url
     * @return ?string
     */
    public function getRevisionFromUrl( string $url ): ?string {
        // TODO: there is surely a better way to do this
        $prefixTable = [
            'raw.githubusercontent.com' => '/wiki-gg-oss/mediawiki-extensions-DataMaps/main/schemas/',
            $this->options->get( MainConfigNames::CanonicalServer ) => $this->getBaseExternalPath(),
        ];

        if ( !str_ends_with( $url, '.json' ) ) {
            return null;
        }

        $url = $this->urlUtils->expand( $url, PROTO_CANONICAL );
        $parsed = $this->urlUtils->parse( $url );
        if ( $parsed === null || !isset( $parsed['host'] ) || !isset( $parsed['path'] )
            || !array_key_exists( $parsed['host'], $prefixTable ) ) {
            return null;
        }

        $prefix = $prefixTable[$parsed['host']];
        if ( !str_starts_with( $parsed['path'], $prefix ) ) {
            return null;
        }

        return substr( $parsed['path'], strlen( $prefix ), -5 );
    }

    public function makeValidator(): MapContentValidator {
        // TODO: convert to "supported sources"
        return new MapContentValidator(
            $this,
            $this->getLocalStorePath(),
            $this->getBaseExternalPath(),
            [
                'https://raw.githubusercontent.com/wiki-gg-oss/mediawiki-extensions-DataMaps/main/schemas/',
                $this->urlUtils->expand( $this->getBaseExternalPath(), PROTO_INTERNAL ),
                $this->urlUtils->expand( $this->getBaseExternalPath(), PROTO_CANONICAL ),
            ]
        );
    }
}
