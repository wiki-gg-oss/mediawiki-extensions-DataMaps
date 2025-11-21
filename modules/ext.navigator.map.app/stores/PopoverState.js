const
    { computed, ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );


module.exports = defineStore( 'popoverState', () => {
	const
		attachmentLocationX = ref( null ),
		attachmentLocationY = ref( null ),
		dataObject = ref( null );


    return {
		attachmentLocationX,
		attachmentLocationY,
		dataObject,
		isVisible: computed( () => attachmentLocationX.value === null ),


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
