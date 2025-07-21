/** @typedef {import( '../core/EventEmitter.js' ).EventHandlerRef & { flag: number }} MapNotificationReceiver */
const
    {
        DataMap,
        MapFlags,
        EventEmitter,
        Util
    } = require( 'ext.datamaps.core' ),
    /** @type {InstanceType<DataMap>[]} */ initialisedMaps = [],
    /** @type {MapNotificationReceiver[]} */ toNotifyOnInit = [],
    /** @type {MapNotificationReceiver[]} */ toNotifyOnDestroy = [],
    MAP_CONTAINER_SELECTOR = '.ext-datamaps-container[data-mw-datamaps="map"][data-datamap-id]',
    INLINE_MARKERS_SELECTOR = ':scope > script[type="application/datamap+json"][data-purpose="markers"]',
    INLINE_CONFIG_SELECTOR = ':scope > script[type="application/datamap+json"][data-purpose="config"]';


/**
 * Looks for a configuration element and parses its contents as JSON.
 *
 * @param {HTMLElement} rootElement Root element of the map.
 * @return {DataMaps.Configuration.Map} Configuration object.
 */
function getConfig( rootElement ) {
    let config;
    const dataElement = rootElement.querySelector( INLINE_CONFIG_SELECTOR );
    if ( dataElement !== null ) {
        config = JSON.parse( /** @type {HTMLElement} */ ( dataElement ).textContent );
    }
    return config;
}


/**
 * @param {MapNotificationReceiver} handler
 * @param {InstanceType<DataMap>} map
 */
function invokeHandler( handler, map ) {
    const isVe = map.checkFeatureFlag( MapFlags.VisualEditor );
    if ( ( isVe && handler.flag & mw.dataMaps.IS_COMPATIBLE_WITH_VISUAL_EDITOR )
        || ( !isVe && handler.flag & mw.dataMaps.IS_COMPATIBLE_WITH_NORMAL ) ) {
        EventEmitter.invokeHandler( handler, [ map ] );
    }
}


/**
 * @param {MapNotificationReceiver[]} list
 * @param {( map: InstanceType<DataMap> ) => void} callback
 * @param {any} [context]
 * @param {number} [filterFlags=mw.dataMaps.IS_COMPATIBLE_WITH_NORMAL]
 * @return {MapNotificationReceiver}
 */
function appendHandler( list, callback, context, filterFlags ) {
    const handler = {
        method: callback,
        context,
        flag: filterFlags || mw.dataMaps.IS_COMPATIBLE_WITH_NORMAL
    };
    list.push( handler );
    return handler;
}


/**
 * @global
 */
mw.dataMaps = {
    /**
     * @constant
     */
    IS_COMPATIBLE_WITH_NORMAL: 1,
    /**
     * @constant
     */
    IS_COMPATIBLE_WITH_VISUAL_EDITOR: 2,


    /**
     * @param {number} id
     * @param {HTMLElement} rootElement
     * @param {DataMaps.Configuration.Map} config
     * @return {InstanceType<DataMap>}
     */
    initialiseMapWithConfig( id, rootElement, config ) {
        const embedConfig = JSON.parse( rootElement.dataset.mwDatamapsConfigure || '{}' );

        // Set the map up
        const map = new DataMap( id, rootElement, config, embedConfig );

        // Push onto internal tracking list
        initialisedMaps.push( map );

        // Set up a handler for linked events (broadcast to other maps)
        map.on( 'sendLinkedEvent', event => {
            event.map = map;
            for ( const otherMap of initialisedMaps ) {
                if ( map !== otherMap ) {
                    otherMap.fire( 'linkedEvent', event );
                }
            }
        } );
        // Pass the deactivation event to gadgets
        map.on( 'deactivate', () => {
            delete initialisedMaps[ initialisedMaps.indexOf( map ) ];
            for ( const handler of toNotifyOnDestroy ) {
                invokeHandler( handler, map );
            }
        } );

        // Notify external scripts waiting on maps
        for ( const handler of toNotifyOnInit ) {
            invokeHandler( handler, map );
        }

        // Request markers from the API
        if ( map.config.version ) {
            map.setStatusOverlay( 'info', mw.msg( 'datamap-loading-data' ), true );
            map.streaming.loadSequential()
                .then( () => {
                    // Wait for Leaflet to be done loading before taking the overlay off
                    map.on( 'leafletLoadedLate', () => map.setStatusOverlay( null ) );
                } )
                .catch( () => map.setStatusOverlay( 'error', mw.msg( 'datamap-error-dataload' ), false ) );
        } else {
            // No page to request markers from, hide the status message
            map.setStatusOverlay( null );

            // Check if any inline data has been included. This is used for edit previews.
            const dataNode = /** @type {HTMLElement?} */ ( rootElement.querySelector( INLINE_MARKERS_SELECTOR ) );
            if ( dataNode ) {
                map.on( 'leafletLoaded', () => {
                    const data = JSON.parse( dataNode.textContent );
                    map.streaming.instantiateMarkers( data );
                    map.fireMemorised( 'chunkStreamingDone' );
                } );
            }
        }

        return map;
    },

    
    /**
     * @param {number} id
     * @param {HTMLElement} rootElement
     * @param {DataMaps.Configuration.Map} config
     */
    lazyInitialiseMapWithConfig( id, rootElement, config ) {
        const observer = new IntersectionObserver(
            ( entries, observer ) => {
                if ( entries[ 0 ].isIntersecting ) {
                    mw.dataMaps.initialiseMapWithConfig( id, rootElement, config );
                    observer.disconnect();
                }
            },
            {
                threshold: 0.03,
            }
        );
        observer.observe( rootElement );
    },


    /**
     * @param {( map: InstanceType<DataMap> ) => void} callback
     * @param {any} [context]
     * @param {number} [filterFlags=mw.dataMaps.IS_COMPATIBLE_WITH_NORMAL]
     */
    onMapInitialised( callback, context, filterFlags ) {
        const handler = appendHandler( toNotifyOnInit, callback, context, filterFlags );
        for ( const map of initialisedMaps ) {
            invokeHandler( handler, map );
        }
    },


    /**
     * @param {( map: InstanceType<DataMap> ) => void} callback
     * @param {any} [context]
     * @param {number} [filterFlags=mw.dataMaps.IS_COMPATIBLE_WITH_NORMAL]
     */
    onMapDeactivated( callback, context, filterFlags ) {
        appendHandler( toNotifyOnDestroy, callback, context, filterFlags );
    },


    /**
     * @param {( map: InstanceType<DataMap> ) => void} callback
     * @param {any} [context]
     */
    registerMapAddedHandler( callback, context ) {
        this.onMapInitialised( callback, context );
    }
};


// Begin initialisation once the document is loaded
mw.hook( 'wikipage.content' ).add( $content => {
    // Run initialisation for every map, followed by events for gadgets to listen to
    const initMethod = Util.isMapLazyLoadingEnabled ? 'lazyInitialiseMapWithConfig' : 'initialiseMapWithConfig';
    for ( const rootElement of /** @type {HTMLElement[]} */ ( $content.find( MAP_CONTAINER_SELECTOR ) ) ) {
        const id = parseInt( Util.getNonNull( rootElement.dataset.datamapId ) ),
            config = getConfig( rootElement );
        if ( config ) {
            mw.dataMaps[ initMethod ]( id, rootElement, config );
        }
    }
} );
