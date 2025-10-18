<?php
namespace MediaWiki\Extension\DataMaps\Api;

use LogicException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataMapContent;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\MarkerProcessorFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use ObjectCache;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

class ApiQueryDataMapEndpoint extends ApiBase {
    // This value is a part of every cache key produced by this endpoint. It should be raised only on API output changes and
    // cache management changes, including changes to the MarkerProcessor class.
    //
    // Prior to v0.10.0, this has always been the last minor version number to require this procedure. If a bump was required in
    // a patch, it was expected that this number is increased anyway (going out of sync with versioning).
    //
    // Since v0.10.0:
    // - if major version becomes higher than zero, the first digit should be the major version;
    // - next two digits should be the minor version;
    // - next two digits should be the patch version, or two zeroes instead.
    public const GENERATION = 1711;
    // Key prefix for every cache key produced by this endpoint. Prior to v0.12.0 this was 'ARKDataMapQuery'.
    private const CACHE_NAMESPACE = 'ExtDataMap::Query';

    /** @var Title */
    private ?Title $cachedTitle = null;

    /**
     * @stable to call
     * @param ApiMain $mainModule
     * @param string $moduleName Name of this module
     * @param ExtensionConfig $config
     * @param MarkerProcessorFactory $markerProcessorFactory
     */
    public function __construct(
        ApiMain $mainModule,
        $moduleName,
        private readonly ExtensionConfig $config,
        private readonly MarkerProcessorFactory $markerProcessorFactory
    ) {
        parent::__construct( $mainModule, $moduleName );
    }

    public function getAllowedParams() {
        return [
            'pageid' => [
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_REQUIRED => true
            ],
            'revid' => [
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_REQUIRED => false,
            ],
            'layers' => [
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
                ParamValidator::PARAM_ISMULTI => true,
            ],
            'limit' => [
                ParamValidator::PARAM_TYPE => 'limit',
                ParamValidator::PARAM_DEFAULT => $this->config->getApiDefaultMarkerLimit(),
                IntegerDef::PARAM_MIN => 1,
                IntegerDef::PARAM_MAX => $this->config->getApiMaxMarkerLimit()
            ],
            'continue' => [
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_REQUIRED => false,
                ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
            ],
        ];
    }

    public function isInternal() {
        return true;
    }

    public function execute() {
        $timeStart = 0;
        if ( $this->config->shouldApiReturnProcessingTime() ) {
            $timeStart = hrtime( true );
        }

        $params = $this->extractRequestParams();

        // Configure browser-side caching recommendations
        $this->getMain()->setCacheMode( 'public' );
        $this->getMain()->setCacheMaxAge( 24 * 60 * 60 );

        $response = null;
        if ( $this->config->getApiCacheTTL() <= 0 ) {
            // Cache expiry time is zero or lower, bypass caching
            $response = $this->doProcessing( $params );
        } else {
            $response = $this->doProcessingCached( $params );
        }

        $this->doPostProcessing( $params, $response );

        $this->getResult()->addValue( null, 'query', $response );

        if ( $this->config->shouldApiReturnProcessingTime() ) {
            $this->getResult()->addValue( null, 'responseTime', hrtime( true ) - $timeStart );
        }
    }

    private function getTitleFromParams( $params ) {
        if ( $this->cachedTitle === null ) {
            $this->cachedTitle = Title::newFromID( $params['pageid'] );
            if ( !$this->cachedTitle || !$this->cachedTitle->exists() ) {
                $this->dieWithError( [ 'apierror-nosuchpageid', $params['pageid'] ] );
            }
        }
        return $this->cachedTitle;
    }

    private function getRevisionFromParams( $params ) {
        // Retrieve latest revision by title
        $title = $this->getTitleFromParams( $params );
        $revision = null;
        if ( isset( $params['revid'] ) ) {
            // Retrieve revision by ID
            $revisionStore = MediaWikiServices::getInstance()->getRevisionStore();
            $revision = $revisionStore->getRevisionById( $params['revid'] );
            if ( !$revision ) {
                $this->dieWithError( [ 'apierror-nosuchrevid', $params['revid'] ] );
            } elseif ( $revision->getPageId() != $title->getId() ) {
                $this->dieWithError( [ 'apierror-revwrongpage', $revision->getId(), $title->getPrefixedText() ] );
            }
        } else {
            $revision = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title )->getRevisionRecord();
        }

