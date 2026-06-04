<script setup>
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    tight: { type: Boolean, default: false },
});
</script>

<template>
    <header class="mb-8" :class="tight ? 'mb-6' : ''">
        <p v-if="eyebrow" class="page-eyebrow">{{ eyebrow }}</p>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="page-title">{{ title }}</h1>
                <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </header>
</template>
