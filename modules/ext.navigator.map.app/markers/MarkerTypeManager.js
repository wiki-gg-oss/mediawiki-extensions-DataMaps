const
    MarkerType = require( './MarkerType.js' ),
    useMarkerTypes = require( '../stores/MarkerTypesStore.js' );


module.exports = class MarkerTypeManager {
    #types;
    #typeById;
    #uiState;
    #dynamicIdCounter = 0;


    constructor( pinia ) {
        this.#types = [];
        this.#typeById = {};
        this.#uiState = useMarkerTypes( pinia );
    }


    createType( id ) {
        if ( id === null || id === undefined ) {
            id = `<dyn+${++this.#dynamicIdCounter}>`;
        }

        const retval = new MarkerType( id );
        this.#types.push( retval );
        this.#typeById[ id ] = retval;
        return retval;
    }


    propagateState() {
        this.#uiState.setMetadata( this.#types.map( item => item.asTransientMetadata() ) );
    }
};
