const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    InjectedSymbol = require( './InjectedSymbol.js' ),
    LeafletViewportManager = require( './viewport/LeafletViewportManager.js' ),
    ViewportInteractionBridge = require( './viewport/ViewportInteractionBridge.js' ),
    App = require( './components/App.vue' );


module.exports = class MapEmbed {
    #viewportElement;
    #viewportManager;
    #pinia;
    #app;


    constructor( mountTargetElement ) {
        this.#viewportElement = document.createElement( 'div' );
        this.#viewportManager = new LeafletViewportManager( this.#viewportElement );
        this.#pinia = createPinia();
        this.#app = Vue.createMwApp( App )
            .use( this.#pinia )
            .provide( InjectedSymbol.LEAFLET_HOST, this.#viewportElement )
            .provide( InjectedSymbol.VIEWPORT_INTERACTION, new ViewportInteractionBridge( this.#viewportManager,
                this.#pinia ) )
            .mount( mountTargetElement );
    }


    getViewportManager() {
        return this.#viewportManager;
    }
};

