<template>
    <div
        v-if="state.isVisible"
        ref="attachmentTarget"
        class="ext-navi-popover-attachment-point"
        :style="attachmentPointStyle"
    >
    	<cdx-popover
            v-if="state.isVisible"
            class="ext-navi-popover"
    		:open="state.isVisible"
    		:anchor="attachmentTarget"
    		placement="bottom"
    		:use-close-button="true"
            :render-in-place="true"
            @update:open="value => ( !value && this.state.deactivate() )"
    		:title="stTitle"
    	>
            <div
                v-if="!!stImageUrl"
                class="ext-navi-popover-image-holder-outer"
                :style="imageHolderOuterOuter"
            >
                <div class="ext-navi-popover-image-holder">
                    <img :src="stImageUrl" width="300" />
                </div>
            </div>
    		<div
                class="ext-navi-popover-desc-holder"
                v-html="stDescHtml">
            </div>
    	</cdx-popover>
    </div>
</template>

<script>
const
    { ref, toRaw } = require( 'vue' ),
    { CdxPopover } = require( '@wikimedia/codex' ),
    usePopoverState = require( '../stores/PopoverState.js' );


const
    MAX_IMAGE_WIDTH = 300,
    MAX_IMAGE_HEIGHT = 250;


// @vue/component
module.exports = {
	name: 'PopoverLayer',
    components: {
        CdxPopover,
    },
    setup() {
        const attachmentTarget = ref();
        return {
            attachmentTarget,
            state: usePopoverState(),
        };
    },
    computed: {
        attachmentPointStyle() {
            const [ x, y ] = this.state.projectedAttachmentPosition;
            return {
                top: `${y}px`,
                left: `${x}px`,
            };
        },
        imageHolderOuterOuter() {
            let
                imgWidth = toRaw( this.state.dataObject ).getImageWidth(),
                imgHeight = toRaw( this.state.dataObject ).getImageHeight(),
                imgAspectWh = imgWidth / imgHeight;
            // TODO: do this calc on the server too to deliver a 'just right' thumbnail
            if ( imgWidth > MAX_IMAGE_WIDTH ) {
                imgWidth = MAX_IMAGE_WIDTH;
                imgHeight = 1 / imgAspectWh * MAX_IMAGE_WIDTH;
            }
            if ( imgHeight > MAX_IMAGE_HEIGHT ) {
                imgHeight = MAX_IMAGE_HEIGHT;
                imgWidth = imgAspectWh * MAX_IMAGE_HEIGHT;
            }
            return {
                '--w': `${imgWidth}px`,
                '--h': `${imgHeight}px`,
            };
        },
        stTitle() {
            // TODO: HACK - Force an attachment update by faking a reference
            this.state.projectedAttachmentPosition;

            return toRaw( this.state.dataObject ).getTitle();
        },
        stDescHtml() {
            return toRaw( this.state.dataObject ).getDescriptionHtml();
        },
        stImageUrl() {
            return toRaw( this.state.dataObject ).getImageUrl();
        },
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

.ext-navi-popover-attachment-point {
    position: absolute;
}

.ext-navi-popover {
    &:has( .ext-navi-popover-image-holder ) {
        padding-top: calc( 16px + 168px );
    }

    .ext-navi-popover-image-holder {
        position: absolute;
        left: 0;
        top: 0;
        right: 0;

        &-outer {
            width: calc( var( --w ) - 16px * 2 );
            height: 0;
        }

        > img {
            width: var( --w );
            height: var( --h );
        }
    }

    .ext-navi-popover-desc-holder {
        > p:first-child {
            margin-top: 0;
            padding-top: 0;
        }

        > p:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }
    }
}
</style>
