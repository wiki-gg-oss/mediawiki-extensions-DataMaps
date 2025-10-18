<?php
namespace MediaWiki\Extension\DataMaps\ParserFunctions;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataMapContent;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderOptions;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;

final class EmbedMapFunction extends ParserFunction {
    public function __construct(
        private readonly ExtensionConfig $config
    ) { }

    /**
     * Embeds a map.
     *
     * {{DataMap:Arbury Interactive Map}}
     * {{Map:Arbury Interactive Map|filter=activities|max-width=300}}
     *
     * @param Parser $parser
     * @param PPFrame $frame
     * @param PPNode[] $args
     * @return string
     */
    public function run( Parser $parser, PPFrame $frame, array $args ): array {
        $params = $this->getArguments( $frame, $args, [
            'filter' => null,
            'max-width' => null,
            'class' => null,
            'layout' => 'full',
            'marker' => null,
            'over-marker' => null,
            'open-marker' => null,
        ] );

        $title = Title::makeTitleSafe( $this->config->getNamespaceId(), $params[0] );
        if ( !$title ) {
            return $this->wrapError( 'datamap-error-pf-invalid-title' );
        }

        // Register page's dependency on the data map
        $parser->getOutput()->addTemplate( $title, $title->getArticleId(),
            $parser->fetchCurrentRevisionRecordOfTitle( $title )?->getId() ?? 0 );

        // Add the page to a tracking category
        $parser->addTrackingCategory( 'datamap-category-pages-including-maps' );

        // Retrieve and validate options
        $options = self::getRenderOptions( $params );
        if ( is_string( $options ) ) {
            return $this->wrapError( $options );
        }

        // Verify the page exists and is a data map
        // TODO: separate message if the page is of foreign format and can be ported
        $content = DataMapContent::loadPage( $title );
        if ( $content === DataMapContent::LERR_NOT_FOUND ) {
            return $this->wrapError(
                'datamap-error-pf-page-does-not-exist',
                wfEscapeWikiText( $title->getFullText() )
            );
        } elseif ( $content === DataMapContent::LERR_NOT_DATAMAP ) {
            return $this->wrapError(
                'datamap-error-pf-page-invalid-content-model',
                wfEscapeWikiText( $title->getFullText() )
            );
        } elseif ( !$content->getValidationStatus()->isOK() ) {
            $parser->addTrackingCategory( 'datamap-category-pages-including-broken-maps' );
            return $this->wrapError(
                'datamap-error-map-validation-fail',
                wfEscapeWikiText( $title->getFullText() )
            );
        }

        $embed = $content->getEmbedRenderer( $title, $parser, $parser->getOutput() );
        $embed->prepareOutput();

        return [ $embed->getHtml( $options ), 'noparse' => true, 'isHTML' => true ];
    }

    /**
     * Extracts and validates options given to this parser function into an EmbedRenderOptions object.
     *
     * @param array $params
     * @return EmbedRenderOptions|string
     */
    private static function getRenderOptions( array $params ) {
        $result = new EmbedRenderOptions();

        if ( $params['filter'] ) {
            $result->displayGroups = explode( ',', $params['filter'] );
        }

        if ( $params['max-width'] ) {
            $result->maxWidthPx = intval( $params['max-width'] );
            if ( $result->maxWidthPx <= 0 ) {
                return 'datamap-error-pf-max-width-invalid';
            }
        }

        if ( $params['class'] ) {
            $result->classes = explode( ' ', $params['class'] );
        }

        if ( $params['layout'] === 'mini' ) {
            $result->miniStyle = true;
        }

        if ( $params['marker'] ) {
            $params['open-marker'] = $params['marker'];
        }

        if ( $params['open-marker'] ) {
            $result->markerIdToOpen = $params['open-marker'];
        }

        if ( $params['over-marker'] ) {
            $result->markerIdToCentreOn = $params['over-marker'];
        }

        return $result;
    }
}
