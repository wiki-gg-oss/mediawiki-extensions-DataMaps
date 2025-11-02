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

        <div
            class="ext-navi-map-legend"
            :class="isLegendOpen ? undefined : 'ext-navi-map-legend--collapsed'"
        >
            <div class="ext-navi-map-legend-row">
                <div class="cdx-button-group">
                    <cdx-button>[PH]Show all</cdx-button>
                    <cdx-button>[PH]Show none</cdx-button>
                </div>
            </div>

            <div class="ext-navi-map-legend-row">
                <ul class="ext-navi-map-legend-groups">
                    <li>
                        <div class="ext-navi-map-legend-group-header">
                            Visions of the Traveler
                            <span class="ext-navi-map-legend-group-count">(2/8)</span>

                            <cdx-toggle-button
                                aria-label="[PH]Expand/collapse"
                                weight="quiet"
                            >
                                <cdx-icon :icon="uiIcons.cdxIconNext.ltr"></cdx-icon>
                            </cdx-toggle-button>
                        </div>
                        <div class="ext-navi-map-legend-group-desc">
                            Collect all to unlock the Khvostov-1 exotic auto rifle.
                        </div>
                        <div class="ext-navi-map-legend-group-extras">

                        </div>
                    </li>
                    <li>
                        <div class="ext-navi-map-legend-group-header">
                            Lost Encryption Bits
                            <span class="ext-navi-map-legend-group-count">(4/15)</span>

                            <cdx-toggle-button
                                aria-label="[PH]Expand/collapse"
                                weight="quiet"
                            >
                                <cdx-icon :icon="uiIcons.cdxIconExpand"></cdx-icon>
                            </cdx-toggle-button>
                        </div>
                        <div class="ext-navi-map-legend-group-desc">
                            Gather by opening Region Chests and searching through Rubble Piles in the Cysts to unlock the Khvostov legendary auto rifle.
                        </div>
                        <div class="ext-navi-map-legend-group-extras">
                            <ul class="ext-navi-map-legend-groups">
                                <li>
                                    <div class="ext-navi-map-legend-group-header">
                                        Region Chests
                                        <span class="ext-navi-map-legend-group-count">(2/9)</span>

                                        <cdx-toggle-button
                                            aria-label="[PH]Expand/collapse"
                                            weight="quiet"
                                        >
                                            <cdx-icon :icon="uiIcons.cdxIconNext.ltr"></cdx-icon>
                                        </cdx-toggle-button>
                                    </div>
                                </li>
                                <li>
                                    <div class="ext-navi-map-legend-group-header">
                                        Rubble Piles
                                        <span class="ext-navi-map-legend-group-count">(2/6)</span>

                                        <cdx-toggle-button
                                            aria-label="[PH]Expand/collapse"
                                            weight="quiet"
                                        >
                                            <cdx-icon :icon="uiIcons.cdxIconNext.ltr"></cdx-icon>
                                        </cdx-toggle-button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton } = require( '@wikimedia/codex' ),
    ControlsArea = require( './ControlsArea.vue' ),
    uiIcons = require( '../data/icons.json' );

// @vue/component
module.exports = {
	name: 'App',
    components: {
        CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
        ControlsArea,
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

    > .cdx-button-group {
        width: 100%;
        display: flex;
        justify-content: stretch;
        align-items: center;

        > .cdx-button {
            flex: 1 1;
            border-color: transparent;
        }
    }

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

        .ext-navi-map-legend-group-header {
            padding: 0.25em 0.4em;
            font-weight: 700;

            > .ext-navi-map-legend-group-count {
                color: @color-subtle;
                font-size: 0.85em;
                font-weight: 400;
            }

            > .cdx-toggle-button {
                font-size: 0.5em;
                float: right;
                min-width: 2em;
                width: 2em;
                height: 2em;
                min-height: 2em;
            }
        }

        .ext-navi-map-legend-group-desc {
            margin-top: calc( -0.15em / 0.9 );
            padding: 0 calc( 0.4em / 0.9 );
            font-size: 0.9em;
            color: @color-subtle;
        }

        .ext-navi-map-legend-group-extras {
            > .ext-navi-map-legend-groups {
                list-style: none;
                display: flex;
                flex-direction: column;
                gap: 0.2rem;
                margin-left: 1rem;
            }
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