        return $revision;
    }

    public static function makeKey( Title $title, int $revid = -1 ) {
        $config = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );
        return ObjectCache::getInstance( $config->getApiCacheType() )
            ->makeKey( self::CACHE_NAMESPACE, self::GENERATION, $title->getId(), $revid );
    }

    private function doProcessingCached( $params ): array {
        $cacheExpiryTime = $this->config->getApiCacheTTL();

        // Retrieve the specified cache instance
        $cache = ObjectCache::getInstance( $this->config->getApiCacheType() );

        // Retrieve the title
        $title = $this->getTitleFromParams( $params );

        // Build the cache key from an identifier, page ID and revision ID parameter
        $revid = isset( $params['revid'] ) ? $params['revid'] : -1;
        $cacheKey = self::makeKey( $title, $revid );

        // Try to retrieve the response
        $response = $cache->get( $cacheKey );
        if ( $response === false ) {
            // Response not cached, process the data in this request
            $response = $this->doProcessing( $params );

            // If TTL extension is allowed, store an internal timestamp
            if ( $this->config->shouldExtendApiCacheTTL() ) {
                $response['refreshedAt'] = time();
            }

            // Write to cache
            $cache->set( $cacheKey, $response, $cacheExpiryTime );
        } else {
            // Response cached, check if TTL should be extended and do it
            if ( $this->config->shouldExtendApiCacheTTL() && isset( $response['refreshedAt'] ) ) {
                $ttlThreshold = $cacheExpiryTime - $this->config->getApiCacheTTLExtensionThreshold();
                if ( time() - $response['refreshedAt'] >= $ttlThreshold ) {
                    $response['refreshedAt'] = time();
                    $cache->set( $cacheKey, $response, $this->config->getApiCacheTTLExtensionValue() );
                }
            }
        }

        // Remove the internal TTL extension timestamp
        if ( isset( $response['refreshedAt'] ) && !$this->config->shouldApiReturnProcessingTime() ) {
            unset( $response['refreshedAt'] );
        }

        return $response;
    }

    private function doProcessing( $params ): array {
        $timeStart = 0;
        if ( $this->config->shouldApiReturnProcessingTime() ) {
            $timeStart = hrtime( true );
        }

        // Retrieve the content
        $title = $this->getTitleFromParams( $params );
        $revision = $this->getRevisionFromParams( $params );
        $content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC, null );

        // Make sure the page is a data map
        if ( !( $content instanceof DataMapContent ) ) {
            $this->dieWithError( [ 'contentmodel-mismatch', $content->getModel(), 'datamap' ] );
        }

        // Cast content to a data model
        $dataMap = $content->asModel();

        // Response skeleton
        $response = [
            'title' => $title->getFullText(),
            'revisionId' => $revision->getId()
        ];

        // Have a MarkerProcessor convert the data and insert it into the response
        $processor = $this->markerProcessorFactory->create( $title, $dataMap, null );
        $response['markers'] = $processor->processAll();

        if ( $this->config->shouldApiReturnProcessingTime() ) {
            $response['timing'] = [
                'processing' => hrtime( true ) - $timeStart,
            ];
        }

        return $response;
    }

    private function doPostProcessing( $params, array &$data ) {
        $timeStart = 0;
        if ( $this->config->shouldApiReturnProcessingTime() ) {
            $timeStart = hrtime( true );
        }

        // Filter markers by layers
        if ( isset( $params['layers'] ) ) {
            foreach ( array_keys( $data['markers'] ) as &$layers ) {
                if ( empty( array_intersect( $params['layers'], explode( ' ', $layers ) ) ) ) {
                    unset( $data['markers'][$layers] );
                }
            }
        }

        // Truncate markers before the index of continue
        if ( isset( $params['continue'] ) ) {
            $toSkip = $params['continue'];
            foreach ( $data['markers'] as $layers => $markers ) {
                if ( count( $markers ) <= $toSkip ) {
                    // Drop the whole set, its size is lower or same as the number we need to skip
                    $toSkip -= count( $markers );
                    unset( $data['markers'][$layers] );
                } else {
                    // Set is bigger than number we need, slice it
                    $data['markers'][$layers] = array_slice( $markers, $toSkip );
                    $toSkip = 0;
                }

                if ( $toSkip < 0 ) {
                    throw new LogicException( 'API response truncating resulted in more markers removed than needed' );
                } elseif ( $toSkip == 0 ) {
                    break;
                }
            }
        }

        // Truncate markers after the limit
        if ( isset( $params['limit'] ) ) {
            $toAllow = $params['limit'];
            $anyRemoved = false;
            foreach ( $data['markers'] as $layers => $markers ) {
                if ( $toAllow <= 0 ) {
                    // Drop this set entirely, we've reached the margin already
                    unset( $data['markers'][$layers] );
                    $anyRemoved = true;
                    continue;
                } elseif ( count( $markers ) <= $toAllow ) {
                    // Don't alter this set, it fits within the margin
                    $toAllow -= count( $markers );
                    continue;
                } else {
                    // Set is bigger than number we need, slice it
                    $data['markers'][$layers] = array_slice( $markers, 0, $toAllow );
                    $toAllow = 0;
                    $anyRemoved = true;
                }

                if ( $toAllow < 0 ) {
                    throw new LogicException( 'API response limiting resulted in more markers removed than needed' );
                }
            }

            if ( $anyRemoved ) {
                $data['continue'] = ( $params['continue'] ?? 0 ) + $params['limit'];
            }
        }

        if ( $this->config->shouldApiReturnProcessingTime() ) {
            $data['timing']['postProcessing'] = hrtime( true ) - $timeStart;
        }
    }
}
