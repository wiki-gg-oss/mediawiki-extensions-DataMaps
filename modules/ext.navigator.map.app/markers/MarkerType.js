const
    MarkerPresentationType = require( './MarkerPresentationType.js' ),
    MarkerStyle = require( './MarkerStyle.js' ),
    PopupData = require( '../features/PopupData.js' );


module.exports = class MarkerType {
    #id;
    #name = '';
    #descriptionHtml = null;
    #childTypes = null;
    #forbidsDisplay = false;
    #style;


    constructor( id ) {
        this.#id = id;
        this.#style = new MarkerStyle();
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


    isDisplayForbidden() {
        return this.#forbidsDisplay;
    }


    setForbidsDisplay( value ) {
        this.#forbidsDisplay = value;
    }


    getStyle() {
        if ( this.#forbidsDisplay ) {
            throw new Error( 'Cannot retrieve marker style of a non-display (organisational) marker type.' );
        }
        return this.#style;
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
            placeholderStyle: this.#forbidsDisplay ? false : this.#style.asTransientMetadata(),
            subtypes: this.#childTypes ? this.#childTypes.map( item => item.asTransientMetadata() ) : null,
        };
    }
};
