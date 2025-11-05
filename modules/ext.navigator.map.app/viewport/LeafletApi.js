let promiseInstance = null;


async function resolveInternal() {
    console.debug( '[Navigator] Leaflet shim called, loading the library...' );
    await mw.loader.using( 'ext.datamaps.leaflet' );
    Object.assign( module.exports, require( 'ext.datamaps.leaflet' ) );
    console.debug( '[Navigator] Leaflet done loading' );
}


module.exports.resolve = () => {
    if ( !promiseInstance ) {
        promiseInstance = resolveInternal();
    }
    return promiseInstance;
};
