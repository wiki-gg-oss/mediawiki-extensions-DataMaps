const
    Feature = require( './Feature.js' );


module.exports = class MarkerFeature extends Feature {
    #markerType;
    #popupData;


    constructor( parentTree, id, locationVec, markerType, popupData = null ) {
        super( parentTree, id, locationVec );
        this.#markerType = markerType;
        this.#popupData = popupData;
    }


    getMarkerType() {
        return this.#markerType;
    }


    getPresentationType() {
        return this.#markerType.getStyle().form;
    }


    onClick( event ) {
        const embed = this.getMapEmbed();
        const popupData = this.#popupData || this.#markerType.getDefaultPopupData();
        embed.displayPopoverAt( event.virtualPos, popupData );
    }
};
