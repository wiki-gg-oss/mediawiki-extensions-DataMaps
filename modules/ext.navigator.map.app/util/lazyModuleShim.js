module.exports = function createLazyModuleShim( debugName, moduleName, callerExports ) {
    let promiseInstance = null;


    async function resolveInternal() {
        console.debug( `[Navigator] ${debugName} shim called, loading the library...` );
        await mw.loader.using( moduleName );
        Object.assign( callerExports, require( moduleName ) );
        console.debug( `[Navigator] ${debugName} done loading` );
    }


    callerExports.resolve = () => {
        if ( !promiseInstance ) {
            promiseInstance = resolveInternal();
        }
        return promiseInstance;
    };
};
