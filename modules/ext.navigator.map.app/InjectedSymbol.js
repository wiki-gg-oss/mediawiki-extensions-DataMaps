const
    { inject } = require( 'vue' );


module.exports = Object.freeze( {
    LEAFLET_HOST: 'leaflet_host',
    VIEWPORT_INTERACTION: 'viewport_interaction',


    useLeafletHost() {
        return inject( module.exports.LEAFLET_HOST );
    },


    useViewportInteraction() {
        return inject( module.exports.VIEWPORT_INTERACTION );
    },
} );
