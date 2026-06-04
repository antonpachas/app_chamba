<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    schedule: { type: Object, default: null },
    compact: { type: Boolean, default: false },
    /** Muestra todos los días (p. ej. en modal). */
    fullWeek: { type: Boolean, default: false },
    /** Sin cabecera ni borde (dentro de modal). */
    embedded: { type: Boolean, default: false },
});

const expanded = ref(false);

const days = [
    { key: 'mon', label: 'Lun', full: 'Lunes' },
    { key: 'tue', label: 'Mar', full: 'Martes' },
    { key: 'wed', label: 'Mié', full: 'Miércoles' },
    { key: 'thu', label: 'Jue', full: 'Jueves' },
    { key: 'fri', label: 'Vie', full: 'Viernes' },
    { key: 'sat', label: 'Sáb', full: 'Sábado' },
    { key: 'sun', label: 'Dom', full: 'Domingo' },
];

const jsDayToKey = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
const todayKey = jsDayToKey[new Date().getDay()];

const rows = computed(() => {
    const s = props.schedule;
    if (!s || typeof s !== 'object') return [];
    return days.map(({ key, label, full }) => {
        const block = s[key] || s[key.toUpperCase()];
        if (!block || typeof block !== 'object') {
            return { key, label, full, text: '—' };
        }
        if (block.closed) {
            return { key, label, full, text: 'Cerrado', closed: true };
        }
        const open = String(block.open || '').trim();
        const close = String(block.close || '').trim();
        if (open && close) {
            return { key, label, full, text: `${open} – ${close}` };
        }
        return { key, label, full, text: '—' };
    });
});

const hasAny = computed(() => rows.value.some((r) => r.text !== '—'));

const todayRow = computed(() => rows.value.find((r) => r.key === todayKey) || null);

const visibleRows = computed(() => {
    if (props.fullWeek) return rows.value;
    return expanded.value ? rows.value : todayRow.value ? [todayRow.value] : [];
});

const canExpand = computed(() => !props.fullWeek && rows.value.filter((r) => r.text !== '—').length > 1);
</script>

<template>
    <div
        v-if="hasAny"
        class="overflow-hidden"
        :class="embedded ? '' : 'rounded-xl border border-slate-100 bg-slate-50/80'"
    >
        <div v-if="!embedded" class="flex items-center justify-between gap-2 px-4 pt-3 pb-2">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Horario de atención</p>
            <button
                v-if="canExpand"
                type="button"
                class="inline-flex items-center gap-1 text-xs font-bold text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer shrink-0"
                :aria-expanded="expanded"
                @click="expanded = !expanded"
            >
                <span class="material-symbols-outlined text-[16px]">
                    {{ expanded ? 'expand_less' : 'expand_more' }}
                </span>
                {{ expanded ? 'Ver solo hoy' : 'Ver toda la semana' }}
            </button>
        </div>
        <ul class="divide-y divide-slate-100" :class="compact ? 'text-xs' : 'text-sm'">
            <li
                v-for="row in visibleRows"
                :key="row.key"
                class="flex justify-between gap-3 px-4 py-2"
                :class="row.key === todayKey ? 'bg-[#003874]/5' : ''"
            >
                <span class="font-bold shrink-0" :class="row.key === todayKey ? 'text-[#003874]' : 'text-slate-600'">
                    <span v-if="expanded" class="w-10 inline-block">{{ row.label }}</span>
                    <span v-else>{{ row.full }}</span>
                    <span v-if="row.key === todayKey && expanded" class="text-[10px] font-black uppercase text-[#003874] ml-1">Hoy</span>
                </span>
                <span class="text-right font-medium" :class="row.closed ? 'text-slate-400' : 'text-slate-800'">
                    {{ row.text }}
                </span>
            </li>
        </ul>
    </div>
</template>
