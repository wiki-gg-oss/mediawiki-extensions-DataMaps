module.exports = class PopupData {
    #titleHtml;
    #descHtml;
    #imageUrl;
    #imageWidth;
    #imageHeight;


    constructor( {
        titleHtml = null,
        descHtml = null,
        imageUrl = null,
        imageWidth = null,
        imageHeight = null,
    } ) {
        this.#titleHtml = titleHtml;
        this.#descHtml = descHtml;
        this.#imageUrl = imageUrl;
        this.#imageWidth = imageWidth;
        this.#imageHeight = imageHeight;
    }


    getTitleHtml() {
        return this.#titleHtml;
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

