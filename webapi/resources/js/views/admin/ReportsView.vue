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
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-[#0b1c30] mb-6">Reportes de búsqueda</h1>
        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="from" type="date" class="rounded-lg border border-slate-200 px-3 py-2" />
            <input v-model="to" type="date" class="rounded-lg border border-slate-200 px-3 py-2" />
            <button type="button" class="rounded-lg bg-[#003874] text-white px-4 py-2 font-bold" :disabled="loading" @click="load">Actualizar</button>
        </div>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 mb-6">
            <h2 class="font-bold text-lg mb-4">Categorías más buscadas</h2>
            <ul class="space-y-2 text-sm">
                <li v-for="row in categories" :key="row.category_id" class="flex justify-between">
                    <span>{{ row.category_name }}</span>
                    <strong>{{ row.searches }}</strong>
                </li>
                <li v-if="!categories.length" class="text-slate-500">Sin datos en el periodo.</li>
            </ul>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="font-bold text-lg mb-4">Términos más buscados</h2>
            <ul class="space-y-2 text-sm">
                <li v-for="row in queries" :key="row.query" class="flex justify-between gap-4">
                    <span class="truncate">{{ row.query }}</span>
                    <strong>{{ row.searches }}</strong>
                </li>
                <li v-if="!queries.length" class="text-slate-500">Sin datos en el periodo.</li>
            </ul>
        </section>
    </div>
</template>
