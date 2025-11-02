const
    Vue = require( 'vue' ),
    App = require( './components/App.vue' );


function initialiseEmbed( element ) {
    element.classList.add( 'ext-navi-map--ready' );
    const app = Vue.createMwApp( App ).mount( element );
}


module.exports = {
    initialiseEmbed,
};
