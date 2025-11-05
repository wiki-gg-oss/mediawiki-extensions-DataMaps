const
    Leaflet = require( './LeafletApi.js' );


module.exports = class LeafletViewportManager {
    #mountTargetElement;
    #map;


    constructor( mountTargetElement ) {
        this.#mountTargetElement = mountTargetElement;
    }


    async enable() {
        await Leaflet.resolve();

        this.#map = new Leaflet.Map( this.#mountTargetElement, {
            center: [ 50, 50 ],
            zoom: 1,
            zoomControl: false,
            attributionControl: false,
            preferCanvas: true,
            renderer: new Leaflet.Canvas( {} ),
        } );

        this.#map.addLayer( new Leaflet.Rectangle(
            [ [ 25, 25 ], [ 75, 75 ] ],
            { color: '#00f', weight: 1 } ) );
    }
};
