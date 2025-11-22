const
    Feature = require( './Feature.js' );


module.exports = class MarkerFeature extends Feature {
    #markerType;
    #popupData;
    #style = null;


    constructor( parentTree, id, locationVec, markerType, popupData = null ) {
        super( parentTree, id, locationVec );
        this.#markerType = markerType;
        this.#popupData = popupData;
    }


    getMarkerType() {
        return this.#markerType;
    }


    getPresentationType() {
        return ( this.#style || this.#markerType.getStyle() ).getPointForm();
    }


    getStyle() {
        return this.#style || this.#markerType.getStyle();
    }


    onClick( event ) {
        const embed = this.getMapEmbed();
        const popupData = this.#popupData || this.#markerType.getDefaultPopupData();
        embed.displayPopoverAt( event.virtualPos, popupData );
    }

    
    getSearchMetadata() {
        const name = this.#popupData ? this.#popupData.getTitle() : this.#markerType.getName();
        const desc = this.#popupData ? this.#popupData.getDescriptionHtml() : null;
        return {
            name,
            phrases: [
                [ 1, name ],
                desc ? [ 0.2, desc ] : false,
            ],
        };
    }
};
