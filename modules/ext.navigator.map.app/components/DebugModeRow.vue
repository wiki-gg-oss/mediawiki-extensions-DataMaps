<template>
    <div class="ext-navi-map-debuginfo">
        <b>View box:</b> (? ?) (? ?)<br />
        <b>Zoom:</b> ? in ?..?
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' );


// @vue/component
module.exports = {
	name: 'DebugModeRow',
};
</script>

<style lang="less">
.ext-navi-map-debuginfo {
    font-size: 11px;
}
</style>
