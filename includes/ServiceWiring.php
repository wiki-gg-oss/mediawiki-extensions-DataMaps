<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\DataMaps\Content\MapContentFactory;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\SchemaProvider;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\MarkerProcessorFactory;
use MediaWiki\MediaWikiServices;

return [
    ExtensionConfig::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): ExtensionConfig {
        return new ExtensionConfig(
            new ServiceOptions(
                ExtensionConfig::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            ),
        );
    },

    SchemaProvider::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): SchemaProvider {
        return new SchemaProvider(
            new ServiceOptions(
                SchemaProvider::CONSTRUCTOR_OPTIONS,
                $services->getMainConfig()
            ),
            $services->getUrlUtils(),
            $services->getService( ExtensionConfig::SERVICE_NAME )
        );
    },

    MapContentFactory::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): MapContentFactory {
        return new MapContentFactory(
            $services->getWikiPageFactory(),
            $services->getService( ExtensionConfig::SERVICE_NAME )
        );
    },

    MarkerProcessorFactory::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): MarkerProcessorFactory {
        return new MarkerProcessorFactory(
            $services->getParserFactory(),
            $services->get( ExtensionConfig::SERVICE_NAME ),
        );
    },
];
