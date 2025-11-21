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
        case MarkerPresentationType.PIN:
            const iconSize = [ style.getSize(), style.getSize() ];
            const icon = new Leaflet.DivIcon( {
                iconSize,
            } );
            icon.createIcon = oldElement => {
                if ( !( oldElement && oldElement.tagName === 'SVG' ) ) {
                    const root = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
                    root.classList.add( 'leaflet-marker-icon' );
                    root.setAttribute( 'viewBox', '0 0 20 20' );
                    const path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
                    path.setAttribute( 'd', 'M 10,0 C 5.4971441,-0.21118927 1.7888107,3.4971441 2,8 c 0,2.52 2,5 3,6 1,1 5,6 5,6 0,0 4,-5 5,'
                        + '-6 1,-1 3,-3.48 3,-6 0.211189,-4.5028559 -3.497144,-8.21118927 -8,-8 z' );
                    path.setAttribute( 'fill', retval.fillColor );
                    path.setAttribute( 'stroke', retval.color );
                    path.setAttribute( 'stroke-width', retval.weight );
                    const circle = document.createElementNS( 'http://www.w3.org/2000/svg', 'circle' );
                    circle.setAttribute( 'cx', '10' );
                    circle.setAttribute( 'cy', '8' );
                    circle.setAttribute( 'r', '3.3' );
                    circle.setAttribute( 'fill', '#0009' );
                    root.style.width = `${iconSize[ 0 ]}px`;
                    root.style.height = `${iconSize[ 1 ]}px`;
                    root.appendChild( path );
                    root.appendChild( circle );
                    oldElement = root;
                }
                return oldElement;
            };
            retval.icon = icon;
            retval.static = true;
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
            case MarkerPresentationType.PIN:
                return tryFactory( 'MarkerFeature.Pin', f );
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
    'MarkerFeature.Pin'( f ) {
        const
            [ swX, swY ] = f.getLocation(),
            style = f.getMarkerType().getStyle();

        return new Leaflet.Marker( [ swY, swX ], styleToLeaflet( style, MarkerPresentationType.PIN ) );
    },
} );
