const
    BackgroundImageFeature = require( './BackgroundImageFeature.js' ),
    TextFeature = require( './TextFeature.js' ),
    MarkerFeature = require( './MarkerFeature.js' );


module.exports = class FeatureFactory {
    #featureTree;
    #featureTreePrivate;
    #featureIdCounter;


    constructor( featureTree, featureTreePrivate ) {
        this.#featureTree = featureTree;
        this.#featureTreePrivate = featureTreePrivate;
        this.#featureIdCounter = 0;
    }


    createBackgroundImage( {
        location,
        width,
        height,
        imageUrl,
    } ) {
        // TODO: type-check
        const retval = new BackgroundImageFeature( this.#featureTree, ++this.#featureIdCounter, location, width, height,
            imageUrl );
        this.#featureTreePrivate.addFeature( retval );
        return retval;
    }


    createText( {
        location,
        text,
    } ) {
        // TODO: type-check
        const retval = new TextFeature( this.#featureTree, ++this.#featureIdCounter, location, text );
        this.#featureTreePrivate.addFeature( retval );
        return retval;
    }


    createMarker( {
        location,
        markerType,
    } ) {
        // TODO: type-check
        const retval = new MarkerFeature( this.#featureTree, ++this.#featureIdCounter, location, markerType );
        this.#featureTreePrivate.addFeature( retval );
        return retval;
    }
};
