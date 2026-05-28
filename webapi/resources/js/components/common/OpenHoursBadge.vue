<script setup>
const props = defineProps({
    isOpen: { type: [Boolean, null], default: null },
    summary: { type: String, default: '' },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div v-if="isOpen !== null || summary" class="flex flex-wrap items-center gap-2" :class="compact ? 'text-xs' : 'text-sm'">
        <span
            v-if="isOpen === true"
            class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5"
            :class="compact ? 'text-[10px]' : 'text-xs'"
        >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Abierto ahora
        </span>
        <span
            v-else-if="isOpen === false"
            class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-600 font-semibold px-2 py-0.5"
            :class="compact ? 'text-[10px]' : 'text-xs'"
        >
            Cerrado ahora
        </span>
        <span v-if="summary" class="text-slate-500 leading-snug">{{ summary }}</span>
    </div>
</template>
