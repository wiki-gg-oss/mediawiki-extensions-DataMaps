const
    { computed, ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );


module.exports = defineStore( 'popoverState', () => {
	const
		attachmentLocationX = ref( null ),
		attachmentLocationY = ref( null ),
		containerProjectionFn = ref( null ),
		dataObject = ref( null );


    return {
		attachmentLocationX,
		attachmentLocationY,
		containerProjectionFn,
		dataObject,
		isVisible: computed( () => ( containerProjectionFn.value !== null && attachmentLocationX.value !== null ) ),


		setContainerProjectionFn( value ) {
			containerProjectionFn.value = value;
		},


		activate( locationVec, data ) {
			attachmentLocationX.value = locationVec[ 0 ];
			attachmentLocationY.value = locationVec[ 1 ];
			dataObject.value = data;
		},


		deactivate() {
			attachmentLocationX.value = null;
			attachmentLocationY.value = null;
			dataObject.value = null;
		},
    };
} );
