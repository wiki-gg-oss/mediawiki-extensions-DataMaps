<?php
namespace MediaWiki\Extension\DataMaps\Content;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\Retrievers\PredefinedArray;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;

class MapContentValidator {
    private const MAX_VALIDATION_ERROR_COUNT = 40;

    private const ERROR_MESSAGE_MAP = [
        'required' => 'datamap-validate-constraint-required',
        'oneOf' => 'datamap-validate-constraint-type',
        'anyOf' => 'datamap-validate-constraint-type',
        'additionalProp' => 'datamap-validate-constraint-unexpected',
        'pattern' => 'datamap-validate-constraint-regex',
        'minLengthEmpty' => 'datamap-validate-constraint-empty',
        'minLength' => 'datamap-validate-constraint-minlength',
        'exclusiveMinimum' => 'datamap-validate-constraint-minimum',
        'exclusiveMaximum' => 'datamap-validate-constraint-maximum',
    ];
    private const UNKNOWN_ERROR_MESSAGE = 'datamap-validate-constraint-fallback';

    /** @var SchemaProvider */
    private SchemaProvider $schemaProvider;
    /** @var SchemaStorage */
    private SchemaStorage $schemaStorage;
    /** @var array */
    private array $schemaVersionMap;

    /**
     * @param SchemaProvider $schemaProvider
     * @param string $localStorePath
     * @param string $remoteRelativeStorePath
     * @param string[] $allowedSchemaHosts
     */
    public function __construct(
        SchemaProvider $schemaProvider,
        string $localStorePath,
        string $remoteRelativeStorePath,
        array $allowedSchemaHosts
    ) {
        $this->schemaProvider = $schemaProvider;

        // Bit of an ugly hack for JsonSchema library to handle relative paths
        $allowedSchemaHosts[] = "internal://provided-schema$remoteRelativeStorePath";

        // TODO: shouldn't load them all upfront, we need a custom retriever

        $schemas = [];
        $this->schemaVersionMap = [];
        foreach ( SchemaRevision::SUPPORTED_REVISIONS as $revision ) {
            $loaded = file_get_contents( "$localStorePath/$revision.json" );
            $this->schemaVersionMap["$remoteRelativeStorePath$revision.json"] = $revision;
            foreach ( $allowedSchemaHosts as $host ) {
                $schemas["$host$revision.json"] = $loaded;
                $this->schemaVersionMap["$host$revision.json"] = $revision;
            }
        }

        $uriRetriever = new UriRetriever();
        $uriRetriever->setUriRetriever( new PredefinedArray( $schemas ) );
        $this->schemaStorage = new SchemaStorage( $uriRetriever );
    }

    private function createValidator(): Validator {
        $factory = new \JsonSchema\Constraints\Factory( $this->schemaStorage );
        $factory->setConstraintClass( 'object', JsonSchemaEx\ObjectConstraintEx::class );
        $factory->setConstraintClass( 'number', JsonSchemaEx\NumberConstraintEx::class );
        $factory->setConstraintClass( 'undefined', JsonSchemaEx\UndefinedConstraintEx::class );
        return new Validator( $factory );
    }

    /**
     * Validates a map's source code for compliance with schema and additional integrity requirements.
     *
     * This is fairly expensive: fragments will be expanded which may cause plenty of page lookups.
     *
     * @param DataMapContent $content
     * @return Status
     */
    public function validate( DataMapContent $content ): Status {
        $result = new Status();
        $contentStatus = $content->getData();

        // Short-circuit if the JSON is known bad
        if ( !$contentStatus->isGood() ) {
            $result->fatal( 'datamap-error-validate-invalid-json' );
            return $result;
        }

        $isFragment = $content->isFragment();
        if ( $isFragment && isset( $contentStatus->getValue()->include ) ) {
            $result->fatal( 'datamap-error-validatespec-map-mixin-with-mixins' );
            return $result;
        }

        if ( !$this->validateFragmentRefs( $result, $contentStatus->getValue() ) ) {
            return $result;
        }

        $data = $content->expandData();

        /** @var ?MapVersionInfo */
        $schemaVersion = null;
        if ( !$this->validateAgainstSchema( $result, $data, $isFragment, $schemaVersion ) || $schemaVersion === null ) {
            return $result;
        }

        if ( !$this->validateAgainstConstraints( $result, $data, $schemaVersion ) ) {
            return $result;
        }

        return $result;
    }

