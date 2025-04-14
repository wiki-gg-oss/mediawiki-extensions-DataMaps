/** @typedef {import( './DataMap.js' )} DataMap */
const {
    isVisualEditorEnabled,
    createDomElement,
    createCdxIconElement,
    getNonNull,
    preventMapInterference,
    TabberNeue,
} = require( './Util.js' );
const CodexIcon = require( './icons.json' );


/**
 * @typedef {Object} ControlOptions
 * @property {string} [tagName='div'] HTML element tag name.
 * @property {string[]} [classes] List of CSS classes to add to the element.
 * @property {boolean} [primary] Whether to use action-indicative styling.
 * @property {boolean} [delegatedBuild] If true, {@link _build} call will be left to the subclass's constructor.
 */
/**
 * @typedef {Object} ControlButtonOptions
 * @property {boolean} [addToSelf] Whether to add the button to the end of the control automatically.
 * @property {OO.ui.Icon} [icon] OOUI icon name, if any.
 * @property {string} [svg] SVG icon paths, if any.
 * @property {string} [label] Label text, if any.
 * @property {boolean} [labelBeforeIcon] Whether to put the label before icon.
 * @property {string} [tooltip] Tooltip, if any.
 * @property {string[]} [classes] CSS classes to add to the button element, if any.
 * @property {EventListenerOrEventListenerObject} [clickHandler] Click event handler, if any.
 * @property {boolean} [returnToMap=true]
 */


/**
 * Base class for all custom map controls.
 *
 * @abstract
 */
class MapControl {
    /**
     * @param {DataMap} map Owning map.
     * @param {string} id
     * @param {ControlOptions} [options]
     */
    constructor( map, id, options ) {
        options = options || {};
        /** @type {DataMap} */
        this.map = map;
        /** @type {HTMLElement} */
        this.element = document.createElement( options.tagName || 'div' );
        /**
         * @protected
         * @type {boolean}
         */
        this._isVisible = true;

        // The following classes are used here:
        // * datamap-control
        // * datamap-control-${id}
        this.element.classList.add( 'leaflet-control', 'ext-datamaps-control', `ext-datamaps-control-${id}` );
        if ( this.isButtonGroup() ) {
            this.element.classList.add( 'ext-datamaps-control--button-group' );
        }
        if ( options.classes ) {
            // eslint-disable-next-line mediawiki/class-doc
            this.element.classList.add( ...options.classes );
        }
        if ( options.primary ) {
            this.element.classList.add( 'ext-datamaps-control-primary' );
        }

        if ( !options.delegatedBuild ) {
            this._build();
        }
    }


    /**
     * Whether this control contains buttons and wants to be styled as one (equivalent of Leaflet's "bar" class).
     *
     * @return {boolean}
     */
    isButtonGroup() {
        return true;
    }


    /**
     * Builds the control's elements. This is invoked by the constructor if {@link ControlOptions.delegatedBuild} is not truey.
     *
     * There may be cases where you may want to handle element creation within the constructor.
     *
     * @protected
     */
    _build() {}


    /**
     * Creates a button from options. See {@link ControlButtonOptions}.
     *
     * @protected
     * @param {ControlButtonOptions} options
     * @return {HTMLElement}
     */
    _makeButton( options ) {
        // eslint-disable-next-line mediawiki/class-doc
        const result = createDomElement( 'button', {
            classes: [
                'cdx-button',
                'cdx-button--action-progressive',
                'cdx-button--weight-normal',
                'cdx-button--framed',
                ...( options.classes || [] )
            ],
            html: options.label,
            attributes: {
                role: 'button',
                title: options.tooltip,
                'aria-label': options.tooltip
            },
            events: {
                click: options.clickHandler
            }
        } );

        if ( options.label ) {
            result.dataset.style = 'labelled';
        }

        if ( options.icon ) {
            // eslint-disable-next-line mediawiki/class-doc
            result[ options.labelBeforeIcon ? 'appendChild' : 'prepend' ]( createDomElement( 'span', {
                classes: [ `oo-ui-icon-${options.icon}` ]
            } ) );

            if ( !options.label ) {
                result.classList.add( 'cdx-button--icon-only' );
            }
        }

        if ( options.svg ) {
            result[ options.labelBeforeIcon ? 'appendChild' : 'prepend' ]( createCdxIconElement( options.svg ) );
            if ( !options.label ) {
                result.classList.add( 'cdx-button--icon-only' );
            }
        }

        if ( options.returnToMap !== false ) {
            result.addEventListener( 'click', () => this._refocusMap() );
        }

        if ( options.addToSelf ) {
            this.element.appendChild( result );
        }

        return result;
    }


