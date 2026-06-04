<script setup>
defineProps({
    padding: { type: String, default: 'md' },
    hover: { type: Boolean, default: false },
});
</script>

<template>
    <div
        class="ui-card"
        :class="[
            {
                'p-4': padding === 'sm',
                'p-6': padding === 'md',
                'p-8 md:p-10': padding === 'lg',
                '': padding === 'none',
            },
            hover ? 'ui-card-hover' : '',
        ]"
    >
        <slot />
    </div>
</template>
