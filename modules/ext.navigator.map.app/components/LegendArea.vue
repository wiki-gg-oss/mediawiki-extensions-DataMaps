<template>
    <div
        class="ext-navi-map-legend"
        :class="isOpen ? undefined : 'ext-navi-map-legend--collapsed'"
    >
        <legend-row
            v-for="item in enabledSections"
            :row-type="item.type"
            :row-data="item"
        >
            <component :is="item.component"></component>
        </legend-row>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require('vue'),
    { CdxAccordion, CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton } = require( '@wikimedia/codex' ),
    LegendRow = require( './LegendRow.vue' ),
    uiIcons = require( '../data/icons.json' );


// @vue/component
module.exports = {
    name: 'LegendArea',
    components: {
        CdxAccordion, CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
        LegendRow,
    },
    props: {
        isOpen: {
            type: [ Boolean ],
            required: true
        },
        sectionData: {
            type: Array,
            required: true,
        },
    },
    setup() {
        return {
            uiIcons,
        };
    },
    computed: {
        enabledSections() {
            return this.sectionData.filter( item => !item.hidden );
        },
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

.ext-navi-map-legend {
    display: flex;
    flex-direction: column;
    z-index: @z-index-stacking-2;
    background: @background-color-interactive-subtle;
    border: 1px solid @background-color-progressive;
    border-radius: 3px;
    font-size: 0.95em;

    &-btn {
        position: relative;
        overflow: unset;

        &.cdx-toggle-button--toggled-on::after {
            content: '';
            position: absolute;
            top: calc( 100% - 1px );
            left: -1px;
            right: -1px;
            height: 2em;
            display: block;
            background: @background-color-progressive;
        }
    }

    &--collapsed {
        display: none;
    }
}

.ext-navi-map-legend-row {
    padding: 3px 4px;

    &:not( :last-child ) {
        border-bottom: 1px solid @border-color-divider;
    }

    &:has( > .cdx-accordion ) {
        padding: 0;
    }

    > .cdx-accordion {
        border-bottom-width: 0;

        > summary {
            padding: 3px 4px;
            border-bottom: 1px solid @border-color-divider;

            > .cdx-accordion__header {
                font-size: inherit;
            }
        }

        > .cdx-accordion__content {
            font-size: inherit;
            padding: 3px 4px;
        }
    }

    .cdx-button-group {
        width: 100%;
        display: flex;
        justify-content: stretch;
        align-items: center;

        > .cdx-button {
            flex: 1 1;
            border-color: transparent;
        }
    }
}
</style>