    /**
     * @param {boolean} value
     * @since 0.16.0
     */
    setVisible( value ) {
        if ( this._isVisible !== value ) {
            this.element.style.display = value ? '' : 'none';
            this._isVisible = value;
        }
    }


    /**
     * @return {boolean}
     * @since 0.16.0
     */
    isVisible() {
        return this.element.style.display !== 'none';
    }


    /**
     * Returns focus back to the map.
     *
     * @protected
     */
    _refocusMap() {
        getNonNull( this.map.viewport ).focus();
    }
}


/**
 * Control to let the user switch map backgrounds.
 */
class BackgroundSwitcher extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'backgrounds', {
            tagName: 'select',
            classes: [ 'cdx-select' ]
        } );
    }


    _build() {
        const element = /** @type {HTMLSelectElement} */ ( this.element );
        for ( const [ index, background ] of this.map.backgrounds.entries() ) {
            createDomElement( 'option', {
                text: background.displayName,
                attributes: {
                    value: index
                },
                appendTo: element
            } );
        }

        const syncDropdownCallback = () => ( element.value = `${this.map.getCurrentBackgroundIndex()}` );
        element.addEventListener( 'change', () => this.map.setBackgroundPreference( parseInt( element.value ) ) );
        syncDropdownCallback();
        this.map.on( 'backgroundChange', syncDropdownCallback );
    }
}


/**
 * Coordinates-under-cursor control.
 */
class Coordinates extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'coords' );
    }


    _build() {
        this.setVisible( false );

        const leaflet = getNonNull( this.map.viewport ).getLeafletMap();
        leaflet.on( 'mousemove', event => {
            this.setVisible( true );
            this.element.innerText = this.map.crs.makeLabel( event.latlng );
        } );
        leaflet.on( 'mouseover', () => this.setVisible( true ) );
        this.map.rootElement.addEventListener( 'mouseout', () => this.setVisible( false ) );
    }
}


/**
 * Control displaying a button leading to user's editor of choice.
 */
class EditButton extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'edit' );
    }


    _build() {
        this._makeButton( {
            addToSelf: true,
            svg: CodexIcon.cdxIconEdit,
            tooltip: mw.msg( 'datamap-control-edit' ),
            clickHandler: () => {
                // @ts-ignore: wrong type signature for wikiScript in the package, argument is optional
                location.href = `${mw.util.wikiScript()}?curid=${this.map.id}&action=` + (
                    isVisualEditorEnabled && mw.user.options.get( 'datamaps-enable-visual-editor' ) ? 'editmap' : 'edit'
                );
            }
        } );
    }
}


/**
 * View reset and centre buttons.
*/
class ZoomControls extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'zoomcontrols' );
    }


    _build() {
        const viewport = getNonNull( this.map.viewport );

        this._zoomIn = this._makeButton( {
            addToSelf: true,
            svg: CodexIcon.cdxIconAdd,
            tooltip: mw.msg( 'datamap-control-zoom-in' ),
            clickHandler: () => viewport.zoomNSteps( 1 )
        } );
        this._zoomOut = this._makeButton( {
            addToSelf: true,
            svg: CodexIcon.cdxIconSubtract,
            tooltip: mw.msg( 'datamap-control-zoom-out' ),
            clickHandler: () => viewport.zoomNSteps( -1 )
        } );

		viewport.getLeafletMap().on( 'zoomend zoomlevelschange', this._updateDisabled, this );
        this._updateDisabled();
    }


    _updateDisabled() {
        const
            map = getNonNull( this.map.viewport ).getLeafletMap(),
            zoom = map.getZoom();
        // Scroll-zoom might not snap us into max zoom, but the difference will be minimal. Use an error margin.
        this._zoomIn.disabled = zoom >= ( map.getMaxZoom() - map.options.zoomDelta * 0.97 );
        this._zoomOut.disabled = zoom <= map.getMinZoom();
    }
}


