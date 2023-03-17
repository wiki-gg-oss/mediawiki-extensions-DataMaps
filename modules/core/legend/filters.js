/** @typedef {import( '../map.js' )} DataMap */
const LegendTabber = require( './tabber.js' ),
    { MarkerGroupFlags } = require( '../enums.js' ),
    Util = require( '../util.js' );


class MarkerFilteringPanel extends LegendTabber.Tab {
    /**
     * @param {LegendTabber} tabber
     * @param {boolean} addTotalToggles
     */
    constructor( tabber, addTotalToggles ) {
        super( tabber, {
            name: mw.msg( 'datamap-legend-tab-locations' ),
            icon: 'funnel'
        } );

        /**
         * Top button group for quick manipulation actions.
         *
         * @type {OO.ui.ButtonGroupWidget}
         */
        this.buttonGroup = new OO.ui.ButtonGroupWidget( {} );
        /**
         * Marker group container.
         *
         * @type {HTMLElement}
         */
        this.groupContainer = Util.createDomElement( 'div', {
            classes: [ 'ext-datamaps-container-groups' ],
            appendTo: this.contentElement
        } );
        /**
         * Mapping of group IDs to {@link MarkerFilteringPanel.MarkerGroupRow}.
         *
         * @type {Object<string, MarkerFilteringPanel.MarkerGroupRow>}
         */
        this.groupToggles = {};
        /**
         * Layer toggles popup container.
         *
         * @type {jQuery?}
         */
        this.$layersPopup = null;

        // Prepend the button group to the root element
        this.buttonGroup.$element.prependTo( this.contentElement );

        if ( addTotalToggles ) {
            this.createActionButton( mw.msg( 'datamap-toggle-show-all' ), this.toggleAllGroups.bind( this, true ) );
            this.createActionButton( mw.msg( 'datamap-toggle-hide-all' ), this.toggleAllGroups.bind( this, false ) );
        }
    }


    /**
     * @param {string} label
     * @param {() => void} clickCallback
     * @return {OO.ui.ButtonWidget}
     */
    createActionButton( label, clickCallback ) {
        const button = new OO.ui.ButtonWidget( { label: label } );
        button.on( 'click', clickCallback );
        this.buttonGroup.addItems( [ button ] );
        return button;
    }


    /**
     * Sets every group's visibility to {@link state}.
     *
     * @param {boolean} state
     */
    toggleAllGroups( state ) {
        for ( const toggle of Object.values( this.groupToggles ) ) {
            toggle.checkbox.setSelected( state );
        }
    }


    setupLayersPopup() {
        if ( this.$layersPopup === null ) {
            this.$layersPopup = $( '<div>' );
            this.buttonGroup.addItems( [
                new OO.ui.PopupButtonWidget( {
                    label: mw.msg( 'datamap-layer-control' ),
                    indicator: 'down',
                    popup: {
                        $content: this.$layersPopup,
                        padded: true,
                        width: 220,
                        align: 'forwards'
                    }
                } )
            ] );
        }
    }


    /**
     * @package
     * @param {jQuery|HTMLElement} parentElement
     * @param {string} layerId
     * @param {string} name
     * @param {'setExclusion' | 'setInclusion' | 'setRequirement'} fnName
     * @param {boolean} invert
     * @param {boolean} tickByDefault
     * @return {[ OO.ui.FieldLayout, OO.ui.CheckboxInputWidget ]}
     */
    _makeLayerToggleField( parentElement, layerId, name, fnName, invert, tickByDefault ) {
        const checkbox = new OO.ui.CheckboxInputWidget( {
            selected: tickByDefault
        } ).on( 'change', () => {
            const state = checkbox.isSelected();
            this.map.layerManager[ fnName ]( layerId, ( invert ? state : !state ) );
        } );
        const field = new OO.ui.FieldLayout( checkbox, {
            label: name,
            align: 'inline'
        } );
        field.$element.appendTo( parentElement );
        return [ field, checkbox ];
    }


    /**
     * @param {string} layerId
     * @param {string} name
     */
    addMarkerLayerToggleExclusive( layerId, name ) {
        this.setupLayersPopup();
        this._makeLayerToggleField( Util.getNonNull( this.$layersPopup ), layerId, name, 'setExclusion', false, true );
    }


    /**
     * @param {string} layerId
     * @param {string} name
     * @param {boolean} invert
     */
    addMarkerLayerToggleInclusive( layerId, name, invert ) {
        this.setupLayersPopup();
        this._makeLayerToggleField( Util.getNonNull( this.$layersPopup ), layerId, name, 'setInclusion', invert, true );
    }


    /**
     * @param {string} layerId
     * @param {string} name
     * @param {boolean} invert
     */
    addMarkerLayerToggleRequired( layerId, name, invert ) {
        this.setupLayersPopup();
        this._makeLayerToggleField( Util.getNonNull( this.$layersPopup ), layerId, name, 'setRequirement', invert, true );
    }


    /**
     * @param {string} groupId
     * @param {DataMaps.Configuration.MarkerGroup} group
     */
    addMarkerGroupToggle( groupId, group ) {
        this.groupToggles[ groupId ] = new MarkerFilteringPanel.MarkerGroupRow( this, groupId, group );
        this.setVisible( true );
    }


    /**
     * @param {string[]} ids
     */
    includeGroups( ids ) {
        for ( const groupId of ids ) {
            this.addMarkerGroupToggle( groupId, this.map.config.groups[ groupId ] );
        }
    }
}


MarkerFilteringPanel.MarkerGroupRow = class MarkerGroupRow {
    /**
     * @param {MarkerFilteringPanel} outerPanel
     * @param {string} groupId
     * @param {DataMaps.Configuration.MarkerGroup} group
     */
    constructor( outerPanel, groupId, group ) {
        /**
         * @type {MarkerFilteringPanel}
         */
        this.outerPanel = outerPanel;
        /**
         * @type {string}
         */
        this.groupId = groupId;

        // Create a backing checkbox field
        const pair = this.outerPanel._makeLayerToggleField( this.outerPanel.groupContainer, groupId, group.name, 'setExclusion',
            false, !Util.isBitSet( group.flags, MarkerGroupFlags.IsUnselected ) );
        /**
         * @type {OO.ui.FieldLayout}
         */
        this.field = pair[ 0 ];
        /**
         * @type {OO.ui.CheckboxInputWidget}
         */
        this.checkbox = pair[ 1 ];

        // Optional elements
        /**
         * @type {jQuery?}
         */
        this.$circle = null;
        /**
         * @type {jQuery?}
         */
        this.$badge = null;
        /**
         * @type {jQuery?}
         */
        this.$icon = null;

        // Add a coloured circle if circle marker group
        if ( 'fillColor' in group ) {
            this.$circle = Util.Groups.createCircleElement( group ).prependTo( this.field.$header );
        }

        // Add a pin icon if pin marker group
        if ( 'pinColor' in group ) {
            this.$pin = Util.Groups.createPinIconElement( group ).prependTo( this.field.$header );
        }

        // Add an icon if one is specified in the group
        if ( group.legendIcon ) {
            this.$icon = Util.Groups.createIconElement( group ).prependTo( this.field.$header );
        }
    }


    /**
     * @param {string?} text
     */
    setBadge( text ) {
        if ( text && text.length > 0 ) {
            if ( this.$badge === null ) {
                this.$badge = $( '<span class="ext-datamaps-legend-badge">' ).appendTo( this.field.$header );
            }
            this.$badge.text( text );
        } else if ( this.$badge ) {
            this.$badge.remove();
            this.$badge = null;
        }
    }
};


module.exports = MarkerFilteringPanel;