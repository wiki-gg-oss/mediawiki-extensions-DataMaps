module.exports = class MarkerSearchEngine {
    #featureTree;


    constructor( featureTree ) {
        this.#featureTree = featureTree;
    }


    async getIndexableFeatures() {

    }


    normalisePhrase( value ) {
        return value
            // Self-explanatory
            .trim()
            // Reduce adjacent white-space
            .replace( /\s+/, ' ' )
            // Self-explanatory
            .toLowerCase()
            // Normalize UTF characters with canonical decomposition
            .normalize( 'NFD' )
            // Remove characters that are of no use
            .replace( /[\u0300-\u036f]/g, '' );
    }


    async query( phrase ) {
        throw new Error( `${this.constructor.name}.query not implemented` );
    }
};
