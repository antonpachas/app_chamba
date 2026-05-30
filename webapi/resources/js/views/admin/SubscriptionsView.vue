<script setup>
import { onMounted, ref } from 'vue';
import { useAdminSubscriptionsStore } from '@/stores/adminSubscriptions';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const store = useAdminSubscriptionsStore();
const filter = ref('pendiente_revision');
const err = ref('');
const ok = ref('');
const busy = ref(null);

async function load() {
    err.value = '';
    try { await store.loadPayments(filter.value); }
    catch (e) { err.value = e.message; }
}
onMounted(load);

async function confirm(id) {
    busy.value = id; err.value = ''; ok.value = '';
    try { await store.confirm(id); ok.value = 'Membresía activada.'; }
    catch (e) { err.value = e.message; }
    finally { busy.value = null; }
}
async function reject(id) {
    busy.value = id; err.value = ''; ok.value = '';
    try { await store.reject(id, 'No se validó la transferencia'); ok.value = 'Pago rechazado.'; }
    catch (e) { err.value = e.message; }
    finally { busy.value = null; }
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex justify-between items-end gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Membresías</h1>
                <p class="text-slate-600 mt-1">Confirma pagos de Pro/Premium para activar las suscripciones.</p>
            </div>
            <select v-model="filter" @change="load()" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                <option value="pendiente_revision">Pendientes</option>
                <option value="confirmado">Confirmados</option>
                <option value="rechazado">Rechazados</option>
                <option value="all">Todos</option>
            </select>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!store.payments.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">Sin pagos de membresía.</div>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Usuario</th>
                        <th class="text-left px-4 py-3">Plan</th>
                        <th class="text-right px-4 py-3">Monto</th>
                        <th class="text-left px-4 py-3">Método/Ref</th>
                        <th class="text-left px-4 py-3">Comprobante</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in store.payments" :key="p.id" class="border-t border-slate-100">
                        <td class="px-4 py-3 font-bold">#{{ p.id }}</td>
                        <td class="px-4 py-3">
                            {{ p.user?.full_name }}<br>
                            <span class="text-xs text-slate-500">{{ p.user?.email }} · {{ p.user?.phone }} · {{ p.user?.role }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-bold">{{ p.plan?.name }}</span>
                            <span class="ml-1 text-xs uppercase font-bold rounded-full px-2 py-0.5"
                                :class="p.plan?.tier === 'pro' ? 'bg-violet-100 text-violet-700' : p.plan?.tier === 'premium' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600'">
                                {{ p.plan?.tier }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold"><Money :amount="p.amount" /></td>
                        <td class="px-4 py-3 text-xs">{{ p.payment_method }} · {{ p.payment_reference || '—' }}</td>
                        <td class="px-4 py-3">
                            <a v-if="p.proof_image_url" :href="p.proof_image_url" target="_blank" rel="noopener" class="inline-block">
                                <img :src="p.proof_image_url" alt="comprobante" class="w-14 h-14 object-cover rounded ring-1 ring-slate-200 hover:ring-chamba-500 transition" />
                            </a>
                            <span v-else class="text-xs text-rose-700 font-bold">Sin comprobante</span>
                        </td>
                        <td class="px-4 py-3"><StatusPill :status="p.status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ new Date(p.created_at).toLocaleString('es-PE') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div v-if="p.status === 'pendiente_revision'" class="flex justify-end gap-1">
                                <AppButton size="sm" variant="primary" :loading="busy === p.id" @click="confirm(p.id)">Activar</AppButton>
                                <AppButton size="sm" variant="ghost" :loading="busy === p.id" @click="reject(p.id)">Rechazar</AppButton>
                            </div>
                            <span v-else-if="p.status === 'confirmado' && p.period_end" class="text-xs text-emerald-700 font-bold">
                                Hasta {{ new Date(p.period_end).toLocaleDateString('es-PE') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div v-if="store.meta.last_page > 1" class="flex items-center justify-between gap-2 mt-4">
            <p class="text-xs text-slate-500">
                Página {{ store.meta.current_page }} de {{ store.meta.last_page }} · {{ store.meta.total }} registros
            </p>
            <div class="flex gap-1 flex-wrap">
                <button
                    v-for="p in store.meta.last_page"
                    :key="p"
                    type="button"
                    class="h-8 min-w-[32px] px-2 rounded-lg border text-sm transition-colors"
                    :class="p === store.meta.current_page ? 'bg-[#003874] text-white border-[#003874]' : 'border-slate-200 hover:bg-slate-50'"
                    @click="store.loadPayments(filter, p)"
                >{{ p }}</button>
            </div>
        </div>
    </div>
</template>
