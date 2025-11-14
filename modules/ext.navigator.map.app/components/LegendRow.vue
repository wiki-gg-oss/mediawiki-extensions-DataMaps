<template>
    <div class="ext-navi-map-legend-row" v-if="rowType === 'rawHtml'" v-html="rowData.value">
    </div>
    <div class="ext-navi-map-legend-row" v-else>
        <cdx-accordion
            open
            v-if="rowType === 'accordion'"
        >
            <template #title>
                {{ rowData.label }}
            </template>
            <slot />
        </cdx-accordion>
        <template v-else>
            <slot />
        </template>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require('vue'),
    { CdxAccordion } = require( '@wikimedia/codex' );


// @vue/component
module.exports = {
    name: 'LegendRow',
    components: {
        CdxAccordion
    },
    props: {
        rowType: {
            type: String,
            required: true
        },
        rowData: {
            type: Object,
            required: true,
        },
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

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
