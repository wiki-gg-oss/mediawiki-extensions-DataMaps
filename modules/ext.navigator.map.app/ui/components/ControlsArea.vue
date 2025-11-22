<template>
    <div class="ext-navi-map-controls">
        <div
            v-for="group in enabledControlGroups"
            class="ext-navi-map-controls-group"
        >
            <h3 v-if="isDebugMode">{{ group.name }}</h3>
            <div
                :class="group.spacing ? undefined : 'cdx-button-group'"
            >
                <control
                    :name="item.name"
                    :data="item"
                    v-for="item in group.items"
                >
                    <cdx-icon :icon="item.icon"></cdx-icon>
                </control>
            </component>
        </div>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxButton, CdxButtonGroup, CdxIcon } = require( '@wikimedia/codex' ),
    Control = require( './Control.vue' );

// @vue/component
module.exports = {
	name: 'ControlsArea',
    components: {
        CdxButton, CdxButtonGroup, CdxIcon,
        Control,
    },
	props: {
		controlGroups: {
			type: [ Array ],
			required: true
		},
	},
    setup() {
        return {
            isDebugMode: mw.config.get( 'debug' ),
        };
    },
    computed: {
        enabledControlGroups() {
            return this.controlGroups.filter( item => !item.hidden );
        },
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

@navi-size-edge-offset: 16px;
@navi-size-control-group-spacing: 0.75rem;
@navi-size-control-spacing: 0.5rem;

.ext-navi-map-controls {
    position: absolute;
    top: @navi-size-edge-offset;
    right: @navi-size-edge-offset;
    display: flex;
    flex-direction: row;
    gap: @navi-size-control-group-spacing;

    > .ext-navi-map-controls-group {
        position: relative;
        display: flex;
        flex-direction: row;
        gap: @navi-size-control-spacing;

        &:not( :last-child )::after {
            content: '';
            display: block;
            margin-left: calc( @navi-size-control-group-spacing - @navi-size-control-spacing );
            width: 0;
            height: 100%;
            box-shadow: 0 0 0 1px @border-color-divider;
        }
        
        > h3 {
            position: absolute;
            font-size: 0.7em;
            top: -2em;
        }
    }

	@media screen and ( max-width: @max-width-breakpoint-tablet ) {
        flex-direction: column;

        > .ext-navi-map-controls-group {
            flex-direction: column;

            &:not( :last-child )::after {
                width: 100%;
                height: 0;
                margin-left: 0;
                margin-top: calc( @navi-size-control-group-spacing - @navi-size-control-spacing );
            }

            > .cdx-button-group {
                display: flex;
                flex-direction: column;
            }
        }
    }
}
</style>
