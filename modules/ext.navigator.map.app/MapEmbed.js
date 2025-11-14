const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    useAppSettings = require( './stores/AppSettingsStore.js' ),
    InjectedSymbol = require( './InjectedSymbol.js' ),
    LeafletViewportManager = require( './viewport/LeafletViewportManager.js' ),
    ViewportInteractionBridge = require( './viewport/ViewportInteractionBridge.js' ),
    MarkerSearchEngine = require( './search/MarkerSearchEngine.js' ),
    App = require( './components/App.vue' );


module.exports = class MapEmbed {
    #viewportElement;
    #viewportManager;
    #pinia;
    #app;
    #appSettings;


    constructor( mountTargetElement ) {
        this.#viewportElement = document.createElement( 'div' );
        this.#viewportManager = new LeafletViewportManager( this.#viewportElement );
        this.#pinia = createPinia();
        this.#app = Vue.createMwApp( App )
            .use( this.#pinia )
            .provide( InjectedSymbol.LEAFLET_HOST, this.#viewportElement )
            .provide( InjectedSymbol.VIEWPORT_INTERACTION, new ViewportInteractionBridge( this.#viewportManager,
                this.#pinia ) )
            .provide( InjectedSymbol.MARKER_SEARCH_ENGINE, new MarkerSearchEngine() )
            .mount( mountTargetElement );
        this.#appSettings = useAppSettings( this.#pinia );
    }


    setSubtitleHtml( value ) {
        if ( !value ) {
            value = null;
        }

        this.#appSettings.setSubtitleHtml( value );
    }


    getViewportManager() {
        return this.#viewportManager;
    }
};

