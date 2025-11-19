const
    Feature = require( './Feature.js' );


module.exports = class BackgroundImageFeature extends Feature {
    #width;
    #height;
    #imageUrl;


    constructor( parentTree, id, locationVec, width, height, imageUrl ) {
        super( parentTree, id, locationVec );
        this.#width = width;
        this.#height = height;
        this.#imageUrl = imageUrl;
    }


    getWidth() {
        return this.#width;
    }


    getHeight() {
        return this.#height;
    }


    getImageUrl() {
        return this.#imageUrl;
    }
};
