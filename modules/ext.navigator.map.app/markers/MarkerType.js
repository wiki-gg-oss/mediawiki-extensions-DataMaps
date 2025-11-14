module.exports = class MarkerType {
    #id;
    #name = '';
    #descriptionHtml = null;


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


    asTransientMetadata() {
        return {
            id: this.#id,
            name: this.#name,
            descriptionHtml: this.#descriptionHtml,
            subtypes: null,
        };
    }
};
