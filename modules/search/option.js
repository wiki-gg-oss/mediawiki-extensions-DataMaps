const Leaflet = require( 'ext.datamaps.leaflet' );


function MenuOptionWidget( config ) {
	MenuOptionWidget.super.call( this, config );

    this.data = config.data;
    this.keywords = config.keywords;
    this.$tab = config.$tab;

    if ( config.badge ) {
        this.$badge = $( '<span class="datamap-search-badge">' )
            .text( config.badge )
            .prependTo( this.$label );
        if ( config.badgeCurrent ) {
            this.$badge.addClass( 'current' );
        }
    }

    const icon = this.data.leafletMarker instanceof Leaflet.Ark.IconMarker
        ? this.data.map.getIconFromLayers( this.data.leafletMarker.attachedLayers ) : null;
    if ( icon ) {
        this.$arkIcon = $( '<img width=16 height=16 />' )
            .attr( {
                src: icon.options.iconUrl
            } )
            .prependTo( this.$label );
    }
}
OO.inheritClass( MenuOptionWidget, OO.ui.MenuOptionWidget );


module.exports = MenuOptionWidget;
