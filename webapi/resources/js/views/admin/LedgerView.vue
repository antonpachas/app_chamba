<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '@/services/api';
import Money from '@/components/common/Money.vue';

const rows = ref([]);
const summary = ref({ ingresos: 0, egresos: 0, balance: 0 });
const form = reactive({ category: 'general', amount: '', description: '', occurred_at: new Date().toISOString().slice(0, 10) });
const msg = ref('');

async function load() {
    const r = await api.get('/admin/ledger', { auth: true });
    rows.value = r.data || [];
    summary.value = r.summary || summary.value;
}

async function addExpense() {
    msg.value = '';
    await api.post('/admin/ledger/expenses', { ...form, amount: Number(form.amount) }, { auth: true });
    msg.value = 'Egreso registrado.';
    form.amount = '';
    form.description = '';
    await load();
}

onMounted(load);
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-[#0b1c30] mb-2">Kardex</h1>
        <p class="text-slate-600 mb-6">Ingresos por suscripciones y egresos manuales.</p>
        <div class="grid sm:grid-cols-3 gap-4 mb-8">
            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                <p class="text-xs font-bold uppercase text-emerald-800">Ingresos</p>
                <p class="text-2xl font-black text-emerald-900"><Money :amount="summary.ingresos" /></p>
            </div>
            <div class="rounded-xl bg-rose-50 border border-rose-100 p-4">
                <p class="text-xs font-bold uppercase text-rose-800">Egresos</p>
                <p class="text-2xl font-black text-rose-900"><Money :amount="summary.egresos" /></p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-xs font-bold uppercase text-slate-600">Balance</p>
                <p class="text-2xl font-black text-slate-900"><Money :amount="summary.balance" /></p>
            </div>
        </div>
        <form class="rounded-2xl border border-slate-200 bg-white p-6 mb-8 space-y-3" @submit.prevent="addExpense">
            <h2 class="font-bold">Registrar egreso</h2>
            <input v-model="form.category" placeholder="Categoría" class="w-full rounded-lg border border-slate-200 px-3 py-2" required />
            <input v-model="form.amount" type="number" step="0.01" placeholder="Monto" class="w-full rounded-lg border border-slate-200 px-3 py-2" required />
            <input v-model="form.occurred_at" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2" required />
            <textarea v-model="form.description" rows="2" placeholder="Descripción" class="w-full rounded-lg border border-slate-200 px-3 py-2"></textarea>
            <button type="submit" class="rounded-lg bg-[#003874] text-white px-4 py-2 font-bold">Guardar egreso</button>
            <p v-if="msg" class="text-sm text-emerald-700">{{ msg }}</p>
        </form>
        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left">
                    <tr>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Tipo</th>
                        <th class="p-3">Categoría</th>
                        <th class="p-3">Monto</th>
                        <th class="p-3">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows" :key="r.id" class="border-t border-slate-100">
                        <td class="p-3">{{ r.occurred_at }}</td>
                        <td class="p-3 capitalize">{{ r.type }}</td>
                        <td class="p-3">{{ r.category }}</td>
                        <td class="p-3 font-bold"><Money :amount="r.amount" /></td>
                        <td class="p-3 text-slate-600">{{ r.description }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
