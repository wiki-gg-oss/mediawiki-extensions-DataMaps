/** @typedef {import( './MarkerFilteringPanel.js' )} MarkerFilteringPanel */
const LegendTabber = require( './LegendTabber.js' ),
    { CRSOrigin, MarkerGroupFlags, MapFlags } = require( '../enums.js' ),
    Util = require( '../Util.js' );


class CollectiblesPanel extends LegendTabber.Tab {
    /**
     * @param {LegendTabber} tabber
     */
    constructor( tabber ) {
        super( tabber, mw.msg( 'datamap-legend-tab-progress' ), [ 'ext-datamaps-container-collectibles' ] );

        /**
         * @type {Record<string, CollectiblesPanel.Section>}
         */
        this.sections = {};
        /**
         * Whether badge updates are disabled.
         *
         * @type {boolean}
         */
        this.suppressBadgeUpdates = true;

        // Register event handlers
        this.map.on( 'markerDismissChange', () => this.updateGroupBadges(), this );
        this.map.on( 'markerDismissChange', this.onDismissalChange, this );
        this.map.on( 'markerReady', this.pushMarker, this );
        this.map.on( 'chunkStreamingDone', this.sort, this );
        this.map.on( 'chunkStreamingDone', () => ( this.suppressBadgeUpdates = false ), this );
        this.map.on( 'chunkStreamingDone', this.updateGroupBadges, this );

        // Call updaters now to bring the main panel in sync
        this.updateGroupBadges( true );

        // Insert an introduction paragraph
        Util.createDomElement( 'p', {
            html: mw.msg( 'datamap-checklist-prelude' ),
            appendTo: this.contentElement
        } );

        // Initialise marker group views
        // eslint-disable-next-line es-x/no-object-entries
        this.includeGroups( Object.entries( this.map.config.groups ).filter( x => Util.Groups.getCollectibleType( x[ 1 ] ) )
            .map( x => x[ 0 ] ) );

        // Import existing markers if any have been loaded
        for ( const groupName in this.map.config.groups ) {
            const group = this.map.config.groups[ groupName ];
            if ( Util.Groups.getCollectibleType( group ) ) {
                for ( const leafletMarker of ( this.map.layerManager.byLayer[ groupName ] || [] ) ) {
                    this.pushMarker( leafletMarker );
                }
            }
        }
        this.sort();
    }


    /**
     * Sets up sections for marker groups.
     *
     * @param {string[]} ids
     */
    includeGroups( ids ) {
        for ( const id of ids ) {
            this.sections[ id ] = new CollectiblesPanel.Section( this, this.map.config.groups[ id ] );
            this.contentElement.appendChild( this.sections[ id ].contentElement );
        }
    }


    /**
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    pushMarker( leafletMarker ) {
        if ( Util.Groups.getCollectibleType( this.map.config.groups[ leafletMarker.attachedLayers[ 0 ] ] ) ) {
            this.sections[ leafletMarker.attachedLayers[ 0 ] ].push( leafletMarker );
        }
    }


    /**
     * Sorts sections.
     */
    sort() {
        for ( const section of Object.values( this.sections ) ) {
            section.sort();
        }

        if ( this.map.checkFeatureFlag( MapFlags.SortChecklistsByAmount ) ) {
            for ( const section of Object.values( this.sections ).sort( ( a, b ) => b.rows.length - a.rows.length ) ) {
                this.contentElement.appendChild( section.contentElement );
            }
        }
    }


