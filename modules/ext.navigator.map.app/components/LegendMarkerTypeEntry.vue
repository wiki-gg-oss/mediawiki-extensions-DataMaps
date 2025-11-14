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
            v-if="descriptionHtml"
            v-html="descriptionHtml"
        ></div>

        <div
            class="ext-navi-map-legend-group-extras"
            v-if="subtypes && subtypes.length"
        >
            <ul class="ext-navi-map-legend-groups">
                <legend-marker-type-entry
                    v-for="item in subtypes"
                    :key="item.id"
                    :id="item.id"
                    :is-expanded="false"
                    :name="item.name"
                    :description-html="item.descriptionHtml"
                    :has-progress-tracking="item.progressTracking"
                    :subtypes="item.subtypes"
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
    useMarkerTypesStore = require( '../stores/MarkerTypesStore.js' ),
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
        id: {
            type: String,
            required: true,
        },
        name: {
            type: String,
            required: true,
        },
        descriptionHtml: {
            type: String,
            required: false,
        },
        hasProgressTracking: {
            type: Boolean,
            required: false,
            default: false,
        },
        subtypes: {
            type: Array,
            required: false,
        }
    },
    computed: {
        markerCount() {
            return this.dataStore.markerCountByType[ this.id ];
        },

        markerCountTickedOff() {
            return this.dataStore.markerCountByTypeTickedOff[ this.id ] || 0;
        },

        markerCountText() {
            if ( this.hasProgressTracking ) {
                return `${this.markerCountTickedOff}/${this.markerCount}`;
            }

            return `${this.markerCount}`;
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
    line-height: 1.3;
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
