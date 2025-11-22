const
    MapEmbed = require( './MapEmbed.js' ),
    PopupData = require( './features/PopupData.js' ),
    MarkerPresentationType = require( './markers/MarkerPresentationType.js' ),
    { MarkerTypeInfo } = require( './stores/MarkerTypesStore.js' ),
    apiClient = new mw.Api();


async function fetchMapConfig( pageId, revId ) {
    return await apiClient.get( {
        action: 'getmap',
        pageid: pageId,
        revid: revId,
        prop: [ 'config' ],
    } );
}


async function fetchMapFeatures( pageId, revId ) {
    return await apiClient.get( {
        action: 'getmap',
        pageid: pageId,
        revid: revId,
        prop: [ 'features' ],
    } );
}


function decodeVec( value ) {
    if ( value === null || value === undefined ) {
        return [ 0, 0 ];
    }
    if ( typeof( value ) === 'number' ) {
        return [ value, value ];
    }
    return value;
}


function buildMarkersFromArray( featureFactory, markerType, liveMarkers ) {
    for ( const markerInfo of liveMarkers ) {
        const
            locationVec = [ markerInfo[ 0 ], markerInfo[ 1 ] ],
            props = markerInfo[ 2 ] || {};

        let popupData = null;
        if (
            'titleHtml' in props
            || 'descHtml' in props
            || 'imgUrl' in props
        ) {
            const [ imageWidth, imageHeight ] = decodeVec( props.imgDimens );
            popupData = new PopupData( {
                titleHtml: props.titleHtml,
                descHtml: props.descHtml,
                imageUrl: props.imgUrl,
                imageWidth,
                imageHeight,
            } );
        }

        featureFactory.createMarker( {
            location: locationVec,
            markerType,
            popupData,
        } );
    }
}


function buildFeaturesFromArray( featureFactory, markerTypeManager, liveFeatures ) {
    for ( const featureInfo of liveFeatures ) {
        const
            typeName = featureInfo[ 0 ],
            locationVec = decodeVec( featureInfo[ 1 ] ),
            props = featureInfo[ 2 ],
            childNodes = featureInfo[ 3 ];

        switch ( typeName ) {
            case 'FeatureCollection':
                buildFeaturesFromArray( featureFactory, markerTypeManager, childNodes );
                break;
            
            case 'BackgroundImage':
                const dimens = decodeVec( props.dimens );
                featureFactory.createBackgroundImage( {
                    location: locationVec,
                    width: dimens[ 0 ],
                    height: dimens[ 1 ],
                    imageUrl: props.imageUrl,
                } );
                break;
            
            case 'Text':
                featureFactory.createText( {
                    location: locationVec,
                    text: props.html,
                } );
                break;
            
            case 'MarkerCollection':
                const markerType = markerTypeManager.getTypeById( props.markerType );
                buildMarkersFromArray( featureFactory, markerType, childNodes );
                break;
            
            default:
                console.debug( `[Navigator] Unknown feature type name: ${typeName}` );
        }
    }
}


function importMarkerTypeSettings( markerType, info ) {
    markerType.setName( info.name );
    markerType.setDescriptionHtml( info.descriptionHtml );

    switch ( info.type ) {
        case 'MarkerType':
            const
                style = markerType.getStyle(),
                styleInfo = info.defaultStyle;
            style.setPointForm( MarkerPresentationType.fromString( styleInfo.pointForm ) );
            style.setSize( styleInfo.size );
            if ( 'fill' in styleInfo ) {
                style.setFillColour( styleInfo.fill.colour );
                style.setFillOpacity( styleInfo.fill.opacity );
            }
            if ( 'outline' in styleInfo ) {
                style.setOutlineColour( styleInfo.outline.colour );
                style.setOutlineOpacity( styleInfo.outline.opacity );
                style.setOutlineWidth( styleInfo.outline.width );
            }
            break;

        case 'Group':
            markerType.setForbidsDisplay( true );
            break;

        default:
            throw new Error( `Cannot initialise a marker type with intent of ${info.type}` );
    }
}


async function initialiseEmbed( mountTargetElement ) {
    // Unserialise the embed config
    const initConfig = JSON.parse( mountTargetElement.getAttribute( 'data-mw-navigator' ) );
    // TODO: check the metadata version /again/

    // Construct the viewer without the viewport, and reserve screen space to indicate progress
    mountTargetElement.classList.add( 'ext-navi-map--ready' );
    const embed = new MapEmbed( mountTargetElement );

    const liveConfig = ( await fetchMapConfig( initConfig.pageId, initConfig.revId ) ).map;

    embed.setSourceCodeTitle( liveConfig.title );

    // Apply the configuration properties
    embed.setSubtitleHtml( liveConfig.subtitleHtml );

    // Apply marker type configuration
    const markerTypeManager = embed.getMarkerTypeManager();
    for ( const markerTypeInfo of liveConfig.markerTypes ) {
        const markerType = markerTypeManager.createType( markerTypeInfo.id );
        importMarkerTypeSettings( markerType, markerTypeInfo );

        if ( markerTypeInfo.subTypes ) {
            for ( const subTypeInfo of markerTypeInfo.subTypes ) {
                const subType = markerTypeManager.createSubType( markerType, subTypeInfo.id );
                importMarkerTypeSettings( subType, subTypeInfo );
            }
        }
    }
    markerTypeManager.propagateState();

    // Create features
    {
        const liveFeatures = ( await fetchMapFeatures( initConfig.pageId, initConfig.revId ) ).map.features;
        embed.getFeatureTree().openMutationSection( featureFactory => {
            buildFeaturesFromArray( featureFactory, embed.getMarkerTypeManager(), liveFeatures );
        } );
    }

    // Enable the viewport now that the core setup is done
    embed.getViewportManager().enable();

    window.dataMapsEmbed = embed;

    return embed;
}


module.exports = {
    initialiseEmbed,
};
