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
const saving = ref(false);
const err = ref('');
const msg = ref('');

const expenseModalOpen = ref(false);

const filters = reactive({
    type: 'all',
    from: '',
    to: '',
});

const defaultForm = () => ({
    category: 'general',
    amount: '',
    description: '',
    occurred_at: new Date().toISOString().slice(0, 10),
});

const form = reactive(defaultForm());

function fmtDate(d) {
    if (!d) return '—';
    try {
        return new Date(d).toLocaleDateString('es-PE', { dateStyle: 'medium' });
    } catch {
        return String(d);
    }
}

function typeLabel(type) {
    return type === 'ingreso' ? 'Ingreso' : type === 'egreso' ? 'Egreso' : type;
}

function referenceLabel(row) {
    if (!row.reference_type) return '—';
    const id = row.reference_id != null ? ` #${row.reference_id}` : '';
    return `${row.reference_type}${id}`;
}

function openExpenseModal() {
    Object.assign(form, defaultForm());
    expenseModalOpen.value = true;
    err.value = '';
}

function closeExpenseModal() {
    expenseModalOpen.value = false;
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
    saving.value = true;
    try {
        await api.post(
            '/admin/ledger/expenses',
            { ...form, amount: Number(form.amount) },
            { auth: true },
        );
        msg.value = 'Egreso registrado correctamente.';
        closeExpenseModal();
        await load();
    } catch (e) {
        err.value = e.message;
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Admin · Finanzas"
            title="Kardex"
            subtitle="Ingresos por membresías confirmadas y egresos manuales de la plataforma."
        />

        <AppAlert v-if="err && !expenseModalOpen" type="error" class="mb-4">{{ err }}</AppAlert>
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
                <p
                    class="text-2xl font-black mt-1"
                    :class="Number(summary.balance) >= 0 ? 'text-slate-900' : 'text-rose-800'"
                >
                    <Money :amount="summary.balance" />
                </p>
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
            <AppButton variant="primary" @click="openExpenseModal">
                <span class="material-symbols-outlined text-lg align-middle mr-1">add</span>
                Registrar egreso
            </AppButton>
            <RouterLink
                :to="{ name: 'admin-dashboard' }"
                class="text-sm font-bold text-[#003874] hover:underline no-underline py-2 ml-auto"
            >
                ← Panel admin
            </RouterLink>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm min-w-[720px]">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                        <th class="px-4 py-3">Detalle</th>
                        <th class="px-4 py-3">Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">Cargando movimientos…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                            Sin movimientos en el periodo seleccionado.
                        </td>
                    </tr>
                    <tr
                        v-for="r in rows"
                        :key="r.id"
                        class="border-t border-slate-100 hover:bg-slate-50/60 transition"
                        :class="r.type === 'ingreso' ? 'bg-emerald-50/25' : r.type === 'egreso' ? 'bg-rose-50/15' : ''"
                    >
                        <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ fmtDate(r.occurred_at) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                :class="
                                    r.type === 'ingreso'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-rose-100 text-rose-800'
                                "
                            >
                                {{ typeLabel(r.type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ r.category || '—' }}</td>
                        <td
                            class="px-4 py-3 text-right font-bold whitespace-nowrap"
                            :class="r.type === 'ingreso' ? 'text-emerald-800' : 'text-rose-800'"
                        >
                            <span v-if="r.type === 'egreso'">−</span><Money :amount="r.amount" />
                        </td>
                        <td class="px-4 py-3 text-slate-600 max-w-[280px]">
                            <span class="line-clamp-2">{{ r.description || '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 font-mono">{{ referenceLabel(r) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-500 mt-3 text-center">
            Los ingresos se registran al confirmar pagos en Admin → Membresías. Usa «Registrar egreso» para gastos de plataforma.
        </p>

        <!-- Modal registrar egreso -->
        <Teleport to="body">
            <div
                v-if="expenseModalOpen"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ledger-expense-modal-title"
                @click.self="closeExpenseModal"
            >
                <div
                    class="bg-white w-full sm:max-w-lg sm:rounded-2xl shadow-xl flex flex-col max-h-[92vh] rounded-t-2xl"
                    @click.stop
                >
                    <header class="flex items-start justify-between gap-3 px-5 py-4 border-b border-slate-200 shrink-0">
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-500">Kardex</p>
                            <h2 id="ledger-expense-modal-title" class="text-lg font-black text-slate-900">
                                Registrar egreso
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50"
                            aria-label="Cerrar"
                            @click="closeExpenseModal"
                        >
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </header>

                    <form class="flex-1 overflow-y-auto px-5 py-4 space-y-4" @submit.prevent="addExpense">
                        <AppAlert v-if="err && expenseModalOpen" type="error">{{ err }}</AppAlert>

                        <p class="text-sm text-slate-600">
                            Registra un gasto de la plataforma (hosting, publicidad, comisiones, etc.). Los ingresos
                            por membresía se cargan automáticamente al confirmar el pago.
                        </p>

                        <label class="block text-sm">
                            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Categoría</span>
                            <input
                                v-model="form.category"
                                required
                                maxlength="50"
                                placeholder="Ej. hosting, ads, soporte"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                            />
                        </label>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <label class="block text-sm">
                                <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Monto (PEN)</span>
                                <input
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="0.00"
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                                />
                            </label>
                            <label class="block text-sm">
                                <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Fecha</span>
                                <input
                                    v-model="form.occurred_at"
                                    type="date"
                                    required
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                                />
                            </label>
                        </div>

                        <label class="block text-sm">
                            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Descripción</span>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                maxlength="500"
                                placeholder="Detalle del egreso (opcional pero recomendado)"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5 resize-y"
                            ></textarea>
                        </label>

                        <div class="flex flex-wrap justify-end gap-2 pt-2 border-t border-slate-100">
                            <AppButton variant="ghost" type="button" :disabled="saving" @click="closeExpenseModal">
                                Cancelar
                            </AppButton>
                            <AppButton variant="primary" type="submit" :loading="saving">Guardar egreso</AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
