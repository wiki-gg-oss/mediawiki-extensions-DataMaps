<?php
namespace MediaWiki\Extension\DataMaps\Content;

use MediaWiki\Extension\DataMaps\Content\Validation\EntityTreeValidator;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;

class MapContentFactory {
    public const SERVICE_NAME = 'DataMaps.ContentFactory';

    public function __construct(
        private readonly WikiPageFactory $wikiPageFactory,
        private readonly ExtensionConfig $config
    ) { }

    /**
     * Returns the title of the documentation page for a given map page. If documentation pages have been disabled on
     * the wiki, returns null instead.
     */
    public function getDocumentationTitle( PageReference $mapPageRef ): ?Title {
        $suffixMsg = wfMessage( 'datamap-doc-page-suffix' )->inContentLanguage();

        if ( $suffixMsg->isDisabled() ) {
            return null;
        }

        return Title::newFromPageReference( $mapPageRef )->getSubpage( $suffixMsg->plain() );
    }

    /**
     * Returns true if the given page is for documentation.
     */
    public function isDocumentationTitle( PageReference $page ): bool {
        $suffixMsg = wfMessage( 'datamap-doc-page-suffix' )->inContentLanguage();
        if ( $suffixMsg->isDisabled() ) {
            return false;
        }

        return $page->getNamespace() === $this->config->getNamespaceId()
            && str_ends_with( $page->getDBkey(), '/' . $suffixMsg->plain() );
    }

    /**
     * Loads map source content from a given title. Returns a null if unsuccessful.
     *
     * @return Status possibly wrapping a MapContent or DataMapContent object.
     */
    public function loadPageContent( Title $title ): Status {
        if ( !$title || !$title->exists() ) {
            return Status::newFatal( 'datamap-error-pf-page-does-not-exist', $title->getFullText() );
        }

        $content = $this->wikiPageFactory->newFromTitle( $title )->getContent( RevisionRecord::RAW );
        if ( !( $content instanceof MapContent ) ) {
            return Status::newFatal( 'datamap-error-pf-page-invalid-content-model', $title->getFullText() );
        }

        return Status::newGood( $content );
    }

    /**
     * Creates a dummy data object which is ready to be preloaded as default page text.
     *
     * @return array
     */
    public function createDefaultData( MapContentVersion $contentVersion = MapContentVersion::Latest ): array {
        return [
            'version' => $contentVersion->value,
            'settings' => [
                'displayCoordinates' => false,
            ],
            'markerTypes' => [
                [
                    'id' => 'lostsector',
                    'name' => 'Lost Sectors',
                    'style' => [
                        'image' => 48,
                        'image' => 'File:lostsector.png',
                    ],
                ],
                [
                    'group' => 'Secrets',
                    'include' => [
                        [
                            'id' => 'feather',
                            'name' => 'Feathers of Light',
                            'progressTracking' => true,
                            'style' => [
                                'size' => 48,
                                'image' => 'File:paleheart feather.png',
                            ],
                        ],
                        [
                            'id' => 'vision',
                            'name' => 'Visions of the Traveler',
                            'progressTracking' => true,
                            'style' => [
                                'size' => 48,
                                'image' => 'File:paleheart vision.png',
                            ],
                        ],
                    ],
                ],
            ],
            'features' => [
                [
                    'type' => 'BackgroundImage',
                    'image' => 'File:paleheart.jpg',
                    'dimensions' => 'same-as-file',
                    'attachFeatures' => [
                        [
                            'type' => 'BackgroundImage',
                            'image' => 'File:paleheart colouriser.jpg',
                            'dimensions' => 'same-as-file',
                        ],
                    ],
                ],
                [
                    'type' => 'MarkerCollection',
                    'attachType' => 'feather',
                    'attachFeatures' => [
                        [
                            'title' => 'Feather #1',
                            'description' => 'Found in The Lost City\'s Outskirts',
                            'image' => 'File:paleheart feather 1 loc.jpg',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Creates a map source validator.
     */
    public function createValidator( MapContentVersion $contentVersion ): EntityTreeValidator {
        return new EntityTreeValidator(
            $contentVersion
        );
    }
}
