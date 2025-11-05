const
    Fuzzysort = require( './FuzzysortApi.js' );


const
    SCORE_THRESHOLD = -75000;


module.exports = class MarkerSearchEngine {
    #indexedItems;


    constructor() {
        this.#indexedItems = null;
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


    async #ensureReady() {
        if ( this.#indexedItems ) {
            return;
        }

        await Fuzzysort.resolve();

        this.#indexedItems = [];
        this.#indexedItems.push( {
            keywords: [
                [ Fuzzysort.prepare( this.normalisePhrase( 'Hello world' ) ), 1 ],
            ],
            target: {
                name: 'Search test dummy data',
            },
        } );
    }


    async query( phrase ) {
        await this.#ensureReady();
        return Fuzzysort.go( this.normalisePhrase( phrase ), this.#indexedItems, {
            threshold: SCORE_THRESHOLD,
            weighedKey: 'keywords',
        } );
    }
};
