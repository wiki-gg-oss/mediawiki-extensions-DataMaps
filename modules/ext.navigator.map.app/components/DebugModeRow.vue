<template>
    <ul class="ext-navi-map-debuginfo">
        <li
            v-for="(left, right) in lines"
        >
            <b>{{ right }}:</b> {{ left }}
        </li>
    </ul>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    useViewportState = require( '../stores/ViewportState.js' ),
    usePopoverState = require( '../stores/PopoverState.js' );


// @vue/component
module.exports = {
	name: 'DebugModeRow',
    setup() {
        return {
            vs: useViewportState(),
            ps: usePopoverState(),
        };
    },
    computed: {
        lines() {
            const
                vs = this.vs,
                ps = this.ps;
            return {
                'View box': `(${vs.viewBoxNe}) (${vs.viewBoxSw})`,
                'Zoom': `${vs.zoomCurrent} in ${vs.zoomMin}..${vs.zoomMax} (out: ${vs.canZoomOut}, in: ${vs.canZoomIn})`,
                'Popover': `${ps.isVisible} @ ${ps.attachmentLocationX},${ps.attachmentLocationY} (real: ${ps.projectedAttachmentPosition})`,
            };
        },
    },
};
</script>

<style lang="less">
.ext-navi-map-debuginfo {
    font-size: 11px;
    list-style: none;
    margin: 0;
}
</style>
