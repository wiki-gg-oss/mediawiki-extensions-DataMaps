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
    			:menu-items="[]"
    			:show-pending="true"
    		>
    			<template #pending>
    				[PH]Loading results...
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
        };
    },
    methods: {
        onUpdateSearchPhrase() {
            if ( this.searchPhrase === '' ) {
                this.isMenuExpanded = false;
                return;
            }

            this.isMenuExpanded = true;

            this.searchEngine.query( this.searchPhrase ).then( results => {
                console.debug( `[Navigator] Marker search finished for phrase '${this.searchPhrase}': `, results );
            } );
        },
    },
};
</script>
