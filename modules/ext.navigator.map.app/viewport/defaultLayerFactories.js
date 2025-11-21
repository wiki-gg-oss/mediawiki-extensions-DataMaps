const
    Leaflet = require( './LeafletApi.js' ),
    MarkerPresentationType = require( '../markers/MarkerPresentationType.js' );


function styleToLeaflet( style, format ) {
    // TODO: options object should be cached
    const retval = {};
    retval.fill = style.hasFill();
    retval.stroke = style.hasOutline();
    if ( retval.fill ) {
        retval.fillColor = style.getFillColour();
        retval.fillOpacity = style.getFillOpacity();
    }
    if ( retval.stroke ) {
        retval.color = style.getOutlineColour();
        retval.opacity = style.getOutlineOpacity();
        retval.weight = style.getOutlineWidth();
    }
    switch ( format ) {
        case MarkerPresentationType.CIRCLE:
            retval.radius = style.getSize() / 2;
            break;
    }
    return retval;
}


module.exports = Object.freeze( {
    BackgroundImageFeature( f ) {
        const
            [ swX, swY ] = f.getLocation(),
            w = f.getWidth(),
            h = f.getHeight(),
            neX = swX + w,
            neY = swY + h;
        return new Leaflet.ImageOverlay(
            f.getUrl(),
            [ [ neY, neX ], [ swY, swX ] ]
        );
    },
    TextFeature( f ) {
        const
            [ swX, swY ] = f.getLocation();
        // TODO: need an actual text overlay implementation
        const retval = new Leaflet.DivOverlay( [ swY, swX ], {
            pane: 'markerPane',
        } );
        retval._initLayout = function () {
            this._container = document.createElement( 'div' );
            const textContainer = document.createElement( 'span' );
            textContainer.style.position = 'absolute';
            textContainer.style.transform = 'translate(-50%) translateY(-50%)';
            textContainer.style.whiteSpace = 'nowrap';
            textContainer.innerHTML = f.getHtml();
            this._container.append( textContainer );
        };
        retval._updateLayout = function () {
            this._container.style.opacity = 1;
        };
        retval._adjustPan = () => {};
        return retval;
    },
    MarkerFeature( f, tryFactory ) {
        switch ( f.getPresentationType() ) {
            case MarkerPresentationType.CIRCLE:
                return tryFactory( 'MarkerFeature.Circle', f );

            default:
                console.debug( `[Navigator] Missing support for marker presentation form with ID of ${f.getPresentationType()}` );
                return null;
        }
    },
    'MarkerFeature.Circle'( f ) {
        const
            [ swX, swY ] = f.getLocation(),
            style = f.getMarkerType().getStyle();

        return new Leaflet.Circle( [ swY, swX ], styleToLeaflet( style, MarkerPresentationType.CIRCLE ) );
    },
} );
