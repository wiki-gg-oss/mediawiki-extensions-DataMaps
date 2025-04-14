/** @typedef {import( './DataMap.js' )} DataMap */
/** @typedef {import( './Background.js' )} Background */
const
    { MapFlags, CRSOrigin } = require( './enums.js' ),
    Controls = require( './controls.js' ),
    DebugControl = require( './DebugControl.js' ),
    EventEmitter = require( './EventEmitter.js' ),
    Util = require( './Util.js' ),
    { createDomElement } = Util;
/** @type {!LeafletModule} */
// @ts-ignore: Lazily initialised, this'd be ideally solved with post-fix assertions but we're in JS land.
let Leaflet = null;


/**
 * @extends EventEmitter<DataMaps.EventHandling.MapListenerSignatures>
 */
class Viewport extends EventEmitter {
    /**
     * @param {DataMap} map
     * @param {DataMaps.Configuration.Map} config
     */
    constructor( map, config ) {
        super();
        // Make sure Leaflet is available in the module. Viewports should only be constructed after it is loaded.
        if ( Leaflet === null ) {
            Leaflet = Util.getLeaflet();
        }

        /**
         * Owning map.
         *
         * @type {DataMap}
         */
        this.map = map;

        // Specify the coordinate reference system and initialise the renderer
        const leafletConfig = this._makeLeafletConfig( config );
        leafletConfig.crs = ( {
            [ CRSOrigin.BottomLeft ]: Leaflet.Ark.BottomLeftPixelCrs,
            [ CRSOrigin.TopLeft ]: Leaflet.Ark.TopLeftPixelCrs,
        } )[ this.map.crs.origin ];
        leafletConfig.renderer = new Leaflet.Canvas( leafletConfig.rendererSettings );

        /**
         * @private
         * @type {HTMLElement}
         */
        this._containerElement = /** @type {HTMLElement} */ ( Util.getNonNull( this.map.rootElement.querySelector(
            '.ext-datamaps-container-leaflet' ) ) );

        // Set a custom backdrop colour if one is present in the configuration
        if ( config.backdrop ) {
            this._containerElement.style.backgroundColor = config.backdrop;
        }

        /**
         * Leaflet instance.
         *
         * @private
         * @type {LeafletModule.Map}
         */
        this._leaflet = new Leaflet.Map( this._containerElement, leafletConfig );

        /**
         * Stash for background layers.
         *
         * @private
         * @type {LeafletModule.Layer[]?}
         */
        this._backgroundStash = null;

        /**
         * Anchor element for the map's legend. This is always available as some controls or gadgets may depend on it.
         */
        this.legendAnchor = createDomElement( 'div', {
            classes: [ 'ext-datamaps-container-legend' ],
            prependTo: this.resolveControlAnchor( Viewport.anchors._realTopLeft )
        } );

        // Create control container in top left corner
        createDomElement( 'div', {
            appendTo: this.resolveControlAnchor( Viewport.anchors._realTopLeft )
        } );
        // Create inline control containers (Viewport.anchors.topLeftInline and Viewport.anchors.topRightInline)
        for ( const anchor of [ Viewport.anchors.topLeft, Viewport.anchors.topRight ] ) {
            createDomElement( 'div', {
                classes: [ 'ext-datamaps-control-group' ],
                prependTo: this.resolveControlAnchor( anchor )
            } );
        }

        // Set map's reference to ourselves here, as controls may need it for initialisation
        this.map.viewport = this;

        // Install the interaction rejection controller
        const useSleepInteractions = this.map.checkFeatureFlag( MapFlags.SleepingInteractions )
            || this.map.checkFeatureFlag( MapFlags.VisualEditor );
        this._leaflet.addHandler( 'interactionControl', Leaflet.Ark[ useSleepInteractions ? 'SleepInteractionControl'
            : 'KeybindInteractionControl' ] );

        /**
         * Coordinates display control.
         *
         * @type {Controls.Coordinates?}
         */
        this.coordinatesControl = null;
        if ( this.map.checkFeatureFlag( MapFlags.ShowCoordinates ) || this.map.checkFeatureFlag( MapFlags.VisualEditor ) ) {
            this.coordinatesControl = this.addControl( Viewport.anchors.bottomLeft, new Controls.Coordinates(
                this.map ) );
        }
        /**
         * Backgrounds dropdown.
         *
         * @type {Controls.BackgroundSwitcher?}
         */
        this.backgroundsControl = null;
        if ( config.backgrounds.length > 1 ) {
            this.backgroundsControl = this.addControl( Viewport.anchors.legend, new Controls.BackgroundSwitcher(
                this.map ) );
        }
        /**
         * Zoom controls
         *
         * @type {Controls.ZoomControls?}
         */
        this.zoomControls = this.addControl( Viewport.anchors.bottomRight, new Controls.ZoomControls( this.map ) );
        /**
         * Reset and centre view controls.
         *
         * @type {Controls.ViewControls?}
         */
        this.viewControls = this.addControl( Viewport.anchors.bottomRight, new Controls.ViewControls( this.map ) );
        /**
         * Fullscreen mode switcher control.
         *
         * @type {Controls.ToggleFullscreen?}
         */
        this.fullscreenToggle = null;
        if ( this.map.checkFeatureFlag( MapFlags.AllowFullscreen ) ) {
            this.fullscreenToggle = this.addControl( Viewport.anchors.topRight, new Controls.ToggleFullscreen(
                this.map ) );
        }
        /**
         * Edit button shown only to registered users.
         *
         * @type {Controls.EditButton?}
         */
        this.editControl = null;
        if (
            !this.map.checkFeatureFlag( MapFlags.IsPreview ) && ( mw.config.get( 'wgUserName' ) !== null
                || Util.canAnonsEdit )
        ) {
            this.editControl = this.addControl( Viewport.anchors.topRight, new Controls.EditButton( this.map ) );
        }
        /**
         * Control that'll host marker search, if it is enabled.
         *
         * @type {Controls.SearchHost?}
         */
        this.searchHost = null;
        if ( !this.map.checkFeatureFlag( MapFlags.VisualEditor ) && this.map.checkFeatureFlag( MapFlags.Search ) ) {
            this.searchHost = this.addControl( Viewport.anchors.legend, new Controls.SearchHost( this.map ) );
        }
        /**
         * @type {DebugControl?}
         */
        this.debugControl = null;
        if ( mw.config.get( 'debug' ) ) {
            this.debugControl = this.addControl( Viewport.anchors.bottomLeft, new DebugControl( this.map ) );
        }

        this._updateBackgroundLayers(
            this.map.getCurrentBackgroundIndex(),
            this.map.backgrounds[ this.map.getCurrentBackgroundIndex() ]
        );
        this.refreshViewProperties();
        this._doInitialViewReset();
        this.updateScaling();

        // Update bounds whenever background is changed or marker set is updated
        this.map.on( 'chunkStreamingDone', this.refreshViewProperties, this );
        this.map.on( 'backgroundChange', this.refreshViewProperties, this );
        this.map.on( 'markerVisibilityUpdate', this.refreshViewProperties, this );
        // Switch background layers whenever current background is changed
        this.map.on( 'backgroundChange', this._updateBackgroundLayers, this );
        // Toggle interaction control off when entering fullscreen mode
        this.map.on( 'fullscreenSwitched', this._onFullscreenToggled, this );
        // Recalculate marker sizes when zoom ends
        this._leaflet.on( 'zoom', this.updateScaling, this );
    }


