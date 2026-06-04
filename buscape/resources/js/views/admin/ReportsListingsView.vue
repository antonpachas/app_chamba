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
        const r = await api.get('/admin/reports/listings', {
            params: { from: from.value, to: to.value },
            auth: true,
        });
        data.value = r.data;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[#0b1c30]">Reporte de Anuncios</h1>
                <p class="text-sm text-slate-500 mt-0.5">Publicaciones del catálogo y contactos del mes.</p>
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
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Total anuncios</p>
                    <p class="text-3xl font-extrabold text-slate-900">{{ data.totals?.total ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Activos</p>
                    <p class="text-3xl font-extrabold text-emerald-700">{{ data.totals?.activos ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Ocultos/Mod.</p>
                    <p class="text-3xl font-extrabold text-slate-500">{{ data.totals?.ocultos ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contactos este mes</p>
                    <p class="text-3xl font-extrabold text-[#003874]">{{ data.contacts_this_month ?? 0 }}</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <!-- Nuevos anuncios por día -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 class="font-bold text-base text-slate-900 mb-4">
                        Nuevos anuncios por día
                        <span class="text-sm font-normal text-slate-500">({{ from }} → {{ to }})</span>
                    </h2>
                    <div v-if="!data.by_day?.length" class="text-sm text-slate-400">Sin datos en el periodo.</div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase">
                                <th class="pb-2">Día</th>
                                <th class="pb-2 text-right">Nuevos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in data.by_day"
                                :key="row.day"
                                class="border-b border-slate-50 hover:bg-slate-50"
                            >
                                <td class="py-1.5 font-mono text-slate-700">{{ row.day }}</td>
                                <td class="py-1.5 text-right font-semibold text-slate-900">{{ row.total }}</td>
                            </tr>
                            <tr class="bg-slate-50 font-bold">
                                <td class="py-2">Total periodo</td>
                                <td class="py-2 text-right text-slate-900">
                                    {{ data.by_day.reduce((s, r) => s + Number(r.total), 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Top categorías -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 class="font-bold text-base text-slate-900 mb-4">Categorías con más anuncios</h2>
                    <div v-if="!data.top_categories?.length" class="text-sm text-slate-400">Sin datos.</div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="(row, i) in data.top_categories"
                            :key="row.category_name"
                            class="flex items-center gap-3"
                        >
                            <span class="text-xs font-bold text-slate-400 w-5 text-right">{{ i + 1 }}</span>
                            <span class="flex-1 text-sm text-slate-800 truncate">{{ row.category_name }}</span>
                            <span class="text-sm font-bold text-slate-900 tabular-nums">{{ row.total }}</span>
                            <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-[#003874] rounded-full"
                                    :style="{ width: Math.round((row.total / data.top_categories[0].total) * 100) + '%' }"
                                />
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </template>
    </div>
</template>
