const
    Feature = require( './Feature.js' );


module.exports = class MarkerFeature extends Feature {
    #markerType;


    constructor( parentTree, id, locationVec, markerType ) {
        super( parentTree, id, locationVec );
        this.#markerType = markerType;
    }


    getMarkerType() {
        return this.#markerType;
    }


    getPresentationType() {
        return this.#markerType.getStyle().form;
    }
};
