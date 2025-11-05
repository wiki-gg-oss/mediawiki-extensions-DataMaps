const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    LeafletViewportManager = require( './viewport/LeafletViewportManager.js' ),
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
            .provide( 'leafletHost', this.#viewportElement )
            .mount( mountTargetElement );
    }


    getViewportManager() {
        return this.#viewportManager;
    }
};

