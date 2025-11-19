const
    Leaflet = require( './LeafletApi.js' );


const DEFAULT_LAYER_FACTORIES = {
    BackgroundImageFeature( f ) {
        const
            [ swX, swY ] = f.getLocation(),
            w = f.getWidth(),
            h = f.getHeight(),
            neX = swX + w,
            neY = swY + h;
        return new Leaflet.ImageOverlay(
            f.getUrl(),
            [ [ neY, neX ], [ swY, swX ] ]
        );
    },
    TextFeature( f ) {
        const
            [ swX, swY ] = f.getLocation();
        // TODO: need an actual text overlay implementation
        const retval = new Leaflet.DivOverlay( [ swY, swX ], {
            pane: 'markerPane',
        } );
        retval._initLayout = function () {
            this._container = document.createElement( 'div' );
            const textContainer = document.createElement( 'span' );
            textContainer.style.position = 'absolute';
            textContainer.style.transform = 'translate(-50%) translateY(-50%)';
            textContainer.style.whiteSpace = 'nowrap';
            textContainer.innerHTML = f.getHtml();
            this._container.append( textContainer );
        };
        retval._updateLayout = function () {
            this._container.style.opacity = 1;
        };
        retval._adjustPan = () => {};
        return retval;
    },
};


module.exports = class LeafletViewportManager {
    #mountTargetElement;
    #map;
    #featureTree;
    #readinessPromise;
    #readinessPromiseResolve;
    #isUpdating = false;
    #layerFactories;
    #featureLayerMap;


    constructor( mountTargetElement, featureTree ) {
        this.#mountTargetElement = mountTargetElement;
        this.#featureTree = featureTree;
        this.#map = null;
        this.#readinessPromise = new Promise( resolve => ( this.#readinessPromiseResolve = resolve ) );
        this.#featureLayerMap = {};
        this.#layerFactories = Object.assign( {}, DEFAULT_LAYER_FACTORIES );
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
            zoom: -3,
            minZoom: -50,
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

        this.#updateInternal();

        this.#isUpdating = false;

        console.debug( `[Navigator] Viewport update complete` );
    }


    #updateInternal() {
        const dirtyFeatures = this.#featureTree.getDirtyFeatureSet();
        for ( const feature of dirtyFeatures ) {
            let layer = this.#featureLayerMap[ feature.getId() ];
            if ( !layer ) {
                layer = this.#initFeatureLayer( feature );
            }
            if ( layer === null ) {
                // Layer initialisation failed, probably because there's no factory
                continue;
            }

            // TODO: update the layer... somehow
        }
    }


    #initFeatureLayer( feature ) {
        const factory = this.#layerFactories[ feature.constructor.name ];
        if ( !factory ) {
            console.debug( `[Navigator] Missing Leaflet layer factory for feature type ${feature.constructor.name}` );
            return null;
        }

        const layer = factory( feature );
        this.#map.addLayer( layer );

        this.#featureLayerMap[ feature.getId() ] = layer;
        return layer;
    }
};
