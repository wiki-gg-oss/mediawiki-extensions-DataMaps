const
    Vue = require( 'vue' ),
    { createPinia } = require( 'pinia' ),
    App = require( './components/App.vue' );


function initialiseEmbed( element ) {
    element.classList.add( 'ext-navi-map--ready' );
    const pinia = createPinia();
    const app = Vue.createMwApp( App )
        .use( pinia )
        .mount( element );
}


module.exports = {
    initialiseEmbed,
};
