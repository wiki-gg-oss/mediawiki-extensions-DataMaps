<template>
    <div
        class="ext-navi-map-legend"
        :class="isOpen ? undefined : 'ext-navi-map-legend--collapsed'"
    >
        <div class="ext-navi-map-legend-row">
            <div class="cdx-button-group">
                <cdx-button>[PH]Show all</cdx-button>
                <cdx-button>[PH]Show none</cdx-button>
            </div>
        </div>

        <div class="ext-navi-map-legend-row">
            <cdx-accordion open>
                <template #title>
                    [PH]Locations (markers)
                </template>
                <ul class="ext-navi-map-legend-groups">
                    <legend-marker-type-entry
                        v-for="item in dataStore.markerTypes"
                        :key="item.id"
                        :id="item.id"
                        :is-expanded="false"
                        :name="item.name"
                        :description="item.description"
                        :has-progress-tracking="item.progressTracking"
                        :subtypes="item.include"
                    >
                    </legend-marker-type-entry>
                </ul>
            </cdx-accordion>
        </div>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require('vue'),
    { CdxAccordion, CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton } = require( '@wikimedia/codex' ),
    LegendMarkerTypeEntry = require( './LegendMarkerTypeEntry.vue' ),
    useMarkerTypesStore = require( '../stores/MarkerTypesStore.js' ),
    uiIcons = require( '../data/icons.json' );


// @vue/component
module.exports = {
    name: 'LegendArea',
    components: {
        CdxAccordion, CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
        LegendMarkerTypeEntry,
    },
    props: {
        isOpen: {
            type: [ Boolean ],
            required: true
        },
    },
    setup() {
        return {
            dataStore: useMarkerTypesStore(),
            uiIcons,
        };
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
