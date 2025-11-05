<template>
    <li>
        <div class="ext-navi-map-legend-group-header">
            {{ name }}
            <span
                class="ext-navi-map-legend-group-count"
                v-if="markerCount > 0"
            >
                ({{ markerCountText }})
            </span>

            <cdx-toggle-button
                aria-label="[PH]Expand/collapse"
                weight="quiet"
                v-if="hasProgressTracking"
            >
                <cdx-icon :icon="uiIcons.cdxIconNext.ltr"></cdx-icon>
            </cdx-toggle-button>
        </div>

        <div
            class="ext-navi-map-legend-group-desc"
            v-if="description"
        >
            {{ description }}
        </div>

        <div
            class="ext-navi-map-legend-group-extras"
            v-if="subtypes && subtypes.length"
        >
            <ul class="ext-navi-map-legend-groups">
                <legend-marker-type-entry
                    v-for="item in subtypes"
                    :key="item.id"
                    :is-expanded="false"
                    :name="item.name"
                    :description="item.description"
                    :has-progress-tracking="false"
                    :marker-count="0"
                    :subtypes="item.include"
                >
                </legend-marker-type-entry>
            </ul>
        </div>
    </li>
</template>

<script>
const
    { ref, computed, watch } = require('vue'),
    { CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton } = require( '@wikimedia/codex' ),
    uiIcons = require( '../data/icons.json' );

// @vue/component
module.exports = {
    name: 'LegendMarkerTypeEntry',
    components: {
        CdxButton, CdxButtonGroup, CdxIcon, CdxSearchInput, CdxToggleButton,
    },
    props: {
        isExpanded: {
            type: [ Boolean ],
            required: true,
        },
        name: {
            type: String,
            required: true,
        },
        description: {
            type: String,
            required: false,
        },
        hasProgressTracking: {
            type: Boolean,
            required: false,
            default: false,
        },
        markerCount: {
            type: Number,
            required: false,
            default: 0,
        },
        subtypes: {
            type: Array,
            required: false,
        }
    },
    computed: {
        markerCountText() {
            if ( this.hasProgressTracking ) {
                return `0/${this.markerCount}`;
            }

            return `${this.markerCount}`;
        },
    },
    setup() {
        return {
            uiIcons,
        };
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

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
</style>
