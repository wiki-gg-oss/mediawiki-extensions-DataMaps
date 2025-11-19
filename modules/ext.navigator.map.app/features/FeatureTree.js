const
    FeatureFactory = require( './FeatureFactory.js' );


module.exports = class FeatureTree {
    #mapEmbed;
    #featureMap;
    #featureSet;
    #factory;


    constructor( mapEmbed ) {
        this.#mapEmbed = mapEmbed;
        this.#featureMap = {};
        this.#featureSet = new Set();
        this.#factory = new FeatureFactory( this, Object.freeze( {
            addFeature: this.#addFeature.bind( this ),
        } ) );
    }


    getMapEmbed() {
        return this.#mapEmbed;
    }


    getFeatureFactory() {
        return this.#factory;
    }


    getFeatures() {
        return Object.freeze( this.#featureSet );
    }


    #addFeature( feature ) {
        this.#featureMap[ feature.getId() ] = feature;
        this.#featureSet.add( feature );
    }
};
