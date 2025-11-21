<template>
    <div
        v-if="state.isVisible"
        ref="attachmentTarget"
        class="ext-navi-popover-attachment-point"
        :style="attachmentPointStyle"
    ></div>
	<cdx-popover
		:open="state.isVisible"
		:anchor="attachmentTarget"
		placement="bottom-start"
		title="Test"
		:use-close-button="true"
	>
		Test popover
	</cdx-popover>
</template>

<script>
const
    { ref } = require( 'vue' ),
    { CdxPopover } = require( '@wikimedia/codex' ),
    usePopoverState = require( '../stores/PopoverState.js' );


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
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';
</style>
