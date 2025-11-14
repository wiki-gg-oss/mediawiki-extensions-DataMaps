const
    MarkerType = require( './MarkerType.js' ),
    useMarkerTypes = require( '../stores/MarkerTypesStore.js' );


module.exports = class MarkerTypeManager {
    #types;
    #rootTypes;
    #typeById;
    #uiState;
    #dynamicIdCounter = 0;


    constructor( pinia ) {
        this.#types = [];
        this.#rootTypes = [];
        this.#typeById = {};
        this.#uiState = useMarkerTypes( pinia );
    }


    #validateId( value ) {
        if ( value === null || value === undefined ) {
            return `<dyn+${++this.#dynamicIdCounter}>`;
        }

        return value;
    }


    #createTypeInternal( id ) {
        id = this.#validateId( id );

        const retval = new MarkerType( id );
        this.#types.push( retval );
        this.#typeById[ id ] = retval;
        return retval;
    }


    createType( id ) {
        const retval = this.#createTypeInternal( id );
        this.#rootTypes.push( retval );
        return retval;
    }


    createSubType( parentType, id ) {
        // TODO: check we own this type - and maybe add a ref to this object on the other side cause we should not be
        // concerned with foreign objects, nor should pushChildInternal be public

        const retval = this.#createTypeInternal( id );
        parentType.pushChildInternal( retval );
        return retval;
    }


    propagateState() {
        this.#uiState.setMetadata( this.#rootTypes.map( item => item.asTransientMetadata() ) );
    }
};
