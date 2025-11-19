const MarkerPresentationType = require( './MarkerPresentationType.js' );


module.exports = class MarkerType {
    #id;
    #name = '';
    #descriptionHtml = null;
    #childTypes = null;


    constructor( id ) {
        this.#id = id;
    }


    getId() {
        return this.#id;
    }


    getName() {
        return this.#name;
    }


    setName( value ) {
        this.#name = value;
    }


    getDescriptionHtml() {
        return this.#descriptionHtml;
    }


    setDescriptionHtml( value ) {
        this.#descriptionHtml = value;
    }


    getStyle() {
        return {
            form: MarkerPresentationType.CIRCLE,
            fillColour: '#f0f',
        };
    }


    pushChildInternal( subtype ) {
        // TODO: really should not be public, see comment in MarkerTypeManager.createSubType

        if ( this.#childTypes === null ) {
            this.#childTypes = [];
        }

        this.#childTypes.push( subtype );
    }


    asTransientMetadata() {
        return {
            id: this.#id,
            name: this.#name,
            descriptionHtml: this.#descriptionHtml,
            subtypes: this.#childTypes ? this.#childTypes.map( item => item.asTransientMetadata() ) : null,
        };
    }
};
