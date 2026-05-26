<script setup>
defineProps({
    type: { type: String, default: 'error' },
});
</script>

<template>
    <div
        class="rounded-lg px-4 py-3 text-sm font-medium border"
        :class="{
            'bg-red-50 text-red-800 border-red-100': type === 'error',
            'bg-emerald-50 text-emerald-900 border-emerald-100': type === 'success',
            'bg-amber-50 text-amber-900 border-amber-100': type === 'warning',
            'bg-blue-50 text-blue-900 border-blue-100': type === 'info',
        }"
    >
        <slot />
    </div>
</template>
