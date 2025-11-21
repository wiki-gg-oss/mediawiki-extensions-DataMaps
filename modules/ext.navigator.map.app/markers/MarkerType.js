const
    MarkerPresentationType = require( './MarkerPresentationType.js' ),
    PopupData = require( '../features/PopupData.js' );


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
            pointForm: MarkerPresentationType.CIRCLE,
            size: 160,
            sizeHalf: 80,
            fillColour: '#f0f',
            fillOpacity: 1,
            strokeColour: '#0f0',
            strokeOpacity: 1,
            strokeWidth: 1,
        };
    }


    getDefaultPopupData() {
        return PopupData.Empty;
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
