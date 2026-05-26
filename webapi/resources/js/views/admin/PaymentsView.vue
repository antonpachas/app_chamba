<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const items = ref([]);
const loading = ref(false);
const filter = ref('pendiente_revision');
const err = ref('');
const ok = ref('');
const busy = ref(null);

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/payments', { auth: true, params: { status: filter.value } });
        items.value = r.data || [];
    } catch (e) { err.value = e.message; items.value = []; }
    finally { loading.value = false; }
}
onMounted(load);

async function confirm(id) {
    busy.value = id; err.value = ''; ok.value = '';
    try { await api.post(`/admin/payments/${id}/confirm`, undefined, { auth: true }); ok.value = 'Pago confirmado.'; await load(); }
    catch (e) { err.value = e.message; }
    finally { busy.value = null; }
}
async function reject(id) {
    busy.value = id; err.value = ''; ok.value = '';
    try { await api.post(`/admin/payments/${id}/reject`, { notes: 'No se validó la transferencia' }, { auth: true }); ok.value = 'Pago rechazado.'; await load(); }
    catch (e) { err.value = e.message; }
    finally { busy.value = null; }
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex justify-between items-end gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Pagos</h1>
                <p class="text-slate-600 mt-1">Confirma transferencias recibidas y libera fondos.</p>
            </div>
            <select v-model="filter" @change="load()" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                <option value="pendiente_revision">Pendientes de revisión</option>
                <option value="en_custodia">En custodia</option>
                <option value="liberado">Liberados</option>
                <option value="rechazado">Rechazados</option>
                <option value="all">Todos</option>
            </select>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <p v-if="loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">Sin pagos.</div>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Proveedor</th>
                        <th class="text-left px-4 py-3">Servicio</th>
                        <th class="text-right px-4 py-3">Monto</th>
                        <th class="text-right px-4 py-3">Comisión</th>
                        <th class="text-right px-4 py-3">Neto</th>
                        <th class="text-left px-4 py-3">Método/Ref</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in items" :key="p.id" class="border-t border-slate-100">
                        <td class="px-4 py-3 font-bold">#{{ p.id }}</td>
                        <td class="px-4 py-3">{{ p.client?.full_name }}<br><span class="text-xs text-slate-500">{{ p.client?.email }} · {{ p.client?.phone }}</span></td>
                        <td class="px-4 py-3">{{ p.provider?.name }}</td>
                        <td class="px-4 py-3">{{ p.service_title }}</td>
                        <td class="px-4 py-3 text-right font-bold"><Money :amount="p.amount" /></td>
                        <td class="px-4 py-3 text-right text-red-700"><Money :amount="p.commission_amount" /></td>
                        <td class="px-4 py-3 text-right text-emerald-700"><Money :amount="p.net_amount" /></td>
                        <td class="px-4 py-3 text-xs">{{ p.payment_method }} · {{ p.payment_reference || '—' }}</td>
                        <td class="px-4 py-3"><StatusPill :status="p.status" /></td>
                        <td class="px-4 py-3 text-right">
                            <div v-if="p.status === 'pendiente_revision'" class="flex justify-end gap-1">
                                <AppButton size="sm" variant="primary" :loading="busy === p.id" @click="confirm(p.id)">Confirmar</AppButton>
                                <AppButton size="sm" variant="ghost" :loading="busy === p.id" @click="reject(p.id)">Rechazar</AppButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
