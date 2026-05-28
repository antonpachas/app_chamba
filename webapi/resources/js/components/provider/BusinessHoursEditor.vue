<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue']);

const days = [
    { key: 'mon', label: 'Lunes' },
    { key: 'tue', label: 'Martes' },
    { key: 'wed', label: 'Miércoles' },
    { key: 'thu', label: 'Jueves' },
    { key: 'fri', label: 'Viernes' },
    { key: 'sat', label: 'Sábado' },
    { key: 'sun', label: 'Domingo' },
];

const schedule = computed({
    get() {
        return props.modelValue || defaultSchedule();
    },
    set(v) {
        emit('update:modelValue', v);
    },
});

function defaultSchedule() {
    return {
        mon: { closed: false, open: '09:00', close: '18:00' },
        tue: { closed: false, open: '09:00', close: '18:00' },
        wed: { closed: false, open: '09:00', close: '18:00' },
        thu: { closed: false, open: '09:00', close: '18:00' },
        fri: { closed: false, open: '09:00', close: '18:00' },
        sat: { closed: false, open: '09:00', close: '14:00' },
        sun: { closed: true, open: '', close: '' },
    };
}

watch(
    () => props.modelValue,
    (v) => {
        if (!v) {
            emit('update:modelValue', defaultSchedule());
        }
    },
    { immediate: true },
);

function patchDay(key, patch) {
    schedule.value = {
        ...schedule.value,
        [key]: { ...schedule.value[key], ...patch },
    };
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3">
        <div>
            <p class="text-sm font-bold text-slate-800">Horario de atención</p>
            <p class="text-xs text-slate-500 mt-0.5">Los clientes verán si estás abierto y tu rango horario en cada anuncio.</p>
        </div>
        <div
            v-for="day in days"
            :key="day.key"
            class="grid grid-cols-1 sm:grid-cols-[7rem_1fr] gap-2 sm:gap-3 items-center bg-white rounded-lg px-3 py-2 border border-slate-100"
        >
            <span class="text-sm font-semibold text-slate-700">{{ day.label }}</span>
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600">
                    <input
                        type="checkbox"
                        class="rounded border-slate-300"
                        :checked="!!schedule[day.key]?.closed"
                        @change="patchDay(day.key, { closed: $event.target.checked })"
                    />
                    Cerrado
                </label>
                <template v-if="!schedule[day.key]?.closed">
                    <input
                        type="time"
                        class="text-sm border border-slate-200 rounded-lg px-2 py-1"
                        :value="schedule[day.key]?.open || '09:00'"
                        @input="patchDay(day.key, { open: $event.target.value })"
                    />
                    <span class="text-slate-400 text-xs">a</span>
                    <input
                        type="time"
                        class="text-sm border border-slate-200 rounded-lg px-2 py-1"
                        :value="schedule[day.key]?.close || '18:00'"
                        @input="patchDay(day.key, { close: $event.target.value })"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
