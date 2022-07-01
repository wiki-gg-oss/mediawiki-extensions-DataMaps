function MarkerLegendPanel( legend, name ) {
    this.legend = legend;
    this.map = this.legend.map;
    // Root DOM element
    this.$root = this.legend.addTab( name ).$element;
    //
    this.buttonGroup = new OO.ui.ButtonGroupWidget( {} );
    //
    this.$groupContainer = $( '<div class="datamap-container-groups">' ).appendTo( this.$root );
    //
    this.groupToggles = [];
    // 
    this.$layersPopup = null;

    // Prepend the button group to the root element
    this.buttonGroup.$element.prependTo( this.$root );
}


MarkerLegendPanel.prototype.createActionButton = function ( label, clickCallback ) {
    const button = new OO.ui.ButtonWidget( { label: label } );
    button.on( 'click', clickCallback );
    this.buttonGroup.addItems( [ button ] );
    return button;
};


MarkerLegendPanel.prototype.createPopupButton = function ( label ) {
    const $content = $( '<div>' );
    const button = new OO.ui.PopupButtonWidget( { 
        label: label,
        indicator: 'down',
        popup: {
            $content: $content,
            padded: true,
            width: 220,
            align: 'forwards'
        }
    } );
    this.buttonGroup.addItems( [ button ] );
    return [ button, $content ];
};


MarkerLegendPanel.prototype.toggleAllGroups = function ( state ) {
    this.groupToggles.forEach( checkbox => checkbox.setSelected( state ) );
};


MarkerLegendPanel.prototype.addTotalToggles = function () {
    this.createActionButton( mw.msg( 'datamap-toggle-show-all' ), this.toggleAllGroups.bind( this, true ) );
    this.createActionButton( mw.msg( 'datamap-toggle-hide-all' ), this.toggleAllGroups.bind( this, false ) );
};


MarkerLegendPanel.prototype.initialiseLayersArea = function () {
    this.$layersPopup = this.createPopupButton( mw.msg( 'datamap-layer-control' ) )[1];
};


MarkerLegendPanel.prototype.addMarkerLayerToggle = function ( layerId, layerName ) {
    this.legend.createCheckboxField( this.$layersPopup, layerName, true, state => {
        // Update visibility mask for this group
        this.map.layerVisibilityMask[layerId] = state;
        // Update visibility for any marker of this group with any visible layer
        this.map.updateLayerVisibility( layerNames => {
            return layerNames.indexOf( layerId ) >= 0 && this.map.groupVisibilityMask[layerNames[0]];
        }, state );
    } );
};


MarkerLegendPanel.prototype.addMarkerGroupToggle = function ( groupId, group ) {
    const pair = this.legend.createCheckboxField( this.$groupContainer, group.name, true, state => {
        // Update visibility mask for this group
        this.map.groupVisibilityMask[groupId] = state;
        // Update visibility for any marker of this group with any visible layer
        this.map.updateLayerVisibility( layerNames => {
            return layerNames[0] == groupId
                && layerNames.filter( x => this.map.layerVisibilityMask[x] === false ).length == 0;
        }, state );
    } );
    const field = pair[1];

    if ( group.fillColor ) {
        $( '<div class="datamap-legend-circle">' ).css( {
            width: group.size+4,
            height: group.size+4,
            backgroundColor: group.fillColor,
            borderColor: group.strokeColor || group.fillColor,
            borderWidth: group.strokeWidth || 1,
        } ).prependTo( field.$header );
    }

    if ( group.legendIcon ) {
        field.$header.prepend( $( '<img width=24 height=24/>' ).attr( 'src', group.legendIcon ) );
    }

    this.groupToggles.push( pair[0] );
};


module.exports = MarkerLegendPanel;