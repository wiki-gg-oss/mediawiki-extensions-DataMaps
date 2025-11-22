<template>
    <li>
        <div class="ext-navi-map-legend-group-header">
            <div
                v-if="transientData.placeholderStyle !== false"
                class="ext-navi-markertype-visualiser"
            >
                <svg viewBox="0 0 20 20">
                    <path
                        d="M 10,0 C 5.4971441,-0.21118927 1.7888107,3.4971441 2,8 c 0,2.52 2,5 3,6 1,1 5,6 5,6 0,0 4,-5 5,-6 1,-1 3,-3.48 3,-6 0.211189,-4.5028559 -3.497144,-8.21118927 -8,-8 z"
                        :fill="visualiserFillColour"
                        :stroke="visualiserOutlineColour"
                        :stroke-width="visualiserOutlineWidth" />
                    <circle cx="10" cy="8" r="3.3" fill="#0009" />
                </svg>
            </div>

            <span class="ext-navi-markertype-name-holder">
                <span class="ext-navi-markertype-name">
                    {{ transientData.name }}
                </span>
                <span
                    class="ext-navi-map-legend-group-count"
                    v-if="markerCount > 0"
                >
                    ({{ markerCountText }})
                </span>
            </span>

            <cdx-toggle-button
                aria-label="[PH]Expand/collapse"
                weight="quiet"
                v-if="transientData.hasProgressTracking"
            >
                <cdx-icon :icon="uiIcons.cdxIconNext.ltr"></cdx-icon>
            </cdx-toggle-button>
        </div>

        <div
            class="ext-navi-map-legend-group-desc"
            v-if="transientData.descriptionHtml"
            v-html="transientData.descriptionHtml"
        ></div>

        <div
            class="ext-navi-map-legend-group-extras"
            v-if="transientData.subtypes && transientData.subtypes.length"
        >
            <ul class="ext-navi-map-legend-groups">
                <legend-marker-type-entry
                    v-for="item in transientData.subtypes"
                    :key="item.id"
                    :id="item.id"
                    :is-expanded="false"
                    :transient-data="item"
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
        transientData: {
            type: Object,
            required: true,
        },
    },
    computed: {
        markerCount() {
            return this.dataStore.markerCountByType[ this.id ];
        },

        markerCountTickedOff() {
            return this.dataStore.markerCountByTypeTickedOff[ this.id ] || 0;
        },

        markerCountText() {
            if ( this.transientData.hasProgressTracking ) {
                return `${this.markerCountTickedOff}/${this.markerCount}`;
            }

            return `${this.markerCount}`;
        },

        visualiserFillColour() {
            return this.transientData.placeholderStyle.fillColour;
        },

        visualiserOutlineColour() {
            return this.transientData.placeholderStyle.outlineColour;
        },

        visualiserOutlineWidth() {
            return this.transientData.placeholderStyle.outlineWidth;
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
    display: flex;
    gap: 4px;
    padding: 0.25em 0.4em;
    font-weight: 700;

    > .ext-navi-markertype-visualiser {
        > svg,
        > img {
            display: block;
            width: 0.9lh;
            height: 0.9lh;
        }
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

.ext-navi-markertype-name-holder {
    > .ext-navi-markertype-name {
        vertical-align: middle;
    }

    > .ext-navi-map-legend-group-count {
        color: @color-subtle;
        font-size: 0.85em;
        font-weight: 400;
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
