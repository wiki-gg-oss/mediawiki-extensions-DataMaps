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
    }
    markerTypeManager.propagateState();

    // Enable the viewport now that the core setup is done
    embed.getViewportManager().enable();
    return embed;
}


module.exports = {
    initialiseEmbed,
};
