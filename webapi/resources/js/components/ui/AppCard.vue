<script setup>
defineProps({
    padding: { type: String, default: 'md' },
});
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-slate-200 shadow-sm shadow-slate-900/5"
        :class="{
            'p-4': padding === 'sm',
            'p-6': padding === 'md',
            'p-8 md:p-10': padding === 'lg',
            '': padding === 'none',
        }"
    >
        <slot />
    </div>
</template>
