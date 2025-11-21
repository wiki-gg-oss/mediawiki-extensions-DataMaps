const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    useAppSettings = require( './stores/AppSettingsStore.js' ),
    useMarkerTypes = require( './stores/MarkerTypesStore.js' ),
    usePopoverState = require( './stores/PopoverState.js' ),
    InjectedSymbol = require( './InjectedSymbol.js' ),
    MarkerTypeManager = require( './markers/MarkerTypeManager.js' ),
    LeafletViewportManager = require( './viewport/LeafletViewportManager.js' ),
    ViewportInteractionBridge = require( './viewport/ViewportInteractionBridge.js' ),
    FeatureTree = require( './features/FeatureTree.js' ),
    MarkerSearchEngine = require( './search/MarkerSearchEngine.js' ),
    App = require( './components/App.vue' );


module.exports = class MapEmbed {
    #viewportElement;
    #markerTypeManager;
    #featureTree;
    #viewportManager;
    #pinia;
    #app;
    #appSettings;
    #popoverState;
    #markerTypes;
    #viewportUpdateTimeoutId = null;
    #dispatchMoveTickImmediateFn;
    #moveTickFrameReqId = null;


    constructor( mountTargetElement ) {
        this.#dispatchMoveTickImmediateFn = this.#dispatchMoveTickImmediate.bind( this );

        this.#pinia = createPinia();
        this.#viewportElement = document.createElement( 'div' );
        this.#featureTree = new FeatureTree( this );
        this.#viewportManager = new LeafletViewportManager( this.#viewportElement, Object.freeze( {
            featureTree: this.#featureTree,
            dispatchMoveTickLatent: this.#dispatchMoveTickLatent.bind( this ),
            dispatchMoveTickImmediate: this.#dispatchMoveTickImmediateFn,
        } ) );
        this.#app = Vue.createMwApp( App )
            .use( this.#pinia )
            .provide( InjectedSymbol.LEAFLET_HOST, this.#viewportElement )
            .provide( InjectedSymbol.VIEWPORT_INTERACTION, new ViewportInteractionBridge( this.#viewportManager,
                this.#pinia ) )
            .provide( InjectedSymbol.MARKER_SEARCH_ENGINE, new MarkerSearchEngine() )
            .mount( mountTargetElement );
        this.#markerTypeManager = new MarkerTypeManager( this.#pinia );
        this.#appSettings = useAppSettings( this.#pinia );
        this.#popoverState = usePopoverState( this.#pinia );
        this.#markerTypes = useMarkerTypes( this.#pinia );

        this.#viewportManager.waitUntilReady().then( () => {
            this.#popoverState.setContainerProjectionFn( this.#viewportManager.getVirtualPointProjector() );
        } );
    }


    setSubtitleHtml( value ) {
        if ( !value ) {
            value = null;
        }

        this.#appSettings.setSubtitleHtml( value );
    }


    getMarkerTypeManager() {
        return this.#markerTypeManager;
    }


    getFeatureTree() {
        return this.#featureTree;
    }


    getFeatureFactory() {
        return this.#featureTree.getFeatureFactory();
    }


    getViewportManager() {
        return this.#viewportManager;
    }


    #dispatchMoveTickLatent() {
        // TODO: if we need to repeat this pattern, better to make a factory and encapsulate the frame request ID
        if ( this.#moveTickFrameReqId === null ) {
            this.#moveTickFrameReqId = requestAnimationFrame( this.#dispatchMoveTickImmediateFn );
        }
    }


    #dispatchMoveTickImmediate() {
        this.#moveTickFrameReqId = null;

        console.debug( `[Navigator] Executing move tick updates` );
        if ( this.#popoverState.isVisible ) {
            this.#popoverState.reproject();
        }
    }


    displayPopoverAt( locationVec, popupData ) {
        console.debug( `[Navigator] Attaching popover to location ${locationVec}:`, popupData );
        this.#popoverState.activate( locationVec, popupData );
    }


    closePopover() {
        this.#popoverState.deactivate();
    }


    queueViewportUpdate() {
        if ( this.#viewportUpdateTimeoutId === null ) {
            console.debug( `[Navigator] Scheduling viewport update` );
            this.#viewportUpdateTimeoutId = setTimeout( async () => {
                await this.#viewportManager.waitUntilReady();
                console.debug( `[Navigator] Executing the viewport update` );
                // TODO: technically raceable here... and if the viewport update fails we've got a deadlock
                this.#viewportManager.update();
                this.#viewportUpdateTimeoutId = null;
            } );
        } else {
            console.debug( `[Navigator] Culling a viewport update request (already scheduled)` );
        }
    }
};

