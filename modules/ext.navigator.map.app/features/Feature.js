module.exports = class Feature {
    #parentTree;
    #id;
    #locationVec;


    constructor( parentTree, id, locationVec ) {
        this.#parentTree = parentTree;
        this.#id = id;
        this.#locationVec = locationVec;
    }


    getOwnerTree() {
        return this.#parentTree;
    }


    getMapEmbed() {
        return this.#parentTree.getMapEmbed();
    }


    getId() {
        return this.#id;
    }


    getLocation() {
        return this.#locationVec;
    }
};
