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
    			"description": "Collect all to unlock the Khvostov-1 exotic auto rifle.",
                "progressTracking": true,
    		},
    		{
                "id": "leb",
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
        markerCountByType: {
            lostsector: 3,
            visions: 8,
            leb: 9+6,
            'leb-regionchest': 9,
            'leb-rubble': 6,
        },
        markerCountByTypeTickedOff: {
            visions: 4,
            leb: 3+4,
            'leb-regionchest': 4,
            'leb-rubble': 3,
        },
    };
} );
