const
    MarkerType = require( './MarkerType.js' ),
    useMarkerTypes = require( '../stores/MarkerTypesStore.js' );


module.exports = class MarkerTypeManager {
    #types;
    #typeById;
    #uiState;


    constructor( pinia ) {
        this.#types = [];
        this.#typeById = {};
        this.#uiState = useMarkerTypes( pinia );
    }


    createType( id ) {
        const retval = new MarkerType( id );
        this.#types.push( retval );
        this.#typeById[ id ] = retval;
        return retval;
    }


    propagateState() {
        this.#uiState.setMetadata( this.#types.map( item => item.asTransientMetadata() ) );
    }
};
