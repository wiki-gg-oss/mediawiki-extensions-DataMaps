const
    Feature = require( './Feature.js' );


module.exports = class TextFeature extends Feature {
    #html;


    constructor( parentTree, id, locationVec, html ) {
        super( parentTree, id, locationVec );
        this.#html = html;
    }


    getHtml() {
        return this.#html;
    }
};
