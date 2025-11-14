const
    { ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );


module.exports = defineStore( 'markerTypes', () => {
    const
        markerTypes = ref( [] ),
        markerCountByType = ref( {} ),
        markerCountByTypeTickedOff = ref( {} );


    return {
        markerTypes,
        markerCountByType,
        markerCountByTypeTickedOff,

        
        setMetadata( values ) {
            markerTypes.value = values;
        },
    };
} );
