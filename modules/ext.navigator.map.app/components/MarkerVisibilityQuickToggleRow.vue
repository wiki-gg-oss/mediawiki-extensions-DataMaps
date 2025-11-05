<template>
    <div class="cdx-button-group">
        <cdx-button>[PH]Show all</cdx-button>
        <cdx-button>[PH]Show none</cdx-button>
    </div>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxButton } = require( '@wikimedia/codex' );


// @vue/component
module.exports = {
	name: 'MarkerVisibilityQuickToggleRow',
    components: {
        CdxButton,
    },
};
</script>
