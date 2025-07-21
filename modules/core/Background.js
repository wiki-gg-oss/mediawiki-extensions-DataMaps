/** @typedef {import( './DataMap.js' )} DataMap */
const
    { CRSOrigin } = require( './enums.js' ),
    EventEmitter = require( './EventEmitter.js' ),
    Util = require( './Util.js' );


/**
 * @extends EventEmitter<DataMaps.EventHandling.MapListenerSignatures>
 */
class Background extends EventEmitter {
    /**
     * @param {DataMap} map
     * @param {DataMaps.Configuration.Background} config
     * @param {number} index
     */
    constructor( map, config, index ) {
        super();

        /**
         * Owning map.
         *
         * @type {DataMap}
         */
        this.map = map;

        /**
         * Display name.
         *
         * @type {string}
         */
        this.displayName = config.name || mw.msg( 'datamap-background-name-fallback' );

        /**
         * Associated marker category.
         *
         * @type {string}
         */
        this.categoryId = config.layer || `${index}`;

        /**
         * Image bounds.
         *
         * @type {LeafletModule.LatLngBoundsTuple}
         */
        // TODO: backend should perform this fallback
        this.bounds = config.at || this.map.config.crs;

        /**
         * Image URL.
         *
         * @type {string?}
         */
        this.image = config.image || null;

        /**
         * Tile offset
         *
         * @type {array?}
         */
        this.tileOffset = config.tileOffset || null;

        /**
         * Tile size
         *
         * @private
         * @type {array?}
         */
        this._physicalTileSize = config.tileSize || null;

        /**
         * Whether the browser should scale this image in pixel-art mode.
         *
         * @type {boolean}
         */
        this.isPixelated = config.pixelated || false;

        /**
         * If true, 0.51px will be added to the image in the viewport to alleviate gaps.
         *
         * @private
         * @type {boolean}
         */
        this._needsFractionalSizing = config.aa || false;

        /**
         * Attached overlays.
         *
         * @private
         * @type {DataMaps.Configuration.BackgroundOverlay[]?}
         */
        this._overlayConfigs = config.overlays || null;

        /**
         * Attached overlays.
         *
         * @private
         * @type {DataMaps.Configuration.BackgroundOverlay[]?}
         */
        this._tilesConfigs = config.tiles || null;
    }


    constructLayers() {
        const results = [];
        if ( this.image ) {
            results.push( this._constructMainLayer() );
        }
        if ( this._tilesConfigs ) {
            results.push( this._constructTiles( this._tilesConfigs ) );
        }
        if ( this._overlayConfigs ) {
            for ( const config of this._overlayConfigs ) {
                results.push( this._constructOverlay( config ) );
            }
        }
        return results;
    }


    /**
     * @private
     * @return {LeafletModule.ImageOverlay}
     */
    _constructMainLayer() {
        const Leaflet = Util.getLeaflet();
        return new Leaflet.ImageOverlay(
            Util.getNonNull( this.image ),
            this.map.crs.fromBox( this.bounds ),
            {
                className: this.isPixelated ? 'ext-datamaps-pixelated-image' : undefined,
                decoding: 'async',
                angle: this.map.crs.rotation * 180 / Math.PI,
                // Expand the DOM element's width and height by 0.51 pixels. This helps with gaps between tiles.
                antiAliasing: this._needsFractionalSizing ? 0.51 : 0
            }
        );
    }


    /**
     * @param {any} tiles
     * @return {[
     *      bounds: LeafletModule.LatLngBounds,
     *      map: Record<string, string>,
     *      maxY: number
     * ]}
     */
    _compileTileData( tiles ) {
        const [ tileY, tileX ] = this._physicalTileSize;
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

        const map = tiles.reduce( ( map, tile ) => {
            const [ y, x ] = tile.position;
            map[ `${y},${x}` ] = tile.image;

            // Update boundary tracking
            minX = Math.min( x, minX );
            minY = Math.min( y, minY );
            maxX = Math.max( x, maxX );
            maxY = Math.max( y, maxY );

            return map;
        }, {} );

        maxX++;
        maxY++;

        return [
            new ( Util.getLeaflet().LatLngBounds )(
                this.map.crs.fromPoint( [ minY / 2 * tileY, minX / 2 * tileX ] ),
                this.map.crs.fromPoint( [ maxY / 2 * tileY, maxX / 2 * tileX ] )
            ),
            map,
            maxY
        ];
    }


    _constructTiles( tiles ) {
        const Leaflet = Util.getLeaflet();

        // Create a map of positions to tiles for a fast lookup of image URLs
        const [ bounds, imageLut, maxY ] = this._compileTileData( tiles );
        const imageLookup = ( coords ) => {
            // If origin point is in the bottom-left corner, invert the Y position here
            let y = coords.y;
            if ( this.map.crs.origin === CRSOrigin.BottomLeft ) {
                y = maxY + y;
            }

            const matchedImage = imageLut[ `${y},${coords.x}` ];
            if ( matchedImage ) {
                return matchedImage;
            }
            return null;
        };

        return new Leaflet.Ark.TileManager( {
            className: this.isPixelated ? 'ext-datamaps-pixelated-image' : undefined,
            maxZoom: this.map.config.zoom.max,
            minZoom: this.map.config.zoom.min,
            maxNativeZoom: 1,
            minNativeZoom: 1,
            // LUT binding
            imageLookup,
            // This option is fairly volatile when asset server has aggressive ratelimiting (this spams network with
            // cache revalidation requests)
            keepBuffer: 16,
            // This has to be set to stop Leaflet from creating tiles where they 100% do not exist, and additionally
            // enables our internal content bounds measurements.
            bounds,
            // Use virtual tile size for the internal grid
            tileSize: Leaflet.point( this._physicalTileSize ),
            // Use physical tile size for canvas dimensions
            physicalWidth: this._physicalTileSize[ 1 ],
            physicalHeight: this._physicalTileSize[ 0 ],
        } );
    }


    /**
     * @private
     * @param {DataMaps.Configuration.BackgroundOverlay} overlay
     * @return {LeafletModule.Rectangle|LeafletModule.Polyline|LeafletModule.ImageOverlay}
     */
    _constructOverlay( overlay ) {
        const Leaflet = Util.getLeaflet();
        let result;

        if ( overlay.image ) {
            // Construct an image
            result = new Leaflet.ImageOverlay( overlay.image, this.map.crs.fromBox( overlay.at ), {
                className: overlay.pixelated ? 'ext-datamaps-pixelated-image' : undefined,
                decoding: 'async',
                // Expand the DOM element's width and height by 0.51 pixels. This helps with gaps between tiles.
                antiAliasing: overlay.aa ? 0.51 : 0
            } );
        } else if ( overlay.path ) {
            // Construct a polyline
            result = new Leaflet.Polyline( overlay.path.map( p => this.map.crs.fromPoint( p ) ), {
                color: overlay.colour || Leaflet.Path.prototype.options.color,
                weight: overlay.thickness || Leaflet.Path.prototype.options.weight
            } );
        } else {
            // Construct a rectangle
            result = new Leaflet.Rectangle( this.map.crs.fromBox( overlay.at ), {
                color: overlay.strokeColour || Leaflet.Path.prototype.options.color,
                fillColor: overlay.colour || Leaflet.Path.prototype.options.fillColor
            } );
        }

        // Bind name as tooltip
        if ( overlay.name ) {
            result.bindTooltip( overlay.name );
        }

        return result;
    }
}


module.exports = Background;
