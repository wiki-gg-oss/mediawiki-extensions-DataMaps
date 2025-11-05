const
    useViewportState = require( '../stores/ViewportState.js' );


module.exports = class ViewportInteractionBridge {
    #viewportManager;
    #uiState;


    constructor( viewportManager, pinia ) {
        this.#viewportManager = viewportManager;
        this.#uiState = useViewportState( pinia );

        this.#viewportManager.waitUntilReady().then( () => {
            const map = this.#viewportManager.getLeafletHandle();

            map.on( 'zoomend', this.#propagateZoomLevel, this );
            map.on( 'zoomlevelschange', this.#propagateZoomRange, this );

            this.#propagateZoomLevel();
            this.#propagateZoomRange();
        } );
    }


    #propagateZoomLevel() {
        console.debug( `[Navigator] Updating zoom level in the viewport UI state` );

        const map = this.#viewportManager.getLeafletHandle();

        this.#uiState.setCurrentZoom( map._zoom );
    }


    #propagateZoomRange() {
        console.debug( `[Navigator] Updating zoom range in the viewport UI state` );

        const map = this.#viewportManager.getLeafletHandle();

        this.#uiState.setZoomRange( map.getMaxZoom(), map.getMinZoom() );
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
