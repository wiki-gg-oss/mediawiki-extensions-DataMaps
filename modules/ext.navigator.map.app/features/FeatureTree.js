const
    FeatureFactory = require( './FeatureFactory.js' );


module.exports = class FeatureTree {
    #mapEmbed;
    #featureMap;
    #featureSet;
    #dirtyFeatureSet;
    #factory;
    #isMutating = false;


    constructor( mapEmbed ) {
        this.#mapEmbed = mapEmbed;
        this.#featureMap = {};
        this.#featureSet = new Set();
        this.#dirtyFeatureSet = new Set();
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
        if ( this.#isMutating ) {
            throw new Error( 'Cannot retrieve the feature set while mutating' );
        }
        return Object.freeze( this.#featureSet );
    }


    getDirtyFeatureSet() {
        const retval = Object.freeze( this.#dirtyFeatureSet );
        this.#dirtyFeatureSet = new Set();
        return retval;
    }


    markFeatureDirty( feature ) {
        if ( !this.#featureSet.has( feature ) ) {
            throw new Error( 'Cannot mark a feature dirty in a tree that does not own it' );
        }
        this.#dirtyFeatureSet.add( feature );
    }


    openMutationSection( callback ) {
        if ( this.#isMutating ) {
            throw new Error( 'Cannot enter mutable state when already in it' );
        }
        console.debug( `[Navigator] Entering a feature tree mutation section...` );
        this.#isMutating = true;
        callback( this.#factory );
        this.#isMutating = false;
        console.debug( `[Navigator] Left the feature tree mutation section` );
        if ( this.#dirtyFeatureSet.size ) {
            console.debug( `[Navigator] Tree has been mutated; pinging the renderer` );
            this.#mapEmbed.queueViewportUpdate();
        }
    }


    #addFeature( feature ) {
        if ( !this.#isMutating ) {
            throw new Error( 'Cannot add features to the tree while not mutable' );
        }
        this.#featureMap[ feature.getId() ] = feature;
        this.#featureSet.add( feature );
        this.#dirtyFeatureSet.add( feature );
    }
};
