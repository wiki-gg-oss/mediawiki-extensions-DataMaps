<?php
namespace MediaWiki\Extension\DataMaps\Data;

use Status;

class MarkerSpec extends DataModel {
    protected static string $publicName = 'MarkerSpec';

    public function reassignTo( object $newRaw ) {
        $this->raw = $newRaw;
    }

    /**
     * Returns marker's Y coordinate.
     */
    public function getLatitude(): float {
        return isset( $this->raw->lat ) ? $this->raw->lat : $this->raw->y;
    }

    /**
     * Returns marker's X coordinate.
     */
    public function getLongitude(): float {
        return isset( $this->raw->lon ) ? $this->raw->lon : $this->raw->x;
    }

    /**
     * Returns marker's label that will be shown in its popup and search.
     */
    public function getLabel(): ?string {
        return isset( $this->raw->name ) ? $this->raw->name : null;
    }

    /**
     * Returns marker's description that will be shown in its popup and used as a keyword source for search.
     */
    public function getDescription()/*: ?array|string */ {
        return isset( $this->raw->description ) ? $this->raw->description : null;
    }

    public function getCustomIcon(): ?string {
        return isset( $this->raw->icon ) ? $this->raw->icon : null;
    }

    /**
     * Returns marker's size scale.
     *
     * @since 0.16.14
     * @return float
     */
    public function getScale(): float {
        return $this->raw->scale ?? 1;
    }

    /**
     * Returns whether the label and description should be treated as wikitext as indicated by the data.
     *
     * If null, leaves the decision to MarkerProcessor.
     */
    public function isWikitext(): ?bool {
        return isset( $this->raw->isWikitext ) ? $this->raw->isWikitext : null;
    }

    /**
     * Returns marker's image to be shown in its popup.
     */
    public function getPopupImage(): ?string {
        if ( isset( $this->raw->image ) && $this->raw->image ) {
            return $this->raw->image;
        }
        return null;
    }

    /**
     * Returns an article to be linked in the marker's popup.
     */
    public function getRelatedArticle(): ?string {
        if ( isset( $this->raw->article ) && $this->raw->article ) {
            return $this->raw->article;
        }
        return null;
    }

    public function getRelatedArticleTarget(): ?string {
        $value = $this->getRelatedArticle();
        if ( !$value ) {
            return null;
        }

        if ( str_contains( $value, '|' ) ) {
            return explode( '|', $value, 2 )[ 0 ];
        }

        return $value;
    }

    /**
     * If set, returns the unique identifier requested in this marker's data. This identifier will be used in persistent
     * links and functions requiring browser's storage.
     */
    public function getCustomPersistentId() {
        return isset( $this->raw->id ) ? $this->raw->id : null;
    }

    /**
     * Returns optional keywords override to be used by search.
     */
    public function getSearchKeywords()/*: ?array|string */ {
        return isset( $this->raw->searchKeywords ) ? $this->raw->searchKeywords : null;
    }

    /**
     * Returns whether this marker should show up in search results.
     */
    public function isIncludedInSearch(): bool {
        return isset( $this->raw->canSearchFor ) ? $this->raw->canSearchFor : true;
    }
}
