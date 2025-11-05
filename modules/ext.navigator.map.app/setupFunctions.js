const
    MapEmbed = require( './MapEmbed.js' );


function initialiseEmbed( mountTargetElement ) {
    mountTargetElement.classList.add( 'ext-navi-map--ready' );
    const embed = new MapEmbed( mountTargetElement );
    embed.getViewportManager().enable();
    return embed;
}


module.exports = {
    initialiseEmbed,
};
