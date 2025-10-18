<?php
namespace MediaWiki\Extension\DataMaps\ParserFunctions;

use Error;
use InvalidArgumentException;
use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapContentFactory;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataMapContent;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Extension\DataMaps\Output\MapOutputFactory;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderOptions;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;

final class EmbedMapFunction extends ParserFunction {
    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly MapContentFactory $mapContentFactory,
        private readonly MapOutputFactory $mapOutputFactory
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
        $contentResult = $this->mapContentFactory->loadPageContent( $title );

        if ( !$contentResult->isOK() ) {
            // TODO: format this properly without getHTML and make sure to use the parser's context info
            return $this->wrapError( $contentResult->getHTML() );
        }

        $content = $contentResult->getValue();
        if ( $content instanceof DataMapContent ) {
            if ( !$content->getValidationStatus()->isOK() ) {
                $parser->addTrackingCategory( 'datamap-category-pages-including-broken-maps' );
                return $this->wrapError( 'datamap-error-map-validation-fail', $title->getFullText() );
            }

            $embed = $content->getEmbedRenderer( $title, $parser, $parser->getOutput() );
            $embed->prepareOutput();

            return [ $embed->getHtml( $options ), 'noparse' => true, 'isHTML' => true ];
        } elseif ( $content instanceof MapContent ) {
            // TODO: run validation

            $metadataEmitter = $this->mapOutputFactory->createMapMetadataEmitter( $title );
            $mapRenderer = $this->mapOutputFactory->createMapRenderer( $title );

            $metadataEmitter->runForContent( $parser, $parser->getOutput(), $content );

            return [ $mapRenderer->getHtmlForContent( $parser->getOutput(), $content ),
                'noparse' => true, 'isHTML' => true ];
        } else {
            throw new InvalidArgumentException( 'MapContentFactory returned an unsupported content object.' );
        }
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
