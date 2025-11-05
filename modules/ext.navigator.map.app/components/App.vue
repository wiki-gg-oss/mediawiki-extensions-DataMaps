<template>
	<div class="ext-navi-map-viewport">
        [PH]Headless Leaflet viewport area
    </div>

    <controls-area
        :control-groups="controlGroups"
    >
    </controls-area>

    <div class="ext-navi-map-panels">
        <div class="ext-navi-map-panels-row">
            <cdx-toggle-button
                v-model="isLegendOpen"
                class="ext-navi-map-legend-btn"
                aria-label="[PH]Expand/collapse"
            >
                <cdx-icon :icon="uiIcons.cdxIconMenu"></cdx-icon>
            </cdx-toggle-button>

            <div class="ext-navi-map-search">
                <cdx-search-input
		            placeholder="[PH]Search..."
                >
                </cdx-search-input>
            </div>
        </div>

        <legend-area
            :is-open="isLegendOpen"
        >
        </legend-area>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton } = require( '@wikimedia/codex' ),
    useMarkerTypesStore = require( '../stores/MarkerTypesStore.js' ),
    ControlsArea = require( './ControlsArea.vue' ),
    LegendArea = require( './LegendArea.vue' ),
    uiIcons = require( '../data/icons.json' );

// @vue/component
module.exports = {
	name: 'App',
    components: {
        CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
        ControlsArea, LegendArea,
    },

    data() {
        return {
            uiIcons,
            controlGroups: [
                {
                    name: 'editing',
                    items: [
                        {
                            type: 'button',
                            name: 'edit',
                            icon: uiIcons.cdxIconEdit,
                        },
                    ],
                },
                {
                    name: 'view',
                    items: [
                        {
                            type: 'toggleButton',
                            name: 'fullscreen',
                            icon: uiIcons.cdxIconFullScreen,
                        },
                    ],
                },
                {
                    name: 'zoom',
                    spacing: false,
                    items: [
                        {
                            type: 'button',
                            name: 'zoomIn',
                            icon: uiIcons.cdxIconAdd,
                        },
                        {
                            type: 'button',
                            name: 'zoomOut',
                            icon: uiIcons.cdxIconSubtract,
                        },
                    ],
                },
            ],
        };
    },

    setup() {
        return {
            markerTypesStore: useMarkerTypesStore(),
            isLegendOpen: ref( true ),
        };
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

@navi-size-edge-offset: 16px;
@navi-size-panel-spacing: 0.5rem;

.ext-navi-map {
    &--ready {
        background-color: @background-color-base;
    }

    > .ext-navi-map-viewport {
        height: 100%;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
    }

    > .ext-navi-map-panels {
        position: absolute;
        top: @navi-size-edge-offset;
        left: @navi-size-edge-offset;
        display: flex;
        flex-direction: column;
        gap: @navi-size-panel-spacing;
        height: ~"calc( 100% - @{navi-size-edge-offset} * 2 )";
        width: 200px;

        @media screen and ( min-width: @min-width-breakpoint-tablet ) {
            width: 300px;
        }

        @media screen and ( min-width: @min-width-breakpoint-desktop ) {
            width: 350px;
        }

        > .ext-navi-map-panels-row {
            display: flex;
            flex-direction: row;
            gap: @navi-size-panel-spacing;
        }
    }
}

.ext-navi-map-search {
    flex-grow: 1;

    .cdx-text-input {
        min-width: unset;

        > input {
            width: 100%;
        }
    }
}
</style>
