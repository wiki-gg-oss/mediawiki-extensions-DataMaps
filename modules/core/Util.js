/** @typedef {import( './DataMap.js' )} DataMap */
const { MarkerGroupFlags } = require( './enums.js' ),
    // @ts-ignore: module resolution error
    serverSettings = /** @type {DataMaps.IExposedServerSettings} */ ( require( './settings.json' ) );

/** @type {LeafletModule?} */
let Leaflet = null;


module.exports = Object.freeze( {
    /**
     * Max circle size in legend elements.
     *
     * @constant
     * @type {number}
     */
    MAX_GROUP_CIRCLE_SIZE: 20,


    /**
     * Max icon size in legend elements.
     *
     * @constant
     * @type {number}
     */
    MAX_GROUP_ICON_SIZE: 20,

    /**
     * Whether experimental features have been enabled server-side.
     *
     * @constant
     * @type {boolean}
     */
    isBleedingEdge: serverSettings.IsBleedingEdge,


    /**
     * Whether visual editor is enabled server-side.
     *
     * @constant
     * @type {boolean}
     */
    isVisualEditorEnabled: serverSettings.IsVisualEditorEnabled,

    /**
     * Whether anonymous users can edit pages.
     *
     * @constant
     * @type {boolean}
     */
    canAnonsEdit: serverSettings.CanAnonsEdit,

    /**
     * Whether maps will be loaded lazily, i.e. when they come into view.
     *
     * @constant
     * @type {boolean}
     */
    isMapLazyLoadingEnabled: serverSettings.IsMapLazyLoadingEnabled,

    /**
     * Throws an exception if the value is null or undefined. Returns it back otherwise. This exists primarily to satisfy
     * TypeScript's type checking.
     *
     * @template T
     * @param {T|null|undefined} value
     * @return {T}
     */
    getNonNull( value ) {
        if ( value === null || value === undefined ) {
            throw new Error( 'Encountered a null value in non-nullable context' );
        }
        return value;
    },


    /**
     * Returns whether a bit is set in a bit field.
     *
     * @param {number|undefined?} a The field.
     * @param {number} b The bit.
     * @return {boolean}
     */
    isBitSet( a, b ) {
        return !!a && ( a & b ) === b;
    },


    /**
     * Returns whether the bit mask masks any bit in a bit field.
     *
     * @param {number|undefined?} a The field.
     * @param {number} b The bit mask.
     * @return {boolean}
     */
    isAnyBitSet( a, b ) {
        return !!a && ( a & b ) !== 0;
    },


    /**
     * @typedef {Object} DomElementFactoryOptions
     * @property {string[]?} [classes]
     * @property {string} [text]
     * @property {string|HTMLElement} [html]
     * @property {Record<string, string|undefined|boolean|number>} [attributes]
     * @property {Partial<CSSStyleDeclaration>} [style]
     * @property {Partial<{
     *     [ P in keyof HTMLElementEventMap ]: ( ( event: HTMLElementEventMap[ P ] ) => void ) | ( () => void )
     * }>} [events]
     * @property {HTMLElement} [appendTo]
     * @property {HTMLElement} [prependTo]
     */

    /**
     * @param {keyof HTMLElementTagNameMap} tag
     * @param {DomElementFactoryOptions} options
     * @return {HTMLElement}
     */
    createDomElement( tag, options ) {
        const result = document.createElement( tag );
        if ( options.text ) {
            result.innerText = options.text;
        }
        if ( options.html ) {
            if ( typeof options.html === 'string' ) {
                result.innerHTML = options.html;
            } else {
                result.appendChild( options.html );
            }
        }
        if ( options.classes ) {
            // eslint-disable-next-line mediawiki/class-doc
            result.classList.add( ...options.classes );
        }
        if ( options.attributes ) {
            for ( const key in options.attributes ) {
                const value = options.attributes[ key ];
                if ( value !== undefined && value !== null ) {
                    result.setAttribute( key, `${value}` );
                }
            }
        }
        if ( options.style ) {
            for ( const key in options.style ) {
                const value = options.style[ key ];
                if ( value !== undefined && value !== null ) {
                    result.style[ key ] = value;
                }
            }
        }
        if ( options.events ) {
            for ( const name in options.events ) {
                const listener = options.events[ /** @type {keyof HTMLElementEventMap} */ ( name ) ];
                if ( listener ) {
                    // @ts-ignore: type checker does not infer listener's type
                    result.addEventListener( name, listener );
                }
            }
        }
        if ( options.appendTo ) {
            options.appendTo.appendChild( result );
        }
        if ( options.prependTo ) {
            options.prependTo.prepend( result );
        }
        return result;
    },


    createCdxIconElement( paths, size = 'medium' ) {
        if ( typeof( paths ) === 'object' ) {
            paths = paths.ltr || paths.default;
        }

        const svgRoot = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
        svgRoot.setAttribute( 'aria-hidden', 'true' );
        svgRoot.setAttribute( 'viewBox', '0 0 20 20' );
        svgRoot.innerHTML = paths;
        return module.exports.createDomElement( 'span', {
            classes: [ 'cdx-icon', `cdx-icon--${size}` ],
            html: svgRoot,
        } );
    },


    /**
     * Retrieves Leaflet exports if they've been loaded.
     *
     * @return {LeafletModule}
     */
    getLeaflet() {
        if ( Leaflet === null ) {
            Leaflet = /** @type {LeafletModule} */ ( require( 'ext.datamaps.leaflet' ) );
        }
        return Leaflet;
    },


    Groups: {
        /**
         * Returns the collectible type of a marker group, or zero if none.
         *
         * @param {DataMaps.Configuration.MarkerGroup} group
         * @return {number}
         */
        getCollectibleType( group ) {
            return ( group.flags || 0 ) & MarkerGroupFlags.Collectible_Any;
        },


        /**
         * Creates an image DOM element showing a marker group's icon.
         *
         * @param {DataMaps.Configuration.MarkerGroup} group
         * @return {HTMLElement}
         */
        createIconElement( group ) {
            return module.exports.createDomElement( 'img', {
                classes: [ 'ext-datamaps-legend-group-icon', 'ext-datamaps-legend-image-icon' ],
                attributes: {
                    width: module.exports.MAX_GROUP_ICON_SIZE,
                    height: module.exports.MAX_GROUP_ICON_SIZE,
                    src: module.exports.getNonNull( group.legendIcon ),
                    loading: 'lazy'
                }
            } );
        },


        /**
         * Creates an SVG element showing a marker group's pin icon.
         *
         * @param {DataMaps.Configuration.PinMarkerGroup} group
         * @return {SVGElement}
         */
        createPinIconElement( group ) {
            const result = module.exports.createPinIconElement( {
                colour: group.pinColor,
                strokeColour: group.strokeColor,
                strokeWidth: group.strokeWidth
            } );
            result.classList.add( 'ext-datamaps-legend-group-icon' );
            result.setAttribute( 'width', `${module.exports.MAX_GROUP_ICON_SIZE}` );
            result.setAttribute( 'height', `${module.exports.MAX_GROUP_ICON_SIZE}` );
            return result;
        },


        /**
         * Creates a DOM element showing a marker group's coloured circle representation.
         *
         * @param {DataMaps.Configuration.CircleMarkerGroup} group
         * @return {HTMLElement}
         */
        createCircleElement( group ) {
            const size = Math.min( module.exports.MAX_GROUP_CIRCLE_SIZE, group.size + 4 ) + 'px';
            return module.exports.createDomElement( 'div', {
                classes: [ 'ext-datamaps-legend-circle' ],
                style: {
                    minWidth: size,
                    width: size,
                    height: size,
                    backgroundColor: group.fillColor,
                    borderColor: group.strokeColor || group.fillColor,
                    borderWidth: ( group.strokeWidth || 1 ) + 'px'
                }
            } );
        },


        /**
         * Creates an image DOM element showing a marker group's icon.
         *
         * @param {DataMaps.Configuration.MarkerGroup} group
         * @return {Element?}
         */
        createIcon( group ) {
            let /** @type {Element?} */ result = null;

            if ( 'fillColor' in group ) {
                result = module.exports.Groups.createCircleElement( group );
            } else if ( 'pinColor' in group ) {
                result = module.exports.Groups.createPinIconElement( group );
            } else if ( group.legendIcon ) {
                result = module.exports.Groups.createIconElement( group );
            }

            return result;
        }
    },


    /**
     * @typedef {Object} PinIconOptions
     * @property {string} colour
     * @property {string} [strokeColour='#0006']
     * @property {number} [strokeWidth=0.5]
     */
    /**
     * Creates an SVG element of a pin-shaped icon.
     *
     * @param {PinIconOptions} options
     * @return {SVGElement}
     */
    createPinIconElement( options ) {
        const root = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
        root.setAttribute( 'viewBox', '0 0 20 20' );
        const path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
        path.setAttribute( 'd', 'M 10,0 C 5.4971441,-0.21118927 1.7888107,3.4971441 2,8 c 0,2.52 2,5 3,6 1,1 5,6 5,6 0,0 4,-5 5,'
            + '-6 1,-1 3,-3.48 3,-6 0.211189,-4.5028559 -3.497144,-8.21118927 -8,-8 z' );
        module.exports.applyPinIconAttributes( path, options );
        const circle = document.createElementNS( 'http://www.w3.org/2000/svg', 'circle' );
        circle.setAttribute( 'cx', '10' );
        circle.setAttribute( 'cy', '8' );
        circle.setAttribute( 'r', '3.3' );
        circle.setAttribute( 'fill', '#0009' );
        root.appendChild( path );
        root.appendChild( circle );
        return root;
    },


    /**
     * @param {SVGElement} pathElement
     * @param {PinIconOptions} options
     */
    applyPinIconAttributes( pathElement, options ) {
        const fill = options.colour,
            strokeColour = options.strokeColour || '#fff6',
            strokeWidth = options.strokeWidth || 0.5;
        pathElement.setAttribute( 'fill', fill );
        if ( strokeWidth > 0 ) {
            pathElement.setAttribute( 'stroke', strokeColour );
            pathElement.setAttribute( 'stroke-width', `${strokeWidth}` );
        }
    },


    /**
     * Generates an identifier of a marker using type and coordinates.
     *
     * @param {LeafletModule.AnyMarker} leafletMarker Marker to get the identifier of.
     * @return {string}
     */
    getGeneratedMarkerId( leafletMarker ) {
        const type = leafletMarker.attachedLayers.join( ' ' );
        return `${type}@${leafletMarker.apiInstance[ 0 ].toFixed( 3 )}:${leafletMarker.apiInstance[ 1 ].toFixed( 3 )}`;
    },


    /**
     * Retrieves an identifier of a marker to use with local storage or in permanent links.
     *
     * @param {LeafletModule.AnyMarker} leafletMarker Marker to get the identifier of.
     * @return {string|number}
     */
    getMarkerId( leafletMarker ) {
        return leafletMarker.apiInstance[ 2 ] && leafletMarker.apiInstance[ 2 ].uid
            || module.exports.getGeneratedMarkerId( leafletMarker );
    },


    /**
     * Retrieves a query (GET) parameter from current URL.
     *
     * @param {string} name Parameter name.
     * @return {string?}
     */
    getQueryParameter( name ) {
        // eslint-disable-next-line compat/compat
        const value = new URLSearchParams( window.location.search ).get( name );
        return value ? decodeURIComponent( value ) : value;
    },


    /**
     * Constructs a URL with specified parameters (appended) and keeps TabberNeue's hash.
     *
     * @param {DataMap} map
     * @param {Record<string, string|number|null>} paramsToSet
     * @param {boolean} [withHost]
     * @return {string}
     */
    makeUrlWithParams( map, paramsToSet, withHost ) {
        // eslint-disable-next-line compat/compat
        const params = new URLSearchParams( window.location.search );
        for ( const paramName in paramsToSet ) {
            if ( paramsToSet[ paramName ] ) {
                params.set( paramName, `${paramsToSet[ paramName ]}` );
            } else {
                params.delete( paramName );
            }
        }

        const tabber = module.exports.TabberNeue.getOwningId( map.rootElement );
        const hash = ( tabber ? ( '#' + tabber ) : window.location.hash );
        return ( withHost ? `https://${window.location.hostname}` : '' )
            + decodeURIComponent( `${window.location.pathname}?${params}`.replace( /\?$/, '' )
            + hash );
    },


    /**
     * Replaces current URL in the browser with a new URL with specified parameters and preserved TabberNeue hash.
     *
     * @param {DataMap} map
     * @param {Record<string, string|number|null>} paramsToSet
     */
    updateLocation( map, paramsToSet ) {
        if ( map.canModifyUriAddress() ) {
            history.replaceState( {}, '', module.exports.makeUrlWithParams( map, paramsToSet, false ) );
        }
    },


    /**
     * Stops propagation of several touch and mouse interaction events.
     *
     * @param {HTMLElement} element
     */
    preventMapInterference( element ) {
        // Stop mouse event propagation onto Leaflet map
        for ( const eventName of [
            'click', 'dblclick', 'scroll', 'mousewheel', 'wheel', 'mousedown', 'mouseup', 'touchstart', 'touchmove', 'touchup',
            'touchcancel'
        ] ) {
            element.addEventListener( eventName, event => event.stopPropagation() );
        }
    },


    TabberNeue: {
        module: serverSettings.TabberNeueModule,


        /**
         * @param {HTMLElement} element
         * @return {HTMLElement?}
         */
        getOwningPanel( element ) {
            return element.closest( 'article.tabber__panel' );
        },


        /**
         * @param {HTMLElement} element
         * @return {HTMLElement?}
         */
        getOwningTabber( element ) {
            return element.closest( 'div.tabber' );
        },


        /**
         * Finds ID of the TabberNeue tab this map is in. If not inside tabber, this will be null.
         *
         * @param {HTMLElement} element
         * @return {string?}
         */
        getOwningId( element ) {
            const panel = module.exports.TabberNeue.getOwningPanel( element );
            return panel ? ( panel.getAttribute( 'id' ) || module.exports.getNonNull( panel.dataset.title ).replace( ' ', '_' ) )
                : null;
        }
    }
} );
