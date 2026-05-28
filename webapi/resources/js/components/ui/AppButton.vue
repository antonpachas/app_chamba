<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const variants = {
    primary: 'btn-grad-primary disabled:opacity-55 disabled:transform-none disabled:shadow-none',
    secondary: 'btn-grad-warm disabled:opacity-55 disabled:transform-none',
    outline:
        'bg-white text-chamba-700 border-2 border-chamba-700/25 hover:border-chamba-700/50 hover:bg-chamba-50 disabled:opacity-55',
    ghost: 'bg-transparent text-chamba-700 hover:bg-chamba-700/8 disabled:opacity-55',
};

const sizes = {
    sm: 'h-9 px-4 text-sm rounded-xl',
    md: 'h-11 px-6 text-sm rounded-xl',
    lg: 'h-14 px-8 text-base rounded-full',
};

const cls = computed(() => [
    'inline-flex items-center justify-center font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-chamba-700/35 focus-visible:ring-offset-2 disabled:cursor-not-allowed',
    variants[props.variant] || variants.primary,
    sizes[props.size] || sizes.md,
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <button :type="type" :disabled="disabled || loading" :class="cls">
        <span
            v-if="loading"
            class="mr-2 inline-block h-4 w-4 rounded-full border-2 border-white/40 border-t-white animate-spin"
            aria-hidden="true"
        ></span>
        <slot />
    </button>
</template>
