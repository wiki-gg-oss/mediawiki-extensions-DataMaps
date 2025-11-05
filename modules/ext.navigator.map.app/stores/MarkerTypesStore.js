const
    { ref } = require( 'vue' ),
    { defineStore } = require( 'pinia' );

module.exports = defineStore( 'markerTypes', () => {
    return {
        markerTypes: [
    		{
    			"id": "lostsector",
    			"name": "Lost Sectors"
    		},
    		{
    			"id": "visions",
    			"name": "Visions of the Traveler",
    			"description": "Collect all to unlock the Khvostov-1 exotic auto rifle."
    		},
    		{
    			"name": "Lost Encryption Bits",
    			"description": "Gather by opening Region Chests and searching through Rubble Piles in the Cysts to unlock the Khvostov legendary auto rifle.",
    			"include": [
    				{
    					"id": "leb-regionchest",
    					"name": "Region Chests",
    					"progressTracking": true
    				},
    				{
    					"id": "leb-rubble",
    					"name": "Rubble Piles",
    					"progressTracking": true
    				}
    			]
    		}
    	],
    };
} );
