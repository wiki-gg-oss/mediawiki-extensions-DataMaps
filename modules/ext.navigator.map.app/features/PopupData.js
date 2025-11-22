module.exports = class PopupData {
    #title;
    #descHtml;
    #imageUrl;
    #imageWidth;
    #imageHeight;


    constructor( {
        title = null,
        descHtml = null,
        imageUrl = null,
        imageWidth = null,
        imageHeight = null,
    } ) {
        this.#title = title;
        this.#descHtml = descHtml;
        this.#imageUrl = imageUrl;
        this.#imageWidth = imageWidth;
        this.#imageHeight = imageHeight;
    }


    getTitle() {
        return this.#title;
    }


    getDescriptionHtml() {
        return this.#descHtml;
    }


    getImageUrl() {
        return this.#imageUrl;
    }


    getImageWidth() {
        return this.#imageWidth;
    }


    getImageHeight() {
        return this.#imageHeight;
    }
};


module.exports.Empty = new module.exports( {} );

