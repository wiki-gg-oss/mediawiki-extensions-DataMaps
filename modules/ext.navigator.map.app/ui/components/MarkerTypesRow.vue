<template>
    <ul class="ext-navi-map-legend-groups">
        <legend-marker-type-entry
            v-for="item in dataStore.markerTypes"
            :key="item.id"
            :id="item.id"
            :is-expanded="false"
            :transient-data="item"
        >
        </legend-marker-type-entry>
    </ul>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    useMarkerTypesStore = require( '../stores/MarkerTypesStore.js' ),
    LegendMarkerTypeEntry = require( './LegendMarkerTypeEntry.vue' );


// @vue/component
module.exports = {
	name: 'MarkerTypesRow',
    components: {
        LegendMarkerTypeEntry,
    },
    setup() {
        return {
            dataStore: useMarkerTypesStore(),
        };
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

.ext-navi-map-legend-row > .cdx-accordion > .cdx-accordion__content {
    > .ext-navi-map-legend-groups {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;

        > li:not( :last-child )::after {
            content: '';
            display: block;
            width: calc( 100% - 0.4em * 2 );
            height: 0;
            box-shadow: 0 0 0 1px @border-color-divider;
            opacity: 0.2;
            position: absolute;
            margin-top: calc( 0.3rem / 2 );
        }
    }
}
</style>
