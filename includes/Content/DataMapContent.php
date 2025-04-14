<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Content\JsonContent;
use MediaWiki\Extension\DataMaps\Data\DataMapSpec;
use MediaWiki\Extension\DataMaps\Data\DataModelMixinTransformer;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderer;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use stdClass;

class DataMapContent extends JsonContent {
    public const LERR_NOT_FOUND = 1;
    public const LERR_NOT_DATAMAP = 2;

    # Reduce 2-12 numbers in an array onto a single line
    private const JOIN_MULTIPLE_NUMBERS_RE = '/(\n\s+)([+-]?\d+(\.\d+)?([eE][-+]?\d+)?|true|false),(?:\n\s+(?:[+-]?\d+(\.\d+)?([eE][-+]?\d+)?|true|false|null|"[^"\n\t]*"),?){1,12}/';
    # Reduce short arrays of strings onto a single line
    private const JOIN_MULTIPLE_STRINGS_RE = '/\[((?:\n\s+".{1,30}",?\s*$){1,4})\n\s+\]/';
    # Reduces dict fields with only a single line of content (including previously joined multiple fields) to a single line
    private const COLLAPSE_SINGLE_LINE_DICT_RE = '/\{\n\s+("\w+": [^}\n\]]{1,120})\n\s+\}/';
    # Reduce arrays with only a single line of content (including previously joined multiple fields) to a single line
    private const COLLAPSE_SINGLE_LINE_ARRAY_RE = '/\[\s+(.+)\s+\]/';
    # Sets of named fields that should be combined onto a single line
    private const JOIN_LINE_FIELDS = [
        'left|right|top|bottom',
        // Backgrounds
        // Marker groups
        'fillColor|size',
        'pinColor|size',
        'icon|size',
        'borderColor|borderWidth',
        // Categories
        'name|subtleText',
        // Markers
        'id|lat|lon',
        'id|x|y',
        'id|y|x',
        'lat|lon',
        'x|y',
        'y|x',
    ];

    private ?DataMapSpec $modelCached = null;

    public function __construct( $text, $modelId = CONTENT_MODEL_DATAMAPS ) {
        parent::__construct( $text, $modelId );
    }

    public static function toJSON( stdclass $raw ) {
        $out = FormatJson::encode( $raw, "\t", FormatJson::ALL_OK );

        foreach ( self::JOIN_LINE_FIELDS as $term ) {
            $part = '(?:("(?:' . $term . ')": [^,\n]+,?))';
            $fieldCount = substr_count( $term, '|' ) + 1;
            $full = '/' . implode( '\s+', array_fill( 0, $fieldCount, $part ) ) . '(\s+)/';
            $subs = implode( ' ', array_map( fn ( $n ) => '$' . $n, range( 1, $fieldCount ) ) ) . "$" . ( $fieldCount + 1 );
            $out = preg_replace( $full, $subs, $out );
        }

        $out = preg_replace_callback( self::JOIN_MULTIPLE_NUMBERS_RE, static function ( array $matches ) {
            $txt = $matches[0];
            $txt = preg_replace( '/\s*\n\s+/', '', $txt );
            $txt = str_replace( ',', ', ', $txt );
            return $matches[1] . $txt;
        }, $out );
        $out = preg_replace( self::COLLAPSE_SINGLE_LINE_DICT_RE, '{ $1 }', $out );
        $out = preg_replace( self::COLLAPSE_SINGLE_LINE_ARRAY_RE, '[ $1 ]', $out );

        return $out;
    }

    public static function loadPage( Title $title ) {
        if ( !$title || !$title->exists() ) {
            return self::LERR_NOT_FOUND;
        }

        $mapPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
        $content = $mapPage->getContent( RevisionRecord::RAW );

        if ( !( $content instanceof DataMapContent ) ) {
            return self::LERR_NOT_DATAMAP;
        }

        return $content;
    }

    /**
     * Decodes the JSON string.
     *
     * @return Status
     */
    public function getData() {
        if ( $this->jsonParse === null ) {
            $this->jsonParse = FormatJson::parse( $this->getText(), FormatJson::TRY_FIXING );
        }
        return $this->jsonParse;
    }

    public function isFragment(): bool {
        return DataMapSpec::staticIsFragment( $this->getData()->getValue() );
    }

    private function mergeFragments( stdClass $main ) {
        $config = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );

        // Copy the mixins list to prevent bad behaviour when merging occurs. Mixins should be always stated explicitly.
        $mixins = $main->include ?? null;

        if ( !$mixins ) {
            return $main;
        }

        $finalMixin = null;
        foreach ( $mixins as &$mixinName ) {
            $title = Title::newFromText( $mixinName, $config->getNamespaceId() );
            $mixinPage = self::loadPage( $title );

            // Mixin failed to load, skip it. There's no way for us to throw an error at this stage without crashing the whole
            // request. However, validation can catch this most of the time.
            if ( is_numeric( $mixinPage ) ) {
                continue;
            }
            $mixin = $mixinPage->getData()->getValue();
            if ( $mixin == null ) {
                continue;
            }

            if ( $finalMixin === null ) {
                // First mixin, keep unmodified
                $finalMixin = $mixin;
            } else {
                // Nth mixin, merge onto collective
                $finalMixin = DataModelMixinTransformer::mergeTwoObjects( $finalMixin, $mixin );
            }
        }

        if ( $finalMixin !== null ) {
            // Merge main onto collective
            $main = DataModelMixinTransformer::mergeTwoObjects( $finalMixin, $main );
        }

        // Remove fragment identification fields
        if ( isset( $main->{'$fragment'} ) ) {
            unset( $main->{'$fragment'} );
        }

        return $main;
    }

    public function expandData(): object {
        return $this->mergeFragments( $this->getData()->getValue() );
    }

    public function asModel(): DataMapSpec {
        if ( $this->modelCached === null ) {
            $this->modelCached = new DataMapSpec( $this->mergeFragments( $this->getData()->getValue() ) );
        }
        return $this->modelCached;
    }

    public function getEmbedRenderer(
        Title $title,
        Parser $parser,
        ParserOutput $parserOutput,
        array $options = []
    ): EmbedRenderer {
        return new EmbedRenderer( $title, $this->asModel(), $parser, $parserOutput, $options );
    }

    public function beautifyJSON() {
        return self::toJSON( $this->getData()->getValue() );
    }

    public function getValidationStatus(): Status {
        $schemaProvider = MediaWikiServices::getInstance()->getService( SchemaProvider::SERVICE_NAME );
        return $schemaProvider->makeValidator()->validate( $this );
    }
}
