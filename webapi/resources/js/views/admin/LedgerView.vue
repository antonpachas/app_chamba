<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';

const rows = ref([]);
const summary = ref({ ingresos: 0, egresos: 0, balance: 0 });
const loading = ref(false);
const err = ref('');
const msg = ref('');

const filters = reactive({
    type: 'all',
    from: '',
    to: '',
});

const form = reactive({
    category: 'general',
    amount: '',
    description: '',
    occurred_at: new Date().toISOString().slice(0, 10),
});

function fmtDate(d) {
    if (!d) return '—';
    try {
        return new Date(d).toLocaleDateString('es-PE', { dateStyle: 'medium' });
    } catch {
        return String(d);
    }
}

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/ledger', {
            auth: true,
            params: {
                type: filters.type === 'all' ? undefined : filters.type,
                from: filters.from || undefined,
                to: filters.to || undefined,
            },
        });
        rows.value = r.data || [];
        summary.value = r.summary || summary.value;
    } catch (e) {
        err.value = e.message;
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

async function addExpense() {
    msg.value = '';
    err.value = '';
    try {
        await api.post(
            '/admin/ledger/expenses',
            { ...form, amount: Number(form.amount) },
            { auth: true },
        );
        msg.value = 'Egreso registrado.';
        form.amount = '';
        form.description = '';
        await load();
    } catch (e) {
        err.value = e.message;
    }
}

onMounted(load);
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Admin · Finanzas"
            title="Kardex"
            subtitle="Ingresos por membresías confirmadas y egresos manuales de la plataforma."
        />

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="msg" type="success" class="mb-4">{{ msg }}</AppAlert>

        <div class="grid sm:grid-cols-3 gap-4 mb-8">
            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-5">
                <p class="text-xs font-bold uppercase text-emerald-800">Ingresos</p>
                <p class="text-2xl font-black text-emerald-900 mt-1"><Money :amount="summary.ingresos" /></p>
            </div>
            <div class="rounded-xl bg-rose-50 border border-rose-100 p-5">
                <p class="text-xs font-bold uppercase text-rose-800">Egresos</p>
                <p class="text-2xl font-black text-rose-900 mt-1"><Money :amount="summary.egresos" /></p>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 p-5">
                <p class="text-xs font-bold uppercase text-slate-600">Balance</p>
                <p class="text-2xl font-black text-slate-900 mt-1"><Money :amount="summary.balance" /></p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-6 items-end">
            <label class="text-sm">
                <span class="block text-xs font-bold uppercase text-slate-500 mb-1">Desde</span>
                <input v-model="filters.from" type="date" class="rounded-lg border border-slate-200 px-3 py-2" />
            </label>
            <label class="text-sm">
                <span class="block text-xs font-bold uppercase text-slate-500 mb-1">Hasta</span>
                <input v-model="filters.to" type="date" class="rounded-lg border border-slate-200 px-3 py-2" />
            </label>
            <label class="text-sm">
                <span class="block text-xs font-bold uppercase text-slate-500 mb-1">Tipo</span>
                <select v-model="filters.type" class="rounded-lg border border-slate-200 px-3 py-2">
                    <option value="all">Todos</option>
                    <option value="ingreso">Ingresos</option>
                    <option value="egreso">Egresos</option>
                </select>
            </label>
            <AppButton variant="primary" @click="load">Filtrar</AppButton>
            <RouterLink :to="{ name: 'admin-dashboard' }" class="text-sm font-bold text-[#003874] hover:underline no-underline py-2">
                ← Panel admin
            </RouterLink>
        </div>

        <form class="rounded-2xl border border-slate-200 bg-white p-6 mb-8 space-y-3 shadow-sm" @submit.prevent="addExpense">
            <h2 class="font-bold text-lg text-slate-900">Registrar egreso</h2>
            <p class="text-sm text-slate-500">Los ingresos se registran al confirmar pagos de membresía en Admin → Membresías.</p>
            <div class="grid sm:grid-cols-2 gap-3">
                <input
                    v-model="form.category"
                    placeholder="Categoría (ej. hosting, ads)"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2"
                    required
                />
                <input
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="Monto (PEN)"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2"
                    required
                />
                <input v-model="form.occurred_at" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2" required />
            </div>
            <textarea
                v-model="form.description"
                rows="2"
                placeholder="Descripción del egreso"
                class="w-full rounded-lg border border-slate-200 px-3 py-2"
            ></textarea>
            <AppButton variant="primary" type="submit">Guardar egreso</AppButton>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Tipo</th>
                        <th class="p-3">Categoría</th>
                        <th class="p-3">Monto</th>
                        <th class="p-3">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="5" class="p-8 text-center text-slate-500">Cargando movimientos…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td colspan="5" class="p-8 text-center text-slate-500">Sin movimientos en el periodo.</td>
                    </tr>
                    <tr
                        v-for="r in rows"
                        :key="r.id"
                        class="border-t border-slate-100"
                        :class="r.type === 'ingreso' ? 'bg-emerald-50/30' : ''"
                    >
                        <td class="p-3 whitespace-nowrap">{{ fmtDate(r.occurred_at) }}</td>
                        <td class="p-3">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                :class="r.type === 'ingreso' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ r.type }}
                            </span>
                        </td>
                        <td class="p-3">{{ r.category || '—' }}</td>
                        <td class="p-3 font-bold" :class="r.type === 'ingreso' ? 'text-emerald-800' : 'text-rose-800'">
                            <Money :amount="r.amount" />
                        </td>
                        <td class="p-3 text-slate-600">{{ r.description || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