    /**
     * @private
     * @param {DataMaps.Configuration.Map} mapConfig
     * @return {LeafletModule.MapOptions}
     */
    _makeLeafletConfig( mapConfig ) {
        // Ensure the `zoom` section of the config is initialised
        if ( !mapConfig.zoom ) {
            mapConfig.zoom = {
                min: -16,
                lock: this.map.checkFeatureFlag( MapFlags.DisableZoom ),
                max: 6,
                auto: true,
                scrollSpeed: 1,
            };
        }

        // If zoom is locked, disable all zoom controls
        if ( mapConfig.zoom.lock ) {
            mapConfig.leafletSettings = $.extend( {
                zoomControl: false,
                boxZoom: false,
                doubleClickZoom: false,
                scrollWheelZoom: false,
                touchZoom: false
            }, mapConfig.leafletSettings || {} );
        }

        // Prepare settings for Leaflet
        /** @type {LeafletModule.MapOptions} */
        const result = $.extend( true, /** @type {LeafletModule.IPublicMapOptions} */ ( {
            // Boundaries
            center: [ 50, 50 ],
            maxBounds: [ [ -100, -100 ], [ 200, 200 ] ],
            maxBoundsViscosity: 0.7,
            // Zoom settings
            zoomSnap: 0,
            zoomDelta: 0.25,
            wheelPxPerZoomLevel: 90 / ( mapConfig.zoom.scrollSpeed || 1 ),
            maxZoom: mapConfig.zoom.max,
            minZoom: mapConfig.zoom.min,
            // Zoom animations cause some awkward locking as Leaflet waits for the animation to finish before processing more
            // zoom requests.
            // However, before v0.15.0 they had to be enabled to mitigate vector drift, which has been since fixed by Leaflet's
            // PR#8794. Before that merge request we had explicitly called zoom in desktop handlers with animations turned off.
            zoomAnimation: false,
            markerZoomAnimation: true,
            // Do not allow pinch-zooming to surpass max zoom even temporarily. This seems to cause a mispositioning.
            bounceAtZoomLimits: false,
            // Pan settings
            inertia: false,
            // Canvas renderer settings - using canvas for performance with padding of 1/3rd (to draw some more markers outside
            // of view for panning UX)
            rendererSettings: {
                padding: 1 / 3
            },

            // Non-standard extended options
            // Automatic minimum zoom calculations
            autoMinZoom: mapConfig.zoom.auto,
            // TODO: merge into minZoom
            autoMinZoomAbsolute: mapConfig.zoom.min,
            // Zoom-based marker scaling
            shouldScaleMarkers: true,
            markerZoomScaleFactor: 1.8,
            // Zoom control
            zoomControlOptions: {
                position: 'bottomright',
                zoomInTitle: mw.msg( 'datamap-control-zoom-in' ),
                zoomOutTitle: mw.msg( 'datamap-control-zoom-out' )
            },
            // Allow rendering icon markers on a canvas
            allowIconsOnCanvas: true,

            // Enable bundled interaction rejection control
            interactionControl: true
        } ), mapConfig.leafletSettings );

        return result;
    }


