module.exports = Object.freeze( {
    ICON: 1,
    RECTANGLE: 2,
    PIN: 3,
    CIRCLE: 4,


    fromString( value ) {
        return this[ value.toUpperCase() ];
    },


    toCanonicalName( value ) {
        const retval = ( {
            [module.exports.ICON]: 'icon',
            [module.exports.RECTANGLE]: 'rectangle',
            [module.exports.PIN]: 'pin',
            [module.exports.CIRCLE]: 'circle',
        } )[ value ];
        if ( !retval ) {
            throw new Error( `Invalid MarkerPresentationType value: ${value}` );
        }
        return retval;
    },
} );
