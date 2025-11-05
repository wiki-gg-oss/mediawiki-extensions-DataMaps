<template>
    <cdx-toggle-button
        :aria-label="name"
        :disabled="data.disabled"
        v-model="data.modelValue"
        v-if="data.type === 'toggleButton'"
    >
        <slot />
    </cdx-toggle-button>
    <cdx-button
        :aria-label="name"
        :disabled="data.disabled"
        v-else
    >
        <slot />
    </cdx-button>
</template>

<script>
const
    { ref, computed, watch } = require( 'vue' ),
    { CdxButton, CdxToggleButton, CdxIcon } = require( '@wikimedia/codex' );

// @vue/component
module.exports = {
	name: 'Control',
    components: {
        CdxButton, CdxToggleButton, CdxIcon,
    },
	props: {
		name: {
            type: String,
            required: true,
        },
        data: {
            type: Object,
            required: true,
        },
	},
};
</script>
