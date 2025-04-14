/** @typedef {import( '../DataMap.js' )} DataMap */
/** @typedef {import( '../controls.js' ).ControlButtonOptions} ControlButtonOptions */
const Util = require( '../Util.js' ),
    { MapControl } = require( '../controls.js' ),
    { MapFlags } = require( '../enums.js' ),
    CodexIcon = require( '../icons.json' );


/**
 * A tab manager for the legend.
 */
class LegendTabber {
    /**
     * @param {DataMap} map Owning map.
     * @param {HTMLElement} rootElement Element to host the legend in.
     */
    constructor( map, rootElement ) {
        /**
         * Owning map.
         *
         * @type {DataMap}
         */
        this.map = map;
        /**
         * Legend container element.
         *
         * @type {HTMLElement}
         */
        this.rootElement = rootElement;
        /**
         * @package
         * @type {OO.ui.IndexLayout}
         */
        this._tabs = new OO.ui.IndexLayout( {
            framed: false,
            expanded: false,
            autoFocus: false
        } );
        /**
         * @private
         * @type {LegendTabber.ExpandableControl}
         */
        this._control = new LegendTabber.ExpandableControl( this.map, {
            label: mw.msg( 'datamap-legend-label' ),
            svg: CodexIcon.cdxIconFunnel
        }, this._tabs.$element[ 0 ] );
        Util.preventMapInterference( this._control.element );
        this.rootElement.appendChild( this._control.element );
    }


    /**
     * Called internally by {@see LegendTabber.Tab} to inform us about a tab change. This hides the menu if there is only one (or
     * less) tab.
     *
     * @package
     */
    _updateOnTabChange() {
        this._tabs.$menu.toggle( Object.values( this._tabs.tabPanels ).length > 1 );
    }


    isExpanded() {
        return this._control.isExpanded();
    }


    /**
     * Expands or collapses depending on the parameter value.
     *
     * @param {boolean} value
     * @return {this}
     */
    setExpanded( value ) {
        this._control.setExpanded( value );
        return this;
    }


    /**
     * Creates a {@link LegendTabber.Tab} with a given name and CSS classes, then adds it to this tabber.
     *
     * @param {string} name
     * @param {string[]} [cssClasses]
     * @param {boolean} [visible]
     * @return {LegendTabber.Tab}
     */
    createTab( name, cssClasses, visible ) {
        return new LegendTabber.Tab( this, name, cssClasses ).setVisible( !!visible );
    }
}


LegendTabber.ExpandableControl = class ExpandableControl extends MapControl {
    /**
     * @package
     * @param {DataMap} map Owning map.
     * @param {ControlButtonOptions} buttonOptions
     * @param {HTMLElement} contentElement
     */
    constructor( map, buttonOptions, contentElement ) {
        super( map, 'expandable' );

        const button = this._makeButton( Object.assign( /** @type {ControlButtonOptions} */ ( {
            addToSelf: true,
            clickHandler: () => this.toggle()
        } ), buttonOptions ) );

        /** @type {HTMLElement} */
        this.innerElement = Util.createDomElement( 'div', {
            classes: [ 'ext-datamaps-control-expandable-content' ],
            appendTo: this.element
        } );
        this.innerElement.appendChild( contentElement );
    }


    /**
     * @return {boolean}
     */
    isExpanded() {
        return this.element.getAttribute( 'aria-expanded' ) === 'true';
    }


    /**
     * Expands or collapses depending on the parameter value.
     *
     * @param {boolean} value
     */
    setExpanded( value ) {
        this.element.setAttribute( 'aria-expanded', value ? 'true' : 'false' );
    }


    /**
     * Toggles expansion state.
     */
    toggle() {
        this.setExpanded( !this.isExpanded() );
    }
};


/**
 * Tab abstraction specialised for DataMaps to reduce direct interactions with OOUI. If we ever switch away from it, this will
 * also reduce the impact on other components and gadgets.
 *
 * You should {@link LegendTabber.addTab add the tab} first to a {@link LegendTabber} before calling any of its methods.
 */
LegendTabber.Tab = class Tab {
    /**
     * @typedef {Object} TabOptions
     * @property {string} name
     * @property {OO.ui.Icon} [icon] Currently unused.
     */


    /**
     * @param {LegendTabber} tabber
     * @param {string|TabOptions} nameOrOptions
     * @param {string[]} [cssClasses]
     */
    constructor( tabber, nameOrOptions, cssClasses ) {
        /**
         * @type {LegendTabber}
         */
        this.tabber = tabber;
        /**
         * @type {DataMap}
         */
        this.map = tabber.map;
        /**
         * Content node.
         *
         * @type {HTMLElement}
         */
        // eslint-disable-next-line mediawiki/class-doc
        this.contentElement = Util.createDomElement( 'div', {
            classes: cssClasses
        } );
        /**
         * @private
         * @type {OO.ui.TabPanelLayout}
         */
        this._tab = new OO.ui.TabPanelLayout( this.constructor.name, {
            $content: $( this.contentElement ),
            label: LegendTabber.Tab._constructLabel( nameOrOptions ),
            scrollable: false,
            expanded: false,
            padded: false
        } );
    }


    /**
     * @private
     * @param {string|TabOptions} nameOrOptions
     * @return {string}
     */
    static _constructLabel( nameOrOptions ) {
        if ( typeof ( nameOrOptions ) === 'string' ) {
            return nameOrOptions;
        }

        return nameOrOptions.name;
    }


    /**
     * Adds or removes this tab from the tabber depending on the parameter.
     *
     * @param {boolean} value
     * @return {this}
     */
    setVisible( value ) {
        this.tabber._tabs[ value ? 'addTabPanels' : 'removeTabPanels' ]( [ this._tab ] );
        this.tabber._updateOnTabChange();
        return this;
    }
};


module.exports = LegendTabber;
