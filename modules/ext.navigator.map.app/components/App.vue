<template>
    <div class="ext-navi-map-app" :class="runtimeContainerClasses">
        <leaflet-viewport></leaflet-viewport>

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

                <marker-search></marker-search>
            </div>

            <legend-area
                :is-open="isLegendOpen"
                :section-data="legendSections"
            >
            </legend-area>
        </div>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton } = require( '@wikimedia/codex' ),
    { useViewportInteraction, useMarkerSearchEngine } = require( '../InjectedSymbol.js' ),
    useMarkerTypesStore = require( '../stores/MarkerTypesStore.js' ),
    useViewportState = require( '../stores/ViewportState.js' ),
    ControlsArea = require( './ControlsArea.vue' ),
    MarkerSearch = require( './MarkerSearch.vue' ),
    LegendArea = require( './LegendArea.vue' ),
    LeafletViewport = require( './LeafletViewport.vue' ),
    MarkerVisibilityQuickToggleRow = require( './MarkerVisibilityQuickToggleRow.vue' ),
    MarkerTypesRow = require( './MarkerTypesRow.vue' ),
    uiIcons = require( '../data/icons.json' );


// @vue/component
module.exports = {
	name: 'App',
    components: {
        CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
        ControlsArea, MarkerSearch, LegendArea, LeafletViewport,
    },

    data() {
        const isFullscreen = ref( false );
        return {
            uiIcons,
            isFullscreen,
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
                            modelValue: isFullscreen,
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
                            disabled: computed( () => !this.viewportState.canZoomIn ),
                            click: () => this.viewportBridge.zoomIn(),
                        },
                        {
                            type: 'button',
                            name: 'zoomOut',
                            icon: uiIcons.cdxIconSubtract,
                            disabled: computed( () => !this.viewportState.canZoomOut ),
                            click: () => this.viewportBridge.zoomOut(),
                        },
                    ],
                },
            ],
            legendSections: [
                {
                    type: 'direct',
                    component: MarkerVisibilityQuickToggleRow,
                },
                {
                    type: 'accordion',
                    label: '[PH]Locations (markers)',
                    component: MarkerTypesRow,
                },
            ]
        };
    },

    computed: {
        runtimeContainerClasses() {
            const classes = [];

            if ( this.isFullscreen ) {
                classes.push( 'ext-navi-map-app--fullscreen' );
            }

            return classes.join( ' ' );
        },
    },

    setup() {
        return {
            viewportBridge: useViewportInteraction(),
            markerTypesStore: useMarkerTypesStore(),
            viewportState: useViewportState(),
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

    &-app {
        height: 100%;
        width: 100%;
    }

    &:has( &-app--fullscreen ) {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        bottom: 0;
        height: auto;
        margin: 0;
        border-radius: 0;
        z-index: @z-index-overlay;
    }
}

.ext-navi-map {
    &-viewport {
        position: relative;
        z-index: @z-index-stacking-0;
    }

    &-controls {
        z-index: @z-index-stacking-1;
    }

    &-panels,
    &-search {
        z-index: @z-index-stacking-2;
    }
}

.ext-navi-map-panels {
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
