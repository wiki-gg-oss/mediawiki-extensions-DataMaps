const
    MapEmbed = require( './MapEmbed.js' ),
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
    if ( typeof( value ) === 'number' ) {
        return [ value, value ];
    }
    return value;
}


function buildMarkersFromArray( featureFactory, markerType, liveMarkers ) {
    for ( const markerInfo of liveMarkers ) {
        const
            locationVec = decodeVec( markerInfo[ 0 ] ),
            props = markerInfo[ 1 ];

        featureFactory.createMarker( {
            location: locationVec,
            markerType,
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


async function initialiseEmbed( mountTargetElement ) {
    // Unserialise the embed config
    const initConfig = JSON.parse( mountTargetElement.getAttribute( 'data-mw-navigator' ) );
    // TODO: check the metadata version /again/

    // Construct the viewer without the viewport, and reserve screen space to indicate progress
    mountTargetElement.classList.add( 'ext-navi-map--ready' );
    const embed = new MapEmbed( mountTargetElement );

    const liveConfig = ( await fetchMapConfig( initConfig.pageId, initConfig.revId ) ).map;

    // Apply the configuration properties
    embed.setSubtitleHtml( liveConfig.subtitleHtml );

    // Apply marker type configuration
    const markerTypeManager = embed.getMarkerTypeManager();
    for ( const markerTypeInfo of liveConfig.markerTypes ) {
        const markerType = markerTypeManager.createType( markerTypeInfo.id );
        markerType.setName( markerTypeInfo.name );
        markerType.setDescriptionHtml( markerTypeInfo.descriptionHtml );

        if ( markerTypeInfo.subTypes ) {
            for ( const subTypeInfo of markerTypeInfo.subTypes ) {
                const subType = markerTypeManager.createSubType( markerType, subTypeInfo.id );
                // TODO: we need an API->runtime factory
                subType.setName( subTypeInfo.name );
                subType.setDescriptionHtml( subTypeInfo.descriptionHtml );
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
