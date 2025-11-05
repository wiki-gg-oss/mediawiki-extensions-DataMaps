const
    { inject } = require( 'vue' );


module.exports = Object.freeze( {
    LEAFLET_HOST: 'leaflet_host',


    useLeafletHost() {
        return inject( module.exports.LEAFLET_HOST );
    },
} );
