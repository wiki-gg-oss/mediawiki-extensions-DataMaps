module.exports = Object.freeze( {
    ICON: 1,
    RECTANGLE: 2,
    PIN: 3,
    CIRCLE: 4,


    fromString( value ) {
        return this[ value.toUpperCase() ];
    },
} );
