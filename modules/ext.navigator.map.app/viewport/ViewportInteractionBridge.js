const
    useViewportState = require( '../ui/stores/ViewportState.js' );


module.exports = class ViewportInteractionBridge {
    #mapEmbed;
    #viewportManager;
    #uiState;


    constructor( mapEmbed, pinia ) {
        this.#mapEmbed = mapEmbed;
        this.#viewportManager = this.#mapEmbed.getViewportManager();
        this.#uiState = useViewportState( pinia );

        this.#viewportManager.waitUntilReady().then( () => {
            const map = this.#viewportManager.getLeafletHandle();

            map.on( 'zoomend', this.#propagateZoomLevel, this );
            map.on( 'zoomlevelschange', this.#propagateZoomRange, this );
            map.on( 'moveend', this.#propagateViewBox, this );

            this.#propagateZoomLevel();
            this.#propagateZoomRange();
            this.#propagateViewBox();
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


    #propagateViewBox() {
        console.debug( `[Navigator] Updating view-box in the viewport UI state` );

        const map = this.#viewportManager.getLeafletHandle();

        const bounds = map.getBounds(),
            ne = bounds.getNorthEast(),
            sw = bounds.getSouthWest();

        this.#uiState.setViewBox( [ ne.lat, ne.lng ], [ sw.lat, sw.lng ] );
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


    navigateToEditPage() {
        location.href = this.#mapEmbed.getSourceCodeTitle().getUrl( { action: 'edit' } );
    }
};
