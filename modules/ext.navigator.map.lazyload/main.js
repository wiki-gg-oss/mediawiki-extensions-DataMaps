const KnownInitMetadataVersions = Object.freeze( [
    1,
] );
const MapInitFlag = Object.freeze( {
    None: 0,
    Lazy: 1 << 0,
} );


function trap( fn ) {
    if ( mw.config.get( 'debug' ) ) {
        return fn();
    }

    try {
        return fn();
    } catch ( ex ) {
        setTimeout( () => { throw ex } );
        return null;
    }
}


function setup( element ) {
    // Unserialise the embed config
    const initConfig = JSON.parse( element.getAttribute( 'data-mw-navigator' ) );
    // Confirm the metadata version is one we provide support for
    if ( !KnownInitMetadataVersions.includes( initConfig['$version'] ) ) {
        throw new Error( `Unknown map metadata version: ${initConfig['$version']}` );
    }
    // Confirm the lazy flag is set, and silently bail otherwise
    if ( !( initConfig.flags & MapInitFlag.Lazy ) ) {
        return;
    }

    // Precheck is done; let's set up the button
    const
        messageElement = element.querySelector( ':scope > div.ext-navigator-statusmsg' ),
        buttonElement = messageElement.querySelector( ':scope > .cdx-button' );
    buttonElement.addEventListener( 'mousedown', () => {
        const progressBarInnerElement = document.createElement( 'div' );
        progressBarInnerElement.classList.add( 'cdx-progress-bar__bar' );

        const progressBarElement = document.createElement( 'div' );
        progressBarElement.classList.add( 'cdx-progress-bar', 'cdx-progress-bar--inline' );
        progressBarElement.role = 'progressbar';
        progressBarElement.append( progressBarInnerElement );
        buttonElement.replaceWith( progressBarElement );

        mw.loader.using( 'ext.navigator.map.app', () => {
            require( 'ext.navigator.map.app' ).initialiseEmbed( element );
        } );
    } );
    buttonElement.removeAttribute( 'disabled' );
}


mw.hook( 'wikipage.content' ).add( $content => {
    for ( const element of $content.find( '.ext-navigator-map[ data-mw-navigator ]' ) ) {
        trap( () => setup( element ) );
    }
} );
