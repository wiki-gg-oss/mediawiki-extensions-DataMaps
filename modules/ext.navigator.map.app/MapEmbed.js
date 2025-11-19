const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    useAppSettings = require( './stores/AppSettingsStore.js' ),
    useMarkerTypes = require( './stores/MarkerTypesStore.js' ),
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
    #markerTypes;
    #viewportUpdateTimeoutId = null;


    constructor( mountTargetElement ) {
        this.#pinia = createPinia();
        this.#viewportElement = document.createElement( 'div' );
        this.#viewportManager = new LeafletViewportManager( this.#viewportElement );
        this.#app = Vue.createMwApp( App )
            .use( this.#pinia )
            .provide( InjectedSymbol.LEAFLET_HOST, this.#viewportElement )
            .provide( InjectedSymbol.VIEWPORT_INTERACTION, new ViewportInteractionBridge( this.#viewportManager,
                this.#pinia ) )
            .provide( InjectedSymbol.MARKER_SEARCH_ENGINE, new MarkerSearchEngine() )
            .mount( mountTargetElement );
        this.#markerTypeManager = new MarkerTypeManager( this.#pinia );
        this.#featureTree = new FeatureTree( this );
        this.#appSettings = useAppSettings( this.#pinia );
        this.#markerTypes = useMarkerTypes( this.#pinia );
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

