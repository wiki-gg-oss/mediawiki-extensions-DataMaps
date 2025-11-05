const
    { computed, ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );


module.exports = defineStore( 'viewportState', () => {
	const
		zoomMax = ref( 0 ),
		zoomMin = ref( 0 ),
		zoomCurrent = ref( 0 );


    return {
		zoomMax,
		zoomMin,
		zoomCurrent,
		canZoomOut: computed( () => zoomCurrent.value > zoomMin.value ),
		canZoomIn: computed( () => zoomCurrent.value < zoomMax.value ),


		setZoomRange( max, min ) {
			zoomMax.value = max;
			zoomMin.value = min;
		},


		setCurrentZoom( value ) {
			zoomCurrent.value = value;
		},
    };
} );
