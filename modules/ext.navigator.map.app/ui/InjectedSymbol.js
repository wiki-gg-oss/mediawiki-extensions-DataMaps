const
    { inject } = require( 'vue' );


module.exports = Object.freeze( {
    LEAFLET_HOST: 'leaflet_host',
    VIEWPORT_INTERACTION: 'viewport_interaction',
    MARKER_SEARCH_ENGINE: 'marker_search_engine',


    useLeafletHost() {
        return inject( module.exports.LEAFLET_HOST );
    },


    useViewportInteraction() {
        return inject( module.exports.VIEWPORT_INTERACTION );
    },


    useMarkerSearchEngine() {
        return inject( module.exports.MARKER_SEARCH_ENGINE );
    },
} );
