const
    { ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );

module.exports = defineStore( 'viewportState', () => {
    return {
        canZoomIn: true,
		canZoomOut: false,
    };
} );
