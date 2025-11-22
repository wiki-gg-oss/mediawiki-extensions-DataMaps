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

        this.#indexedItems = ( await this.getIndexableFeatures() ).map( feature => {
            const meta = feature.getSearchMetadata();
            return {
                target: {
                    featureId: feature.getId(),
                    name: meta.name,
                },
                identifiers: meta.phrases.filter( item => !!item ).map( item => [
                    Fuzzysort.prepare( this.normalisePhrase( item[ 1 ] ) ),
                    item[ 0 ],
                ] ),
            };
        } );
        console.debug( `[Navigator] Indexed ${this.#indexedItems.length} features for marker search` );
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
