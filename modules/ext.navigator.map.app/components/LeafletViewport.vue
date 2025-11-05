<template>
    <div class="ext-navi-map-viewport" ref="root"></div>
</template>

<script>
const
    { inject } = require( 'vue' );

// @vue/component
module.exports = {
	name: 'LeafletViewport',
    mounted() {
        this.$refs.root.appendChild( inject( 'leafletHost' ) );
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

.ext-navi-map-viewport {
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}
</style>
