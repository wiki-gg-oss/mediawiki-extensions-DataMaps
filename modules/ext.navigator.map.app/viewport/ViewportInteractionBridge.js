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

            map.on( 'zoomend zoomlevelschange', this.#updateZoomUiState, this );

            this.#updateZoomUiState();
        } );
    }


    #updateZoomUiState() {
        console.debug( `[Navigator] Updating viewport UI state following zoom change` );

        const map = this.#viewportManager.getLeafletHandle();

        this.#uiState.setZoomAbility( map._zoom > map.getMinZoom(), map._zoom < map.getMaxZoom() );
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
