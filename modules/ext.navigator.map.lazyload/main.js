const KnownInitMetadataVersions = Object.freeze( [
    1,
] );
const MapInitFlag = Object.freeze( {
    None: 0,
    Lazy: 1 << 0,
} );


const
    INTERSECT_THRESHOLD = 0.1,
    INTERSECT_DELAY_TIME = 200;


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


function proxiedInitialiseEmbed( element ) {
    mw.loader.using( 'ext.navigator.map.app', () => {
        console.debug( `[Navigator] Moving container out of bootstrap stage:`, element );
        require( 'ext.navigator.map.app' ).initialiseEmbed( element );
    } );
}


function setup( element ) {
    console.debug( `[Navigator] Container setup request invoked for:`, element );

    // Unserialise the embed config
    const initConfig = JSON.parse( element.getAttribute( 'data-mw-navigator' ) );
    // Confirm the metadata version is one we provide support for
    if ( !KnownInitMetadataVersions.includes( initConfig['$version'] ) ) {
        throw new Error( `Unknown map metadata version: ${initConfig['$version']}` );
    }

    // Precheck is done. We handle both eager and lazy loading here, so the paths diverge depending on the flag
    if ( initConfig.flags & MapInitFlag.Lazy ) {
        // Embed is configured in lazily-loaded mode; let's set up the button
        console.debug( `[Navigator] Setting up manual lazy-loader on container:`, element );
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

            proxiedInitialiseEmbed( element );
        } );
        buttonElement.removeAttribute( 'disabled' );
    } else {
        // Embed is configured in eagerly-loaded mode; let's set up an IntersectionObserver and initialise the embed
        // once it's sufficiently in the viewport
        console.debug( `[Navigator] Setting up intersection loader on container:`, element );
        // TODO: ideally we'd reuse this observer
        // TODO: possible memory leak if the element gets detached before init?
        let timeoutId = null;
        const observer = new IntersectionObserver(
            ( entries, observer ) => {
                if ( entries[ 0 ].isIntersecting ) {
                    // Map's scrolled into view, let's queue up the load after a short while
                    if ( !timeoutId ) {
                        console.debug( `[Navigator] Container in view, scheduling for loading:`, element );
                        timeoutId = setTimeout( () => {
                            observer.disconnect();
                            proxiedInitialiseEmbed( element );
                        }, INTERSECT_DELAY_TIME );
                    }
                } else if ( timeoutId ) {
                    // Map scrolled out of the view while still connected here, cancel the load
                    console.debug( `[Navigator] Container out of view, cancelling load:`, element );
                    clearTimeout( timeoutId );
                    timeoutId = null;
                }
            },
            {
                threshold: INTERSECT_THRESHOLD,
            }
        );
        observer.observe( element );
    }
}


mw.hook( 'wikipage.content' ).add( $content => {
    for ( const element of $content.find( '.ext-navigator-map[ data-mw-navigator ]' ) ) {
        trap( () => setup( element ) );
    }
} );