    private function validateFragmentRefs( Status $status, \stdClass $data ): bool {
        $result = true;

        if ( isset( $data->include ) ) {
            $config = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );

            foreach ( $data->include as $fragmentName ) {
                $title = Title::newFromText( $fragmentName );
                $fragmentPage = -1;
                if ( $title->getNamespace() === $config->getNamespaceId() ) {
                    $fragmentPage = DataMapContent::loadPage( $title );
                }

                if ( is_numeric( $fragmentPage ) || $fragmentPage->getData()->getValue() === null ) {
                    $status->fatal( 'datamap-error-validatespec-map-bad-mixin', wfEscapeWikiText( $fragmentName ) );
                    $result = false;
                }
            }
        }

        return $result;
    }

    private function validateAgainstSchema(
        Status $result,
        \stdClass $data,
        bool $isFragment,
        ?MapVersionInfo &$version
    ): bool {
        $validator = $this->createValidator();
        $schemaWasBad = false;

        if ( isset( $data->{'$schema'} ) && is_string( $data->{'$schema'} ) ) {
            try {
                $validator->validate( $data, (object)[ '$ref' => $data->{'$schema'} ] );
            } catch ( ResourceNotFoundException $exc ) {
                $schemaWasBad = true;
            }
        } else {
            $schemaWasBad = true;
        }

        if ( $schemaWasBad ) {
            $formatted = implode(
                '',
                array_map(
                    fn ( $item ) => "\n* <code>" . $this->schemaProvider->makePublicUrl( $item ) . '</code>',
                    SchemaRevision::SUPPORTED_REVISIONS
                )
            );
            $result->fatal( 'datamap-validate-bad-schema', new RawMessage( $formatted ) );
            return false;
        }

        $version = new MapVersionInfo(
            $this->schemaVersionMap[$data->{'$schema'}],
            $isFragment
        );

        if ( !$validator->isValid() ) {

            $this->formatJsonSchemaErrors(
                $result,
                $version,
                $validator->getErrors( Validator::ERROR_DOCUMENT_VALIDATION )
            );
        }

        return $result->isOK();
    }

    private function formatJsonSchemaErrors( Status $status, MapVersionInfo $version, array $errors ) {
        foreach ( $errors as $error ) {
            // Ignore required property constraints in fragments, and reduce 'anyOf' to warnings
            $reduceToWarning = false;
            if ( $version->isFragment ) {
                if ( $error['constraint'] === 'required' ) {
                    continue;
                }

                $reduceToWarning = in_array( $error['constraint'], [ 'anyOf', 'oneOf' ] );

                if ( $error['constraint'] === 'oneOf' && $error['pointer'] === '' ) {
                    continue;
                }
            }

            // 'anyOf' contains a sub-array of errors for each match attempt. Find one without any 'required' and
            // 'additionalProp' violations. Otherwise, fall back to a generic error.
            if ( $error['constraint'] === 'anyOf' ) {
                $moreErrors = array_filter(
                    array_map(
                        static function ( $errors ) {
                            return array_filter(
                                $errors,
                                fn ( $el ) => !in_array( $el['constraint'], [ 'required', 'additionalProp' ] )
                            );
                        },
                        $error['matchErrors']
                    ),
                    fn ( $el ) => !empty( $el )
                );

                if ( count( $moreErrors ) === 1 ) {
                    $this->formatJsonSchemaErrors( $status, $version, $moreErrors[0] );
                    continue;
                }
            }

            // If minimum length is 1, narrow this error down to 'non-empty' so we can display a better message
            if ( $error['constraint'] === 'minLength' && $error['minLength'] === 1 ) {
                $error['constraint'] = 'minLengthEmpty';
            }

            // Pick a message and provide any additional parameters
            $msg = self::ERROR_MESSAGE_MAP[$error['constraint']] ?? self::UNKNOWN_ERROR_MESSAGE;
            $params = [
                $error['pointer'],
            ];
            switch ( $error['constraint'] ) {
                case 'additionalProp':
                    $params[0] .= '/' . $error['apProperty'];
                    break;
                case 'minLength':
                    $params[] = $error['minLength'];
                    break;
                case 'exclusiveMinimum':
                    $params[] = $error['minimum'];
                    break;
                case 'exclusiveMaximum':
                    $params[] = $error['maximum'];
                    break;

                default:
                    break;
            }

            if ( $reduceToWarning ) {
                $status->warning( $msg, ...$params );
            } else {
                $status->fatal( $msg, ...$params );
            }
        }
    }

    private function validateAgainstConstraints( Status $result, \stdClass $data, MapVersionInfo $version ): bool {
        $checker = new MapDataConstraintChecker( $version, $data, $result );
        return $checker->run();
    }
}