/**
 * View reset and centre buttons.
*/
class ViewControls extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'viewcontrols' );
    }


    _build() {
        this._makeButton( {
            addToSelf: true,
            svg: CodexIcon.cdxIconImageLayoutFrame,
            tooltip: mw.msg( 'datamap-control-reset-view' ),
            clickHandler: () => getNonNull( this.map.viewport ).resetView()
        } );
        this._makeButton( {
            addToSelf: true,
            svg: CodexIcon.cdxIconAlignCenter,
            tooltip: mw.msg( 'datamap-control-centre-view' ),
            clickHandler: () => getNonNull( this.map.viewport ).centreView()
        } );
    }
}


/**
 * Button to toggle fullscreen mode of the map.
 */
class ToggleFullscreen extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'toggle-fullscreen' );

        this._button = this._makeButton( {
            addToSelf: true,
            svg: CodexIcon.cdxIconFullScreen,
            tooltip: mw.msg( 'datamap-control-fullscreen' ),
            clickHandler: () => {
                this.map.setFullScreen( !this.map.isFullScreen() );
                this._refreshIcon();
            }
        } );
    }


    _refreshIcon() {
        const state = this.map.isFullScreen(),
            element = getNonNull( this._button.firstElementChild );
        element.innerHTML = '';
        element.append( createCdxIconElement( CodexIcon[ state ? 'cdxIconExitFullscreen' : 'cdxIconFullScreen' ] ) );
    }
}


/**
 * Container for the search module. Clicking on it loads the additional scripts.
 */
class SearchHost extends MapControl {
    /**
     * @param {DataMap} map Owning map.
     */
    constructor( map ) {
        super( map, 'search' );

        preventMapInterference( this.element );

        mw.loader.using(
            [
                'oojs-ui-core',
                'oojs-ui-widgets'
            ], () => {
                /**
                 * @type {OO.ui.TextInputWidget}
                 */
                this._inputBox = new OO.ui.TextInputWidget( {
                    placeholder: mw.msg( 'datamap-control-search' ),
                    icon: 'search'
                } );
                this._inputBox.$element.appendTo( this.element );

                this._inputBox.$element.one( 'click', () => this._loadSearch( true ) );
            }
        );

        map.on( 'linkedEvent', event => {
            if ( event.type === 'initLinkedSearch' && event.tabber === TabberNeue.getOwningTabber( map.rootElement ) ) {
                this._loadSearch( false );
            }
        } );
    }


    /**
     * @private
     */
    _loadSearch( canFocus ) {
        if ( this.moduleInstance ) {
            return;
        }

        const spinner = createDomElement( 'div', {
            classes: [ 'ext-datamaps-control-search-spinner' ],
            appendTo: this._inputBox.$element[ 0 ]
        } );

        mw.loader.using( 'ext.datamaps.search', () => {
            this.map.on( 'chunkStreamingDone', () => {
                this.moduleInstance = require( 'ext.datamaps.search' ).setupInHostControl(
                    this,
                    this._inputBox
                );
                spinner.remove();

                if ( canFocus ) {
                    this.moduleInstance.toggle( true );
                }

                // If linked, initialise search in other tabs
                if ( canFocus && this.moduleInstance.isLinked() ) {
                    setTimeout( () => {
                        this.map.fire( 'sendLinkedEvent', {
                            type: 'initLinkedSearch',
                            tabber: TabberNeue.getOwningTabber( this.map.rootElement )
                        } );
                    }, 0 );
                }
            } );
        } );
    }
}


module.exports = {
    MapControl,
    BackgroundSwitcher,
    Coordinates,
    EditButton,
    ZoomControls,
    ViewControls,
    ExtraViewControls: ViewControls,
    ToggleFullscreen,
    SearchHost
};
