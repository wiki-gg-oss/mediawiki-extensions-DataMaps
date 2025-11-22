const
    { ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );


module.exports = defineStore( 'appSettings', () => {
	const
		isSourceCodeTitleAvailable = ref( false ),
		subtitleHtml = ref( '' );

    return {
		isSourceCodeTitleAvailable,
		setSourceCodeTitleAvailable( value ) { isSourceCodeTitleAvailable.value = value; },
        subtitleHtml,
		setSubtitleHtml( value ) { subtitleHtml.value = value; }
    };
} );