    /**
     * @return {LeafletModule.Map}
     */
    getLeafletMap() {
        return this._leaflet;
    }


    /**
     * Updates map options regarding our custom marker scaling behaviour.
     */
    updateScaling() {
        const
            minimumScale = 0.05,
            zoomNormalisedVal = this._leaflet.getZoom() - this._leaflet.options.minZoom,
            zoomNormalisedMax = this._leaflet.options.maxZoom - this._leaflet.options.minZoom,
            zoomPercent = Math.round( zoomNormalisedVal / zoomNormalisedMax * 100 ) / 100;
        this._leaflet.options.vecMarkerScale = Math.max( minimumScale, zoomPercent * Viewport.VECTOR_ZOOM_SCALING_MAX );
        this._leaflet.options.iconMarkerScale = Math.max( minimumScale, zoomPercent * Viewport.ICON_ZOOM_SCALING_MAX );
    }


    /**
     * Updates Leaflet's max view bounds to padded content bounds in current state. This is usually done after a data chunk is
     * streamed in, and is fairly expensive.
     */
    refreshViewProperties() {
        this._leaflet.setMaxBounds( this.map.getPaddedContentBounds( true ) );

        if ( this._leaflet.options.autoMinZoom ) {
            this._leaflet.options.minZoom = this._leaflet.options.autoMinZoomAbsolute;
            const computedZoom = this._leaflet.getBoundsZoom( this.map.getPaddedContentBounds( false, 0.6 ), false, [ 0, 0 ] );
            this._leaflet.setMinZoom( computedZoom );
            // TODO: this should recalculate popup zoom?
        }
    }


    /**
     * @private
     * @param {number} _
     * @param {Background} background
     */
    _updateBackgroundLayers( _, background ) {
        if ( this._backgroundStash !== null ) {
            for ( const layer of this._backgroundStash ) {
                layer.remove();
            }
        }

        this._backgroundStash = background.constructLayers();
        for ( const layer of this._backgroundStash ) {
            this._leaflet.addLayer( layer );
            layer.bringToBack();
        }
    }


    /**
     * @private
     * @param {boolean} value
     */
    _onFullscreenToggled( value ) {
        if ( this._leaflet.interactionControl ) {
            this._leaflet.interactionControl[ value ? 'disable' : 'enable' ]();
        }
    }


    focus() {
        this._leaflet.getContainer().focus();
    }


    /**
     * Returns zoom level for automatically opened marker popups.
     *
     * @return {number}
     */
    getPopupZoom() {
        if ( this._leaflet.options.uriPopupZoom ) {
            return this._leaflet.options.uriPopupZoom;
        }

        const
            min = this._leaflet.options.minZoom,
            max = this._leaflet.options.maxZoom,
            result = Math.pow( ( max - min + 3.5 ), 0.75 ) / 5 * 3 + min;
        return result;
    }


    /**
     * Flies over to a marker, putting it in the viewport's centre at a reasonable zoom level.
     *
     * @param {LeafletModule.AnyMarker} leafletMarker
     * @param {boolean} [doInstant=false]
     */
    flyToMarker( leafletMarker, doInstant ) {
        this._leaflet.flyTo(
            leafletMarker.getLatLng(),
            this.getPopupZoom(),
            {
                animate: !doInstant,
            }
        );
    }


