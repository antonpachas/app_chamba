<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, default: '' } });

const map = {
    nuevo: { label: 'Nueva', cls: 'bg-blue-100 text-blue-800' },
    visto: { label: 'Visto', cls: 'bg-violet-100 text-violet-800' },
    contactado: { label: 'Contactado', cls: 'bg-violet-100 text-violet-800' },
    cotizado: { label: 'Cotizado', cls: 'bg-amber-100 text-amber-900' },
    aceptado: { label: 'Cotización aceptada', cls: 'bg-emerald-100 text-emerald-900' },
    pagado_pendiente: { label: 'Pago en revisión', cls: 'bg-yellow-100 text-yellow-900' },
    en_custodia: { label: 'Pagado (en custodia)', cls: 'bg-emerald-100 text-emerald-900' },
    en_progreso: { label: 'En progreso', cls: 'bg-cyan-100 text-cyan-900' },
    terminado: { label: 'Terminado', cls: 'bg-indigo-100 text-indigo-900' },
    confirmado: { label: 'Confirmado', cls: 'bg-emerald-200 text-emerald-900' },
    cancelado: { label: 'Cancelado', cls: 'bg-slate-200 text-slate-700' },
    cerrado: { label: 'Cerrado', cls: 'bg-slate-200 text-slate-700' },
    disputado: { label: 'En disputa', cls: 'bg-red-100 text-red-800' },
    pendiente: { label: 'Pendiente', cls: 'bg-amber-100 text-amber-900' },
    aceptada: { label: 'Aceptada', cls: 'bg-emerald-100 text-emerald-900' },
    rechazada: { label: 'Rechazada', cls: 'bg-slate-200 text-slate-700' },
    expirada: { label: 'Expirada', cls: 'bg-slate-200 text-slate-700' },
    pendiente_revision: { label: 'En revisión', cls: 'bg-amber-100 text-amber-900' },
    liberado: { label: 'Liberado', cls: 'bg-emerald-100 text-emerald-900' },
    reembolsado: { label: 'Reembolsado', cls: 'bg-blue-100 text-blue-900' },
    rechazado: { label: 'Rechazado', cls: 'bg-red-100 text-red-800' },
    solicitado: { label: 'Solicitado', cls: 'bg-amber-100 text-amber-900' },
    pagado: { label: 'Pagado', cls: 'bg-emerald-100 text-emerald-900' },
};

const item = computed(() => map[props.status] || { label: props.status || '—', cls: 'bg-slate-200 text-slate-700' });
</script>

<template>
    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide" :class="item.cls">
        {{ item.label }}
    </span>
</template>
