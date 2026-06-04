<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';

const from = ref(new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10));
const to = ref(new Date().toISOString().slice(0, 10));
const loading = ref(false);
const data = ref(null);

async function load() {
    loading.value = true;
    try {
        const r = await api.get('/admin/reports/users', {
            params: { from: from.value, to: to.value },
            auth: true,
        });
        data.value = r.data;
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function pct(part, total) {
    if (!total) return '0%';
    return Math.round((part / total) * 100) + '%';
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[#0b1c30]">Reporte de Usuarios</h1>
                <p class="text-sm text-slate-500 mt-0.5">Registro de nuevas cuentas y estadísticas generales.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input v-model="from" type="date" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                <input v-model="to" type="date" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                <button
                    type="button"
                    class="rounded-lg bg-[#003874] text-white px-4 py-2 text-sm font-bold hover:bg-[#002e60]"
                    :disabled="loading"
                    @click="load"
                >
                    Actualizar
                </button>
            </div>
        </div>

        <div v-if="loading" class="py-16 text-center text-slate-400">Cargando…</div>

        <template v-else-if="data">
            <!-- Tarjetas resumen -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Total usuarios</p>
                    <p class="text-3xl font-extrabold text-slate-900">{{ data.totals?.total ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Clientes</p>
                    <p class="text-3xl font-extrabold text-[#003874]">{{ data.totals?.clientes ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ pct(data.totals?.clientes, data.totals?.total) }} del total</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Negocios</p>
                    <p class="text-3xl font-extrabold text-emerald-700">{{ data.totals?.proveedores ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ pct(data.totals?.proveedores, data.totals?.total) }} del total</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Suscripción Pro</p>
                    <p class="text-3xl font-extrabold text-amber-600">{{ data.pro_active ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">activos ahora</p>
                </div>
            </div>

            <!-- Registros en el periodo -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 mb-6">
                <h2 class="font-bold text-base text-slate-900 mb-4">
                    Nuevos registros en el periodo
                    <span class="text-sm font-normal text-slate-500">({{ from }} → {{ to }})</span>
                </h2>
                <div v-if="!data.by_day?.length" class="text-sm text-slate-400">Sin registros en el periodo.</div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase">
                            <th class="pb-2">Día</th>
                            <th class="pb-2 text-right">Clientes</th>
                            <th class="pb-2 text-right">Negocios</th>
                            <th class="pb-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in data.by_day"
                            :key="row.day"
                            class="border-b border-slate-50 hover:bg-slate-50"
                        >
                            <td class="py-1.5 font-mono text-slate-700">{{ row.day }}</td>
                            <td class="py-1.5 text-right text-[#003874] font-semibold">{{ row.clientes }}</td>
                            <td class="py-1.5 text-right text-emerald-700 font-semibold">{{ row.proveedores }}</td>
                            <td class="py-1.5 text-right font-bold text-slate-900">{{ row.total }}</td>
                        </tr>
                        <tr class="bg-slate-50 font-bold text-sm">
                            <td class="py-2">Total periodo</td>
                            <td class="py-2 text-right text-[#003874]">
                                {{ data.by_day.reduce((s, r) => s + Number(r.clientes), 0) }}
                            </td>
                            <td class="py-2 text-right text-emerald-700">
                                {{ data.by_day.reduce((s, r) => s + Number(r.proveedores), 0) }}
                            </td>
                            <td class="py-2 text-right text-slate-900">
                                {{ data.by_day.reduce((s, r) => s + Number(r.total), 0) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Alertas -->
            <div v-if="(data.totals?.suspendidos ?? 0) > 0" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                <span class="font-bold">{{ data.totals.suspendidos }}</span> cuenta(s) suspendidas en el sistema.
            </div>
        </template>
    </div>
</template>
