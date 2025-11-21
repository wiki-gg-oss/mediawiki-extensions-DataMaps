const
    MarkerPresentationType = require( './MarkerPresentationType.js' );

module.exports = class MarkerStyle {
    #pointForm = MarkerPresentationType.CIRCLE;
    #size = 1;
    #fillColour = false;
    #fillOpacity = 1;
    #outlineColour = false;
    #outlineOpacity = 1;
    #outlineWidth = 1;


    getPointForm() {
        return this.#pointForm;
    }


    setPointForm( value ) {
        this.#pointForm = value;
        return this;
    }


    getSize( value ) {
        return this.#size;
    }


    setSize( value ) {
        this.#size = value;
        return this;
    }


    hasFill() {
        return this.#fillColour !== false;
    }


    assertFill( caller ) {
        if ( this.#fillColour === false ) {
            throw new Error( `Attempted to invoke '${caller}' but the style has no fill configured.` );
        }
    }


    getFillColour( value ) {
        this.assertFill( 'getFillColour' );
        return this.#fillColour;
    }


    setFillColour( value ) {
        this.#fillColour = value;
        return this;
    }


    getFillOpacity( value ) {
        this.assertFill( 'getFillOpacity' );
        return this.#fillOpacity;
    }


    setFillOpacity( value ) {
        this.#fillOpacity = value;
        return this;
    }


    hasOutline() {
        return this.#outlineColour !== false;
    }


    assertOutline( caller ) {
        if ( this.#outlineColour === false ) {
            throw new Error( `Attempted to invoke '${caller}' but the style has no fill configured.` );
        }
    }


    getOutlineColour( value ) {
        this.assertOutline( 'getOutlineColour' );
        return this.#outlineColour;
    }


    setOutlineColour( value ) {
        this.#outlineColour = value;
        return this;
    }


    getOutlineOpacity( value ) {
        this.assertOutline( 'getOutlineOpacity' );
        return this.#outlineOpacity;
    }


    setOutlineOpacity( value ) {
        this.#outlineOpacity = value;
        return this;
    }


    getOutlineWidth( value ) {
        this.assertOutline( 'getOutlineWidth' );
        return this.#outlineWidth;
    }


    setOutlineWidth( value ) {
        this.#outlineWidth = value;
        return this;
    }
};


module.exports.Empty = new module.exports( {} );
