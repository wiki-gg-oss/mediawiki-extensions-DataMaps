const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    App = require( './components/App.vue' );


function initialiseEmbed( element ) {
    element.classList.add( 'ext-navi-map--ready' );

    const leafletHost = document.createElement( 'div' );
    leafletHost.textContent = '[PH]Headless Leaflet viewport host area';

    const pinia = createPinia();
    Vue.createMwApp( App )
        .use( pinia )
        .provide( 'leafletHost', leafletHost )
        .mount( element );
}


module.exports = {
    initialiseEmbed,
};
