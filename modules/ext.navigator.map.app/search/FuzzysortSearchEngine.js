const
    MarkerSearchEngine = require( './MarkerSearchEngine.js' ),
    Fuzzysort = require( './FuzzysortApi.js' );


const
    SCORE_THRESHOLD = -75000;


module.exports = class FuzzysortSearchEngine extends MarkerSearchEngine {
    #indexedItems;


    constructor( featureTree ) {
        super( featureTree );
        this.#indexedItems = null;
    }


    async #ensureReady() {
        if ( this.#indexedItems ) {
            return;
        }

        await Fuzzysort.resolve();

        this.#indexedItems = [];
        this.#indexedItems.push( {
            identifiers: [
                [ Fuzzysort.prepare( this.normalisePhrase( 'Hello world' ) ), 1 ],
            ],
            target: {
                name: 'Search test dummy data',
                featureId: 1,
            },
        } );
        this.#indexedItems.push( {
            identifiers: [
                [ Fuzzysort.prepare( this.normalisePhrase( 'Hello aaa' ) ), 1 ],
            ],
            target: {
                name: 'Search test dummy data',
                featureId: 1,
            },
        } );
        this.#indexedItems.push( {
            identifiers: [
                [ Fuzzysort.prepare( this.normalisePhrase( 'Hello' ) ), 1 ],
            ],
            target: {
                name: 'Search test dummy data',
                featureId: 1,
            },
        } );

        await new Promise( resolve => {
            setTimeout( resolve, 2000 );
        } )
    }


    async query( phrase ) {
        await this.#ensureReady();
        return Fuzzysort.go( this.normalisePhrase( phrase ), this.#indexedItems, {
            threshold: SCORE_THRESHOLD,
            weighedKey: 'identifiers',
        } )
            .map( item => item.obj.target );
    }
};
