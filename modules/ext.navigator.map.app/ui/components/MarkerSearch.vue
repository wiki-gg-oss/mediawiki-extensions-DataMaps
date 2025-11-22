<template>
    <div class="ext-navi-map-search">
        <cdx-search-input
            placeholder="[PH]Search..."
            v-model="searchPhrase"
            @update:model-value="onUpdateSearchPhrase"
        >
    		<cdx-menu
    			:id="menuId"
    			ref="menu"
    			v-model:expanded="isMenuExpanded"
    			:show-pending="isSearchRunning"
    			:menu-items="searchResults"
                :visible-item-limit="8"
			    v-model:selected="selectedResult"
    		>
    			<template #pending>
    				[PH]Loading results...
    			</template>
			    <template #no-results>
			    	[PH]No results found
			    </template>
    		</cdx-menu>
        </cdx-search-input>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxMenu, CdxSearchInput } = require( '@wikimedia/codex' ),
    { useMarkerSearchEngine } = require( '../InjectedSymbol.js' );

// @vue/component
module.exports = {
	name: 'MarkerSearch',
    components: {
        CdxMenu, CdxSearchInput,
    },
    setup() {
        const menu = ref();
        return {
            menuId: 'searchmenu',
            menu,
            searchEngine: useMarkerSearchEngine(),
            searchPhrase: ref( '' ),
            isMenuExpanded: ref( false ),
            isSearchRunning: ref( true ),
            searchResults: ref( [] ),
            selectedResult: ref( '' ),
        };
    },
    methods: {
        onUpdateSearchPhrase() {
            if ( this.searchPhrase === '' ) {
                this.isMenuExpanded = false;
                this.isSearchRunning = true;
                this.searchResults = [];
                return;
            }

            this.isMenuExpanded = true;
            this.isSearchRunning = true;

            this.searchEngine.query( this.searchPhrase ).then( results => {
                console.debug( `[Navigator] Marker search finished for phrase '${this.searchPhrase}': `, results );
                this.isSearchRunning = false;
                this.searchResults = results.map( item => ( {
                    label: item.name,
                    value: item.featureId,
                } ) );
            } );
        },
    },
};
</script>
