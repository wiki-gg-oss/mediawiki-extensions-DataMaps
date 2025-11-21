const
    Leaflet = require( './LeafletApi.js' ),
    DEFAULT_LAYER_FACTORIES = require( './defaultLayerFactories.js' );


module.exports = class LeafletViewportManager {
    #mountTargetElement;
    #map;
    #featureTree;
    #readinessPromise;
    #readinessPromiseResolve;
    #isUpdating = false;
    #layerFactories;
    #featureLayerMap;
    #tryFeatureFn;
    #layerClickFn;


    constructor( mountTargetElement, featureTree ) {
        this.#mountTargetElement = mountTargetElement;
        this.#featureTree = featureTree;
        this.#map = null;
        this.#readinessPromise = new Promise( resolve => ( this.#readinessPromiseResolve = resolve ) );
        this.#featureLayerMap = {};
        this.#layerFactories = Object.assign( {}, DEFAULT_LAYER_FACTORIES );
        this.#tryFeatureFn = this.#tryFactory.bind( this );
        this.#layerClickFn = this.#createFeatureEventDispatcher( 'onClick' );
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
        const layer = this.#tryFactory( feature.constructor.name, feature );
        if ( !layer ) {
            console.debug( `[Navigator] Failed to construct a Leaflet layer for feature type ${feature.constructor.name}` );
            return null;
        }

        layer._ng_feature_id = feature.getId();

        if ( 'onClick' in feature ) {
            layer.on( {
                click: this.#layerClickFn,
            } );
        }

        this.#map.addLayer( layer );
        this.#featureLayerMap[ feature.getId() ] = layer;

        return layer;
    }


    #tryFactory( factoryName, feature ) {
        const factory = this.#layerFactories[ factoryName ];
        if ( !factory ) {
            console.debug( `[Navigator] Missing Leaflet layer factory with name of ${factoryName}` );
            return null;
        }
        return factory( feature, this.#tryFeatureFn );
    }


    #createFeatureEventDispatcher( methodName ) {
        return event => {
            const feature = this.#featureTree.getFeatureById( event.target._ng_feature_id );
            console.debug( `[Navigator] Dispatching event '${methodName}' to feature ${feature.getId()}:`, feature,
                event );

            const handled = feature[ methodName ].call( feature, {
                virtualPos: [ event.latlng.lng, event.latlng.lat ],
                physicalPos: [ event.containerPoint.x, event.containerPoint.y ],
                domEvent: event.originalEvent,
            } );
            if ( handled ) {
                Leaflet.DomEvent.stop( event );
            }
        };
    }
};
