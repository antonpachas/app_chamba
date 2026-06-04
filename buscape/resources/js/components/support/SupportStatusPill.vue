<script setup>
const props = defineProps({
    status: { type: String, required: true },
});

const map = {
    abierto: { label: 'Abierto', class: 'bg-sky-100 text-sky-800' },
    en_progreso: { label: 'En progreso', class: 'bg-indigo-100 text-indigo-800' },
    esperando_usuario: { label: 'Esperando usuario', class: 'bg-amber-100 text-amber-900' },
    pendiente_soporte: { label: 'Pendiente soporte', class: 'bg-orange-100 text-orange-900' },
    resuelto: { label: 'Resuelto', class: 'bg-emerald-100 text-emerald-800' },
    cerrado: { label: 'Cerrado', class: 'bg-slate-200 text-slate-700' },
};

const cfg = map[props.status] || { label: props.status, class: 'bg-slate-100 text-slate-700' };
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="cfg.class">
        {{ cfg.label }}
    </span>
</template>
