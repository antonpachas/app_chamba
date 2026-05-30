<script setup>
defineProps({
    isOpen: { type: [Boolean, null], default: null },
    summary: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    interactive: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);
</script>

<template>
    <component
        :is="interactive ? 'button' : 'div'"
        v-if="isOpen !== null || summary"
        :type="interactive ? 'button' : undefined"
        class="inline-flex flex-wrap items-center gap-1.5 border-0 p-0"
        :class="[
            compact ? 'text-xs' : 'text-sm',
            interactive
                ? 'listing-detail__meta-chip listing-detail__meta-chip--link cursor-pointer group/hours font-medium shadow-sm hover:shadow'
                : 'bg-transparent',
        ]"
        :aria-label="interactive ? 'Ver horario de atención' : undefined"
        @click="interactive ? emit('click') : undefined"
    >
        <span
            v-if="isOpen === true"
            class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 shrink-0"
            :class="compact ? 'text-[10px]' : 'text-xs'"
        >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Abierto ahora
        </span>
        <span
            v-else-if="isOpen === false"
            class="inline-flex items-center gap-1 rounded-full bg-white/80 text-slate-700 font-semibold px-2 py-0.5 shrink-0 border border-slate-200/80"
            :class="compact ? 'text-[10px]' : 'text-xs'"
        >
            Cerrado ahora
        </span>
        <template v-if="interactive">
            <span class="text-xs font-bold text-[#003874] whitespace-nowrap">Ver horario</span>
            <span class="material-symbols-outlined text-[18px] text-[#003874] shrink-0">schedule</span>
            <span
                class="material-symbols-outlined text-[18px] text-[#003874]/70 shrink-0 transition-transform group-hover/hours:translate-x-0.5"
            >chevron_right</span>
        </template>
        <span v-if="summary && !interactive" class="text-slate-500 leading-snug">{{ summary }}</span>
    </component>
</template>
