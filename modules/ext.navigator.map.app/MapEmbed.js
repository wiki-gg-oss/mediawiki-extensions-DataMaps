const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    useAppSettings = require( './stores/AppSettingsStore.js' ),
    useMarkerTypes = require( './stores/MarkerTypesStore.js' ),
    InjectedSymbol = require( './InjectedSymbol.js' ),
    MarkerTypeManager = require( './markers/MarkerTypeManager.js' ),
    LeafletViewportManager = require( './viewport/LeafletViewportManager.js' ),
    ViewportInteractionBridge = require( './viewport/ViewportInteractionBridge.js' ),
    MarkerSearchEngine = require( './search/MarkerSearchEngine.js' ),
    App = require( './components/App.vue' );


module.exports = class MapEmbed {
    #viewportElement;
    #markerTypeManager;
    #viewportManager;
    #pinia;
    #app;
    #appSettings;
    #markerTypes;


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


    getViewportManager() {
        return this.#viewportManager;
    }
};

