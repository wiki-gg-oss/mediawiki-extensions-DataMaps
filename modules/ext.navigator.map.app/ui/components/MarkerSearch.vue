<template>
    <div class="ext-navi-map-search">
        <cdx-search-input
            placeholder="[PH]Search..."
            v-model="searchPhrase"
            @update:model-value="onUpdateSearchPhrase"
        >
        </cdx-search-input>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxSearchInput } = require( '@wikimedia/codex' ),
    { useMarkerSearchEngine } = require( '../InjectedSymbol.js' );

// @vue/component
module.exports = {
	name: 'MarkerSearch',
    components: {
        CdxSearchInput,
    },

    methods: {
        onUpdateSearchPhrase() {
            this.searchEngine.query( this.searchPhrase ).then( results => {
                console.debug( `[Navigator] Marker search finished for phrase '${this.searchPhrase}': `, results );
            } );
        },
    },

    setup() {
        return {
            searchEngine: useMarkerSearchEngine(),
            searchPhrase: ref( '' ),
        };
    },
};
</script>
