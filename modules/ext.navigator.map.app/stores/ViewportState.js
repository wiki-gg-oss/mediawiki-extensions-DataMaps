const
    { ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );

module.exports = defineStore( 'viewportState', () => {
	const
		canZoomOut = ref( false ),
		canZoomIn = ref( false );


    return {
		canZoomOut,
        canZoomIn,


		setZoomAbility( newCanZoomOut, newCanZoomIn ) {
			canZoomOut.value = newCanZoomOut;
			canZoomIn.value = newCanZoomIn;
		},
    };
} );
