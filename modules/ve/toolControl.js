/** @typedef {import( './editor.js' )} MapVisualEditor */
const { Controls, CRSOrigin, Util } = require( 'ext.datamaps.core' ),
    { DomUtil } = require( 'ext.datamaps.leaflet' );


/**
 * @abstract
 */
module.exports = class ToolBarControl extends Controls.MapControl {
    /**
     * @param {MapVisualEditor} editor Owning editor.
     */
    constructor( editor ) {
        super( editor.map, 've-toolbar' );

        /**
         * @type {MapVisualEditor}
         */
        this.editor = editor;
        /**
         * @type {HTMLElement}
         */
        this._controlsElement = Util.createDomElement( 'div', {
            classes: [ 'ext-datamaps-control-ve-toolbar-controls' ],
            appendTo: this.element
        } );
        /**
         * @type {HTMLElement}
         */
        this._coordinatesElement = Util.createDomElement( 'span', {
            classes: [ 'ext-datamaps-control-ve-toolbar-coordinates' ],
            appendTo: this.element
        } );
        /**
         * @private
         * @type {LeafletModule.LatLng?}
         */
        this._latlng = null;

        this.setVisible( false );
        this.editor.map.leaflet.on( 'click', this._onMapClick, this );
        this.editor.map.leaflet.on( 'move', this._onMapMove, this );
    }


    /**
     * @private
     * @param {LeafletModule.EventHandling.MouseEvent} event
     */
    _onMapClick( event ) {
        if ( this.isVisible() ) {
            this.setVisible( false );
            return;
        }

        this.openAt( event.latlng );
    }


    /**
     * @private
     */
    _onMapMove() {
        if ( this.isVisible() ) {
            this._updatePosition();
        }
    }


    /**
     * @param {LeafletModule.LatLng} latlng
     */
    openAt( latlng ) {
        this._latlng = latlng;

        this.setVisible( true );

        // TODO: should be extracted for generic use
        let lat = this._latlng.lat / this.editor.map.crsScaleY;
        const lon = this._latlng.lng / this.editor.map.crsScaleX;
        if ( this.editor.map.crsOrigin === CRSOrigin.TopLeft ) {
            lat = this.editor.map.config.crs[ 1 ][ 0 ] - lat;
        }
        this._coordinatesElement.innerHTML = this.editor.map.getCoordinateLabel( lat, lon ).replace( ', ', '<br/>' );

        this._updatePosition();
    }


    /**
     * @private
     */
    _updatePosition() {
        const pos = this.map.leaflet.latLngToContainerPoint( Util.getNonNull( this._latlng ) );
        pos.x -= this.element.offsetWidth / 2;
        pos.y += 10;
        DomUtil.setPosition( this.element, pos );
    }
};