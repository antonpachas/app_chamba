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
    primary:
        'bg-[#003874] text-white hover:bg-[#08458b] shadow-md shadow-[#003874]/15 disabled:opacity-55',
    secondary:
        'bg-[#ff7a2b] text-[#602500] hover:brightness-105 disabled:opacity-55',
    outline:
        'bg-white text-[#003874] border-2 border-[#003874]/30 hover:border-[#003874]/60 disabled:opacity-55',
    ghost: 'bg-transparent text-[#003874] hover:bg-[#003874]/5 disabled:opacity-55',
};

const sizes = {
    sm: 'h-9 px-4 text-sm rounded-lg',
    md: 'h-11 px-6 text-sm rounded-lg',
    lg: 'h-14 px-8 text-base rounded-full',
};

const cls = computed(() => [
    'inline-flex items-center justify-center font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#003874]/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed',
    variants[props.variant] || variants.primary,
    sizes[props.size] || sizes.md,
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <button :type="type" :disabled="disabled || loading" :class="cls">
        <span v-if="loading" class="mr-2 inline-block h-4 w-4 rounded-full border-2 border-white/40 border-t-white animate-spin" aria-hidden="true"></span>
        <slot />
    </button>
</template>
