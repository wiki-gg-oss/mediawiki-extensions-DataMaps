module.exports = class PopupData {
    #title;
    #descHtml;
    #linkTarget;
    #imageUrl;
    #imageWidth;
    #imageHeight;


    constructor( {
        title = null,
        descHtml = null,
        linkTarget = null,
        imageUrl = null,
        imageWidth = null,
        imageHeight = null,
    } ) {
        this.#title = title;
        this.#descHtml = descHtml;
        this.#linkTarget = linkTarget;
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


    getLinkTarget() {
        return this.#linkTarget;
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

