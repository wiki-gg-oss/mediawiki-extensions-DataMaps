<template>
    <div class="ext-navi-map-viewport" ref="root"></div>
</template>

<script>
const
    { inject } = require( 'vue' ),
    InjectedSymbol = require( '../InjectedSymbol.js' );

// @vue/component
module.exports = {
	name: 'LeafletViewport',
    mounted() {
        this.$refs.root.appendChild( inject( InjectedSymbol.LEAFLET_HOST ) );
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
@import 'mediawiki.mixins.less';

.ext-navi-map-viewport {
    height: 100%;
    width: 100%;
    display: grid;
    grid-template: ~"1fr / 1fr";
    color: #666;
}
</style>