    zoomNSteps( steps ) {
		if ( steps > 0 && this._leaflet._zoom < this._leaflet.getMaxZoom() ) {
            this._leaflet.zoomIn( this._leaflet.options.zoomDelta * steps );
		}
		if ( steps < 0 && this._leaflet._zoom > this._leaflet.getMinZoom() ) {
            this._leaflet.zoomOut( this._leaflet.options.zoomDelta * -steps );
		}
    }


    /**
     * Snaps the viewport to the content. Zooms out entirely on a double click.
     */
    resetView() {
        this._leaflet.setZoom( this._leaflet.options.minZoom ).fitBounds( this.map.getCurrentContentBounds(), {
            paddingTopLeft: [ this.map.getMapOffsetWidth(), 0 ]
        } );
    }


    /**
     * Resets the view as part of initial setup.
     *
     * If our container height is 0 (due to a collapsible), this effectively fails and results in a weird state. To
     * work that around, let's listen for a resize event, invalidate Leaflet's tracked size, and reset the view then.
     *
     * @private
     */
    _doInitialViewReset() {
        this.resetView();

        const leafletContainer = this._leaflet.getContainer();
        if ( leafletContainer.clientHeight === 0 ) {
            const observer = new ResizeObserver( entries => {
                if ( entries[ 0 ].contentRect.height > 0 ) {
                    this._leaflet.invalidateSize();
                    this.refreshViewProperties();
                    this.resetView();
                    observer.disconnect();
                }
            } );
            observer.observe( leafletContainer );
        }
    }


    /**
     * Moves the viewport to the centre of the content bounds without affecting zoom.
     */
    centreView() {
        this._leaflet.setView( this.map.getCurrentContentBounds().getCenter() );
    }


    /**
     * @param {Viewport.anchors[ keyof Viewport.anchors ]} anchor
     * @return {HTMLElement}
     */
    resolveControlAnchor( anchor ) {
        return /** @type {HTMLElement} */ ( Util.getNonNull( this._containerElement.querySelector(
            `.leaflet-control-container ${anchor}` ) ) );
    }


    /**
     * Adds a custom control to Leaflet's container.
     *
     * Requires the Leaflet map to be initialised.
     *
     * @template {HTMLElement|Controls.MapControl} T
     * @param {Viewport.anchors[ keyof Viewport.anchors ]} anchor Anchor selector.
     * @param {T} control Control to add.
     * @param {boolean} [prepend] Whether to add the control to the beginning of the anchor.
     * @return {T} {@link control} for chaining.
     */
    addControl( anchor, control, prepend ) {
        const controlElement = /** @type {HTMLElement} */ ( control instanceof Controls.MapControl ? control.element : control ),
            anchorElement = this.resolveControlAnchor( anchor ),
            beforeInlineGroup = prepend && anchorElement.querySelector( ':scope > .ext-datamaps-control-group' );
        if ( beforeInlineGroup ) {
            anchorElement.insertBefore( controlElement, beforeInlineGroup.nextSibling );
        } else {
            anchorElement[ prepend ? 'prepend' : 'appendChild' ]( controlElement );
        }
        Util.preventMapInterference( controlElement );
        return control;
    }
}


/**
 * @constant
 */
Viewport.anchors = Object.freeze( {
    /** @package */
    _realTopLeft: '.leaflet-top.leaflet-left',
    /** @package */
    _none: '',

    legend: '.leaflet-top.leaflet-left > .ext-datamaps-container-legend',
    topLeft: '.leaflet-top.leaflet-left > :last-child',
    topRight: '.leaflet-top.leaflet-right',
    bottomLeft: '.leaflet-bottom.leaflet-left',
    bottomRight: '.leaflet-bottom.leaflet-right',

    topRightInline: '.leaflet-top.leaflet-right > .ext-datamaps-control-group',
    topLeftInline: '.leaflet-top.leaflet-left > :last-child > .ext-datamaps-control-group',

    veToolBar: '.ext-datamaps-control-ve-toolbar > .ext-datamaps-control-ve-toolbar-controls'
} );


/**
 * Max zoom-caused scale value for vector markers.
 *
 * @constant
 * @type {number}
 */
Viewport.VECTOR_ZOOM_SCALING_MAX = 1;


/**
 * Max zoom-caused scale value for icon markers.
 *
 * @constant
 * @type {number}
 */
Viewport.ICON_ZOOM_SCALING_MAX = 1;


module.exports = Viewport;
