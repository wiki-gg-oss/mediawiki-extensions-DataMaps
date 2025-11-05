<template>
    <div class="ext-navi-map-debuginfo">
        <b>View box:</b> ({{ vs.viewBoxNe[ 0 ] }} {{ vs.viewBoxNe[ 1 ] }}) ({{ vs.viewBoxSw[ 0 ] }} {{ vs.viewBoxSw[ 1 ] }})<br />
        <b>Zoom:</b> {{ vs.zoomCurrent }} in {{ vs.zoomMin }}..{{ vs.zoomMax }} (out: {{ vs.canZoomOut }}, in: {{ vs.canZoomIn }})
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    useViewportState = require( '../stores/ViewportState.js' );


// @vue/component
module.exports = {
	name: 'DebugModeRow',
    setup() {
        return {
            vs: useViewportState(),
        };
    },
};
</script>

<style lang="less">
.ext-navi-map-debuginfo {
    font-size: 11px;
}
</style>
