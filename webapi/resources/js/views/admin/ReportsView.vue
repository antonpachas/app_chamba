<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';

const from = ref(new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10));
const to = ref(new Date().toISOString().slice(0, 10));
const categories = ref([]);
const queries = ref([]);
const loading = ref(false);

async function load() {
    loading.value = true;
    try {
        const [c, q] = await Promise.all([
            api.get('/admin/reports/top-categories', { params: { from: from.value, to: to.value }, auth: true }),
            api.get('/admin/reports/top-queries', { params: { from: from.value, to: to.value }, auth: true }),
        ]);
        categories.value = c.data || [];
        queries.value = q.data || [];
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
                <h1 class="text-2xl font-bold text-[#0b1c30]">Reporte de Búsquedas</h1>
                <p class="text-sm text-slate-500 mt-0.5">Categorías y términos más buscados en el periodo.</p>
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

        <div v-else class="grid md:grid-cols-2 gap-6">
            <!-- Categorías -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="font-bold text-base text-slate-900 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-[#003874]">category</span>
                    Categorías más buscadas
                </h2>
                <div v-if="!categories.length" class="text-sm text-slate-400">Sin datos en el periodo.</div>
                <ul v-else class="space-y-2">
                    <li
                        v-for="(row, i) in categories"
                        :key="row.category_id"
                        class="flex items-center gap-3"
                    >
                        <span class="text-xs font-bold text-slate-400 w-5 text-right">{{ i + 1 }}</span>
                        <span class="flex-1 text-sm text-slate-800 truncate">{{ row.category_name }}</span>
                        <span class="text-sm font-bold tabular-nums text-slate-900">{{ row.searches }}</span>
                        <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-[#003874] rounded-full"
                                :style="{ width: Math.round((row.searches / categories[0].searches) * 100) + '%' }"
                            />
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Términos -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="font-bold text-base text-slate-900 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-[#003874]">search</span>
                    Términos más buscados
                </h2>
                <div v-if="!queries.length" class="text-sm text-slate-400">Sin datos en el periodo.</div>
                <ul v-else class="space-y-2">
                    <li
                        v-for="(row, i) in queries"
                        :key="row.query"
                        class="flex items-center gap-3"
                    >
                        <span class="text-xs font-bold text-slate-400 w-5 text-right">{{ i + 1 }}</span>
                        <span class="flex-1 text-sm text-slate-800 truncate">{{ row.query }}</span>
                        <span class="text-sm font-bold tabular-nums text-slate-900">{{ row.searches }}</span>
                        <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-sky-500 rounded-full"
                                :style="{ width: Math.round((row.searches / queries[0].searches) * 100) + '%' }"
                            />
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