    /**
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    onDismissalChange( leafletMarker ) {
        this.sections[ leafletMarker.attachedLayers[ 0 ] ].replicateMarkerState( leafletMarker );
    }


    /**
     * Updates badges in the filtering panel with current collected / available counts.
     *
     * @param {boolean} [force]
     */
    updateGroupBadges( force ) {
        if ( !force && this.suppressBadgeUpdates ) {
            return;
        }

        for ( const groupId in this.sections ) {
            const markers = this.map.layerManager.byLayer[ groupId ],
                filterEntry = /** @type {MarkerFilteringPanel} */ ( this.map.filtersPanel ).groupToggles[ groupId ];
            // Check if the layer is registered in the visibility manager, and if a group toggle is ready
            if ( markers && filterEntry ) {
                const count = markers.filter( x => x.options.dismissed ).length,
                    mode = Util.Groups.getCollectibleType( this.map.config.groups[ groupId ] );
                filterEntry.setBadge( ( mode === MarkerGroupFlags.Collectible_Individual ? `${count} / ${markers.length}` : '' )
                    + ( count > 0 && count === markers.length ? ' ✓' : '' ) );
            }
        }
    }
}


CollectiblesPanel.Section = class Section {
    /**
     * @param {CollectiblesPanel} panel
     * @param {DataMaps.Configuration.MarkerGroup} group
     */
    constructor( panel, group ) {
        /**
         * @type {CollectiblesPanel}
         */
        this.panel = panel;
        /**
         * @type {DataMaps.Configuration.MarkerGroup}
         */
        this.group = group;
        /**
         * @type {CollectiblesPanel.Row[]}
         */
        this.rows = [];
        /**
         * @readonly
         * @type {boolean}
         */
        this.isIndividual = Util.Groups.getCollectibleType( group ) === MarkerGroupFlags.Collectible_Individual;

        // UI construction
        /**
         * @type {OO.ui.CheckboxInputWidget}
         */
        this.checkbox = new OO.ui.CheckboxInputWidget( {
            selected: false
        } ).on( 'change', () => this.toggleAll( this.checkbox.isSelected() ) );
        /**
         * @type {HTMLElement?}
         */
        this.icon = null;
        if ( 'legendIcon' in group ) {
            this.icon = Util.Groups.createIconElement( group );
        } else if ( 'fillColor' in group ) {
            this.icon = Util.Groups.createCircleElement( group );
        }
        /**
         * @type {HTMLElement}
         */
        this.containerElement = Util.createDomElement( 'div', {
            classes: [ 'ext-datamaps-collectible-group-markers' ]
        } );
        /**
         * @type {HTMLElement}
         */
        this.contentElement = new OO.ui.PanelLayout( {
            framed: true,
            expanded: false,
            content: [
                new OO.ui.PanelLayout( {
                    padded: true,
                    expanded: false,
                    classes: [ 'ext-datamaps-collectible-group-header' ],
                    content: [
                        this.checkbox,
                        this.icon || '',
                        new OO.ui.LabelWidget( {
                            label: group.name
                        } )
                    ]
                } ),
                this.containerElement
            ]
        } ).$element[ 0 ];

        // Hide this section by default. We'll make it visible only when the first row is added.
        this.contentElement.style.display = 'none';
    }


    /**
     * @param {boolean} newState
     */
    toggleAll( newState ) {
        for ( const row of this.rows ) {
            if ( newState !== row.marker.options.dismissed ) {
                this.panel.map.toggleMarkerDismissal( row.marker );
            }
        }
    }


    /**
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    push( leafletMarker ) {
        this.rows.push( new CollectiblesPanel.Row( this, leafletMarker ) );
        this.updateCheckboxState();

        // Make this section visible if this is the first row added
        if ( this.rows.length === 1 ) {
            this.contentElement.style.display = '';
        }
    }


    /**
     * @private
     * @param {number} origin
     * @param {CollectiblesPanel.Row} a
     * @param {CollectiblesPanel.Row} b
     * @return {number}
     */
    static _compareSort( origin, a, b ) {
        if ( origin === CRSOrigin.BottomLeft ) {
            const t = b;
            b = a;
            a = t;
        }

        if ( a.marker.apiInstance[ 0 ] - b.marker.apiInstance[ 0 ] === 0 ) {
            return a.marker.apiInstance[ 1 ] - b.marker.apiInstance[ 1 ];
        }
        return a.marker.apiInstance[ 0 ] - b.marker.apiInstance[ 0 ];
    }


    sort() {
        this.rows.sort( CollectiblesPanel.Section._compareSort.bind( null, this.panel.map.crs.origin ) );

        for ( let index = 0; index < this.rows.length; index++ ) {
            const row = this.rows[ index ];
            row.field.$element.appendTo( this.containerElement );
            if ( row.indexElement ) {
                row.setIndex( index + 1 );
            }
        }
    }


    /**
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    replicateMarkerState( leafletMarker ) {
        for ( const row of this.rows ) {
            if ( row.marker === leafletMarker ) {
                // @ts-ignore: second parameter ("is internal") is missing in the type defs
                row.checkbox.setSelected( leafletMarker.options.dismissed, true );
                break;
            }
        }
        this.updateCheckboxState();
    }


    updateCheckboxState() {
        // @ts-ignore: second parameter ("is internal") is missing in the type defs
        this.checkbox.setSelected( this.rows.every( x => x.marker.options.dismissed ), true );
    }
};


/**
 * A row component representing a Leaflet marker in the collectibles UI.
 */
CollectiblesPanel.Row = class Row {
    /**
     * @param {CollectiblesPanel.Section} outerSection
     * @param {LeafletModule.AnyMarker} leafletMarker
     */
    constructor( outerSection, leafletMarker ) {
        /**
         * @type {CollectiblesPanel.Section}
         */
        this.outerSection = outerSection;
        /**
         * @type {LeafletModule.AnyMarker}
         */
        this.marker = leafletMarker;
        /**
         * @type {DataMaps.IApiMarkerSlots}
         */
        this.slots = this.marker.apiInstance[ 2 ] || {};

        // UI construction

        /**
         * @type {OO.ui.CheckboxInputWidget}
         */
        this.checkbox = new OO.ui.CheckboxInputWidget( {
            selected: this.marker.options.dismissed
        } ).on( 'change', () => this.outerSection.panel.map.toggleMarkerDismissal( this.marker ) );
        /**
         * @type {OO.ui.FieldLayout}
         */
        this.field = new OO.ui.FieldLayout( this.checkbox, {
            label: '...',
            align: 'inline'
        } );
        this.field.$element.appendTo( this.outerSection.containerElement );
        /**
         * @type {HTMLElement}
         */
        this.labelElement = this.field.$label[ 0 ];
        /**
         * @type {HTMLElement?}
         */
        this.indexElement = null;

        this.field.$label.empty();
        // Hide field input area if for group
        if ( !this.outerSection.isIndividual ) {
            this.field.getField().toggle( false );
        }

        if ( this.outerSection.panel.map.checkFeatureFlag( MapFlags.ShowCoordinates ) ) {
            Util.createDomElement( 'b', {
                text: this.outerSection.panel.map.crs.makeLabel( this.marker.apiInstance ),
                appendTo: this.labelElement
            } );
        }

        /**
         * @type {HTMLElement}
         */
        this.labelTextElement = Util.createDomElement( 'span', {
            appendTo: this.labelElement
        } );
        if ( this.slots.label ) {
            const groupName = this.outerSection.group.name;
            let labelText = this.slots.label;
            if ( labelText.indexOf( groupName ) === 0 ) {
                labelText = labelText.slice( groupName.length ).trim().replace( /(^\(|\)$)/g, '' );
                if ( labelText.length <= 2 ) {
                    labelText = this.slots.label;
                }
            }
            this.labelTextElement.innerHTML = labelText;
        }

        if ( Util.isBitSet( this.outerSection.group.flags, MarkerGroupFlags.IsNumberedInChecklists ) ) {
            this.setIndex( this.outerSection.rows.length + 1 );
        }

        this.field.$header.on( 'click', event => {
            event.preventDefault();
            this.marker.openPopup();
        } );
    }


    /**
     * @param {number} index
     */
    setIndex( index ) {
        if ( this.indexElement === null ) {
            this.indexElement = Util.createDomElement( 'span', {
                classes: [ 'ext-datamaps-collapsible-index' ],
                appendTo: this.labelTextElement
            } );
        }
        this.indexElement.innerText = ` #${index}`;
    }
};


module.exports = CollectiblesPanel;
