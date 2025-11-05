let promiseInstance = null;


async function resolveInternal() {
    console.debug( '[Navigator] Fuzzysort shim called, loading the library...' );
    await mw.loader.using( 'ext.navigator.3rdparty.fuzzysort' );
    Object.assign( module.exports, require( 'ext.navigator.3rdparty.fuzzysort' ) );
    console.debug( '[Navigator] Fuzzysort done loading' );
}


module.exports.resolve = () => {
    if ( !promiseInstance ) {
        promiseInstance = resolveInternal();
    }
    return promiseInstance;
};
