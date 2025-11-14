const
    { ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );


module.exports = defineStore( 'appSettings', () => {
	const subtitleHtml = ref( '' );

    return {
        subtitleHtml,
		setSubtitleHtml( value ) { subtitleHtml.value = value; }
    };
} );
