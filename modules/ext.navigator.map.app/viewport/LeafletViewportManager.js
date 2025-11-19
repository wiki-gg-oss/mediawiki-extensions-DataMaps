const
    Leaflet = require( './LeafletApi.js' );


module.exports = class LeafletViewportManager {
    #mountTargetElement;
    #map;
    #readinessPromise;
    #readinessPromiseResolve;
    #isUpdating = false;


    constructor( mountTargetElement ) {
        this.#mountTargetElement = mountTargetElement;
        this.#map = null;
        this.#readinessPromise = new Promise( resolve => ( this.#readinessPromiseResolve = resolve ) );
    }


    getLeafletHandle() {
        return this.#map;
    }


    async waitUntilReady() {
        return this.#readinessPromise;
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
        
        this.#readinessPromiseResolve();
    }


    async update() {
        if ( this.#isUpdating ) {
            throw new Error( 'Viewport is already in the process of being updated' );
        }

        console.debug( `[Navigator] Starting a viewport update, locking it...` );

        this.#isUpdating = true;

        await this.#readinessPromise;

        this.#isUpdating = false;
    }
};
