<template>
    <div class="ext-navi-map-app ext-navi-map-stack" :class="runtimeContainerClasses">
        <leaflet-viewport>
            <popover-layer></popover-layer>
        </leaflet-viewport>

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
    useAppSettings = require( '../stores/AppSettingsStore.js' ),
    useMarkerTypesStore = require( '../stores/MarkerTypesStore.js' ),
    useViewportState = require( '../stores/ViewportState.js' ),
    ControlsArea = require( './ControlsArea.vue' ),
    MarkerSearch = require( './MarkerSearch.vue' ),
    LegendArea = require( './LegendArea.vue' ),
    LeafletViewport = require( './LeafletViewport.vue' ),
    PopoverLayer = require( './PopoverLayer.vue' ),
    MarkerVisibilityQuickToggleRow = require( './MarkerVisibilityQuickToggleRow.vue' ),
    MarkerTypesRow = require( './MarkerTypesRow.vue' ),
    DebugModeRow = require( './DebugModeRow.vue' ),
    uiIcons = require( '../data/icons.json' );


// @vue/component
module.exports = {
	name: 'App',
    components: {
        CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
        ControlsArea, MarkerSearch, LegendArea, LeafletViewport, PopoverLayer,
    },

    data() {
        const isFullscreen = ref( false );

        return {
            uiIcons,
            isFullscreen,
            controlGroups: [
                {
                    name: 'editing',
                    hidden: !mw.user.isNamed(),
                    items: [
                        {
                            type: 'button',
                            name: mw.msg( 'navigator-app-control-edit' ),
                            icon: uiIcons.cdxIconEdit,
                            disabled: computed( () => !this.appSettings.isSourceCodeTitleAvailable ),
                            click: () => this.viewportBridge.navigateToEditPage(),
                        },
                    ],
                },
                {
                    name: 'view',
                    hidden: true,
                    items: [
                        {
                            type: 'toggleButton',
                            name: mw.msg( 'navigator-app-control-fullscreen' ),
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
                            name: mw.msg( 'navigator-app-control-zoom-in' ),
                            icon: uiIcons.cdxIconAdd,
                            disabled: computed( () => !this.viewportState.canZoomIn ),
                            click: () => this.viewportBridge.zoomIn(),
                        },
                        {
                            type: 'button',
                            name: mw.msg( 'navigator-app-control-zoom-out' ),
                            icon: uiIcons.cdxIconSubtract,
                            disabled: computed( () => !this.viewportState.canZoomOut ),
                            click: () => this.viewportBridge.zoomOut(),
                        },
                    ],
                },
            ],
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


        legendSections() {
            const retval = [];

            if ( this.appSettings.subtitleHtml ) {
                retval.push( {
                    type: 'rawHtml',
                    value: this.appSettings.subtitleHtml,
                } );
            }
            
            retval.push(
                {
                    type: 'direct',
                    hidden: true,
                    component: MarkerVisibilityQuickToggleRow,
                },
                {
                    type: 'accordion',
                    label: mw.msg( 'navigator-app-section-markertypes' ),
                    component: MarkerTypesRow,
                },
            );

            if ( mw.config.get( 'debug' ) ) {
                retval.push( {
                    type: 'accordion',
                    label: mw.msg( 'navigator-app-section-debug' ),
                    component: DebugModeRow,
                } );
            }

            return retval;
        },
    },

    setup() {
        return {
            viewportBridge: useViewportInteraction(),
            appSettings: useAppSettings(),
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

    &-panels {
        z-index: @z-index-stacking-2;
    }

    &-search {
        z-index: @z-index-stacking-3;
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

.ext-navi-map-panels {
    pointer-events: none;

    > div {
        pointer-events: auto;
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
