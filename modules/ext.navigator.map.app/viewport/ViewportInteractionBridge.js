module.exports = class ViewportInteractionBridge {
    #viewportManager;


    constructor( viewportManager ) {
        this.#viewportManager = viewportManager;
    }


    zoomIn() {
        const map = this.#viewportManager.getLeafletHandle();
        if ( map ) {
            map.zoomIn();
        }
    }


    zoomOut() {
        const map = this.#viewportManager.getLeafletHandle();
        if ( map ) {
            map.zoomOut();
        }
    }
};
