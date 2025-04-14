/** @typedef {import( './DataMap.js' )} DataMap */
const Util = require( './Util.js' ),
    { MapFlags } = require( './enums.js' ),
    { createDomElement, getNonNull } = Util,
    CodexIcon = require( './icons.json' );


/**
 * @implements {LeafletModule.Ark.IPopupContentRenderer}
 */
module.exports = class MarkerPopup {
    // TODO: document lifetime

    /**
     * @param {DataMap} map
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    constructor( map, leafletMarker ) {
        /**
         * Owning map.
         *
         * @type {DataMap}
         */
        this.map = map;
        /**
         * Associated marker.
         *
         * @type {LeafletModule.AnyMarker}
         */
        this.leafletMarker = leafletMarker;
        /**
         * Marker group configuration.
         *
         * @type {DataMaps.Configuration.MarkerGroup}
         */
        this.markerGroup = map.config.groups[ this.leafletMarker.attachedLayers[ 0 ] ];
        /**
         * Slot data of the associated marker.
         *
         * @type {DataMaps.IApiMarkerSlots}
         */
        this.slots = this.leafletMarker.apiInstance[ 2 ] || {};
        /**
         * Unique identifier of the associated marker.
         *
         * @type {string|number}
         */
        this.uid = Util.getMarkerId( this.leafletMarker );
        /**
         * MultimediaViewer thumbnail index.
         *
         * @type {number?}
         */
        this.mmvThumbIndex = null;

        // These elements are created during building
        /** @type {HTMLElement?} */
        this.subTitle = null;
        /** @type {HTMLElement?} */
        this.title = null;
        /** @type {HTMLElement?} */
        this.location = null;
        /** @type {HTMLElement?} */
        this.description = null;
        /** @type {HTMLElement?} */
        this.imageContainer = null;
        /** @type {HTMLElement?} */
        this.image = null;
        /** @type {HTMLElement?} */
        this.dismiss = null;
    }


    /**
     * Binds a dynamically initialised popup to a marker. The renderer class is provided by the map via the
     * {@link DataMap.getPopupClass} method.
     *
     * @param {DataMap} map
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    static bindTo( map, leafletMarker ) {
        leafletMarker.bindPopup( () => new ( map.getPopupClass() )( map, leafletMarker ), {
            closeButton: false,
            // TODO: Currently VE is not compatible with ephemeral popups
            tooltip: !map.checkFeatureFlag( MapFlags.VisualEditor ) && map.checkFeatureFlag( MapFlags.PopupTooltips )
        }, Util.getLeaflet().Ark.Popup );
    }


    /**
     * Whether the popup manager should retain the DOM nodes after the popup is closed.
     *
     * @return {boolean}
     */
    shouldKeepAround() {
        return true;
    }


    /**
     * Builds the popup.
     *
     * @param {HTMLElement} element
     */
    build( element ) {
        this.buildHeader( createDomElement( 'div', {
            classes: [ 'ext-datamaps-popup-header' ],
            appendTo: element
        } ) );
        this.buildContent( element );
        // If tools are not empty, push them onto the content
        const actionsElement = createDomElement( 'div', {
            classes: [ 'ext-datamaps-popup-tools' ]
        } );
        this.buildActions( actionsElement );
        if ( actionsElement.children.length > 0 ) {
            element.appendChild( actionsElement );
        }
    }


    /**
     * Builds the buttons.
     *
     * @param {HTMLElement} element
     */
    buildButtons( element ) {
        const createButton = ( id, labelMsg, svgIcon, clickHandler ) => {
            return createDomElement( 'button', {
                classes: [
                    'cdx-button',
                    'cdx-button--action-default',
                    'cdx-button--weight-quiet',
                    'cdx-button--size-medium',
                    'cdx-button--icon-only',
                    `ext-datamaps-popup-button`,
                    `ext-datamaps-popup-button--${id}`,
                    `ext-datamaps-popup-${id}`
                ],
                attributes: {
                    type: 'button',
                    title: mw.msg( labelMsg ),
                    'aria-label': mw.msg( labelMsg )
                },
                events: {
                    click: event => {
                        event.preventDefault();
                        clickHandler();
                    }
                },
                html: Util.createCdxIconElement( svgIcon ),
                appendTo: element
            } );
        };

        if ( this.map.canModifyUriAddress() ) {
            const getLink = createButton(
                'link',
                'datamap-popup-marker-link-get',
                CodexIcon.cdxIconLink,
                () => {
                    // eslint-disable-next-line compat/compat
                    navigator.clipboard.writeText( /** @type {string} */ ( getLink.getAttribute( 'href' ) ) )
                        .then( () => mw.notify( mw.msg( 'datamap-popup-marker-link-copied' ) ) );
                }
            );
        }

        createButton(
            'close',
            'datamap-popup-close',
            CodexIcon.cdxIconClose,
            () => {
                this.leafletMarker._popup.close();
            }
        );
    }


    /**
     * @param {HTMLElement} element
     */
    buildHeader( element ) {
        // Build the title
        if ( this.slots.label && this.markerGroup.name !== this.slots.label ) {
            this.subTitle = createDomElement( 'b', {
                classes: [ 'ext-datamaps-popup-subtitle' ],
                text: this.markerGroup.name,
                appendTo: element
            } );
            this.title = createDomElement( 'b', {
                classes: [ 'ext-datamaps-popup-title' ],
                html: this.slots.label,
                appendTo: element
            } );
        } else {
            this.title = createDomElement( 'b', {
                classes: [ 'ext-datamaps-popup-title' ],
                text: this.markerGroup.name,
                appendTo: element
            } );
        }

        // Collect layer discriminators
        /** @type {string[]} */
        const discrims = [];
        this.leafletMarker.attachedLayers.forEach( ( layerId, index ) => {
            const layer = this.map.config.layers[ layerId ];
            if ( index > 0 && layer && layer.discrim ) {
                discrims.push( layer.discrim );
            }
        } );

        // Gather detail text from layers
        let detailText = discrims.join( ', ' );
        // Reformat if coordinates are to be shown
        if ( this.map.checkFeatureFlag( MapFlags.ShowCoordinates ) ) {
            const coordText = this.map.crs.makeLabel( this.leafletMarker.apiInstance );
            detailText = detailText ? `${coordText} (${detailText})` : coordText;
        }
        // Push onto the contents
        this.location = createDomElement( 'div', {
            classes: [ 'ext-datamaps-popup-location' ],
            text: detailText,
            appendTo: element
        } );
    }

    /**
     * Builds contents of this popup.
     *
     * @param {HTMLElement} element
     */
    buildContent( element ) {
        // Description
        if ( this.slots.desc ) {
            if ( !this.slots.desc.startsWith( '<p>' ) ) {
                this.slots.desc = `<p>${this.slots.desc}</p>`;
            }
            this.description = createDomElement( 'div', {
                classes: [ 'ext-datamaps-popup-description' ],
                html: this.slots.desc,
                appendTo: element
            } );
        }

        // Image
        if ( this.slots.image ) {
            this.imageContainer = createDomElement( 'div', {
                classes: [ 'ext-datamaps-popup-image-container' ],
                html: (
                    this.image = createDomElement( 'img', {
                        classes: [ 'ext-datamaps-popup-image' ],
                        attributes: {
                            src: this.slots.image[ 0 ],
                            width: this.slots.image[ 4 ] ? this.slots.image[ 4 ] : Math.min( this.slots.image[ 1 ], 288 ),
                            height: this.slots.image[ 3 ],
                            'data-file-width': this.slots.image[ 1 ],
                            'data-file-height': this.slots.image[ 2 ],
                            loading: 'lazy'
                        }
                    } )
                ),
                appendTo: element
            } );
            this._setupMMVIntegration();
        }
    }


    /**
     * Builds the action list of this popup.
     *
     * @param {HTMLElement} element
     */
    buildActions( element ) {
        const createButton = ( labelText, svgIcon, clickHandler ) => {
            const buttonElement = createDomElement( 'button', {
                classes: [
                    'cdx-button',
                    'cdx-button--action-progressive',
                    'cdx-button--weight-quiet',
                    'cdx-button--size-small',
                    'cdx-button--framed'
                ],
                text: labelText
            } );

            if ( svgIcon ) {
                buttonElement.prepend( Util.createCdxIconElement( svgIcon, 'small' ) );
            }

            if ( typeof( clickHandler ) === 'string' ) {
                return createDomElement( 'a', {
                    attributes: {
                        href: clickHandler
                    },
                    html: buttonElement,
                    appendTo: element
                } );
            } else {
                buttonElement.addEventListener( 'click', event => {
                    event.preventDefault();
                    clickHandler();
                } );
                element.append( buttonElement );
            }

            return buttonElement
        };

        // Related article
        let article = this.slots.article || this.markerGroup.article;
        if ( article ) {
            let msg = mw.msg( 'datamap-popup-related-article' );
            if ( article.indexOf( '|' ) >= 0 ) {
                const split = article.split( '|', 2 );
                msg = split[ 1 ];
                article = split[ 0 ];
            }

            createButton(
                msg,
                CodexIcon.cdxIconLink,
                mw.util.getUrl( article )
            );
        }

        // Dismissables
        if ( Util.Groups.getCollectibleType( this.markerGroup ) ) {
            this.dismiss = createButton(
                undefined,
                undefined,
                () => {
                    this.map.toggleMarkerDismissal( this.leafletMarker );
                    getNonNull( this.map.viewport ).getLeafletMap().closePopup();
                }
            );
        }
    }


    /**
     * Updates URL with currently opened marker.
     *
     * @param {boolean} isTooltip
     */
    onAdd( isTooltip ) {
        if ( !isTooltip ) {
            Util.updateLocation( this.map, { marker: this.uid } );
        }
    }


    onPromoted() {
        Util.updateLocation( this.map, { marker: this.uid } );
    }


    /**
     * Updates URL to remove the marker parameter.
     *
     * @param {boolean} wasTooltip
     */
    onRemove( wasTooltip ) {
        if ( !wasTooltip ) {
            Util.updateLocation( this.map, { marker: null } );
        }
    }


    /**
     * Returns a label for the collectible status change action.
     *
     * @protected
     * @return {string}
     */
    _getDismissToolText() {
        // Messages that can be used here:
        // * datamap-popup-dismissed
        // * datamap-popup-mark-as-dismissed
        return mw.msg( 'datamap-popup-' + ( this.leafletMarker.options.dismissed ? 'dismissed' : 'mark-as-dismissed' ) );
    }


    /**
     * Updates the label of the collectible status change action.
     */
    onUpdate() {
        if ( this.dismiss ) {
            this.dismiss.innerText = this._getDismissToolText();
        }
    }


    /**
     * Sets up MultimediaViewer integration on the popup's image.
     *
     * @private
     */
    _setupMMVIntegration() {
        if ( this.image && mw.config.get( 'wgMediaViewer' ) ) {
            const $image = $( this.image );
            mw.loader.using( 'mmv.bootstrap', () => {
                const title = mw.Title.newFromImg( $image );
                let caption = this.markerGroup.name;
                if ( this.map.checkFeatureFlag( MapFlags.ShowCoordinates ) ) {
                    caption += ` (${this.map.crs.makeLabel( this.leafletMarker.apiInstance )})`;
                }

                $image.on( 'click', event => {
                    // Do not interfere with non-left clicks or if modifier keys are pressed.
                    if ( ( event.button !== 0 && event.which !== 1 ) || event.altKey || event.ctrlKey || event.shiftKey
                        || event.metaKey ) {
                        return true;
                    }

                    mw.mmv.bootstrap.ensureEventHandlersAreSetUp();
                    mw.mmv.bootstrap.loadViewer( true ).then( viewer => {
                        if ( this.mmvThumbIndex === null ) {
                            const mmvThumb = {
                                thumb: $image[ 0 ],
                                $thumb: $image,
                                title,
                                link: title.getUrl( {} ),
                                alt: caption,
                                caption,
                                extraStatsDeferred: $.Deferred(),
                                image: null
                            };
                            if ( !viewer.thumbs ) {
                                viewer.thumbs = [];
                            }
                            viewer.thumbs.push( mmvThumb );
                            this.mmvThumbIndex = viewer.thumbs.length - 1;

                            mmvThumb.image = viewer.createNewImage(
                                mmvThumb.$thumb.prop( 'src' ),
                                mmvThumb.link,
                                mmvThumb.title,
                                this.mmvThumbIndex,
                                mmvThumb.thumb,
                                mmvThumb.caption,
                                mmvThumb.alt
                            );
                        }

                        // HACK: suppress MMV's setMediaHash on the whole page
                        if ( viewer._noSMH ) {
                            viewer.setMediaHash = () => {};
                            viewer._noSMH = true;
                        }

                        const thumb = viewer.thumbs[ this.mmvThumbIndex ];
                        viewer.loadImage( thumb.image, thumb.$thumb.clone()[ 0 ], false );
                    } );
                    event.preventDefault();
                    return false;
                } );
            } );
        }
    }
};
