<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import { escrowEnabled } from '@/services/features';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const data = ref(null);
const loading = ref(false);
const err = ref('');
const escrow = escrowEnabled();

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/dashboard', { auth: true });
        data.value = r.data || null;
    } catch (e) {
        err.value = e.message;
    } finally {
        loading.value = false;
    }
}
onMounted(load);

const subscriptionsActive = computed(() => {
    if (!data.value) return false;
    return !!data.value.features?.subscriptions;
});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 rounded-3xl bg-grad-hero text-white p-6 md:p-10 relative overflow-hidden">
            <div class="relative z-10 flex flex-wrap justify-between items-end gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-white/70">Administración</p>
                    <h1 class="text-3xl md:text-4xl font-black tracking-tight mt-1">Panel general</h1>
                    <p class="text-white/80 mt-1">Estado en vivo de la plataforma.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <RouterLink :to="{ name: 'admin-subscriptions' }" class="rounded-full bg-white text-[#003874] font-bold px-5 py-2.5 text-sm shadow no-underline hover:brightness-105">Revisar membresías</RouterLink>
                    <RouterLink :to="{ name: 'admin-users' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Usuarios</RouterLink>
                    <RouterLink :to="{ name: 'admin-moderation' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Moderación</RouterLink>
                    <RouterLink :to="{ name: 'admin-ledger' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Kardex</RouterLink>
                    <RouterLink :to="{ name: 'admin-category-suggestions' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Categorías</RouterLink>
                    <RouterLink :to="{ name: 'admin-settings' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Configuración</RouterLink>
                    <RouterLink v-if="escrow" :to="{ name: 'admin-payments' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Pagos en custodia</RouterLink>
                    <RouterLink v-if="escrow" :to="{ name: 'admin-withdrawals' }" class="rounded-full bg-white/10 border border-white/20 backdrop-blur text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-white/20">Procesar retiros</RouterLink>
                </div>
            </div>
            <div class="pointer-events-none absolute -bottom-20 -right-20 w-72 h-72 bg-[#ff7a2b]/30 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -top-20 -left-20 w-72 h-72 bg-[#7c3aed]/30 rounded-full blur-3xl"></div>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <p v-if="loading && !data" class="text-slate-500">Cargando…</p>

        <template v-else-if="data">
            <!-- Bloque de membresías (siempre visible si subscriptions=on) -->
            <section v-if="subscriptionsActive" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-2xl bg-grad-card-amber border border-amber-200 p-5 glow-amber relative overflow-hidden">
                    <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-warm flex items-center justify-center text-white">
                        <span class="material-symbols-outlined text-[22px]">priority_high</span>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest text-amber-900">Pagos por revisar</p>
                    <p class="text-4xl font-black text-amber-950 mt-2">{{ data.kpis.subs_payments_pending || 0 }}</p>
                    <p class="text-sm text-amber-900 font-bold mt-0.5"><Money :amount="data.kpis.subs_payments_pending_amount || 0" /></p>
                    <RouterLink :to="{ name: 'admin-subscriptions' }" class="text-xs font-bold text-amber-900 hover:underline mt-1 inline-block no-underline">Validar Yape →</RouterLink>
                </div>
                <div class="rounded-2xl bg-grad-card-emerald border border-emerald-200 p-5 glow-emerald relative overflow-hidden">
                    <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-emerald flex items-center justify-center text-white">
                        <span class="material-symbols-outlined text-[22px]">trending_up</span>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-900">MRR (recurrente)</p>
                    <p class="text-3xl font-black text-emerald-950 mt-2"><Money :amount="data.kpis.mrr || 0" /></p>
                    <p class="text-xs text-emerald-900/80 mt-1">Ingreso mensual estimado</p>
                </div>
                <div class="rounded-2xl bg-grad-card-violet border border-violet-200 p-5 relative overflow-hidden" style="box-shadow: 0 16px 40px -16px rgba(124, 58, 237, 0.30)">
                    <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-violet flex items-center justify-center text-white">
                        <span class="material-symbols-outlined text-[22px]">workspace_premium</span>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest text-violet-900">Pro activos</p>
                    <p class="text-4xl font-black text-violet-950 mt-2">{{ data.kpis.subs_pro_active || 0 }}</p>
                    <p class="text-xs text-violet-900/70 mt-1">Negocios con plan Pro</p>
                </div>
                <div class="rounded-2xl bg-grad-card-coral border border-rose-200 p-5 relative overflow-hidden" style="box-shadow: 0 16px 40px -16px rgba(255, 94, 126, 0.30)">
                    <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-warm flex items-center justify-center text-white">
                        <span class="material-symbols-outlined text-[22px]">verified</span>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest text-rose-900">Premium activos</p>
                    <p class="text-4xl font-black text-rose-950 mt-2">{{ data.kpis.subs_premium_active || 0 }}</p>
                    <p class="text-xs text-rose-900/70 mt-1">Clientes con plan Premium</p>
                </div>
            </section>

            <!-- Bloque de escrow (solo si está activo) -->
            <section v-if="escrow" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-2xl bg-grad-card-amber border border-amber-200 p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-amber-900">Pagos por revisar (custodia)</p>
                    <p class="text-3xl font-black text-amber-950 mt-2">{{ data.kpis.payments_pending_review }}</p>
                    <RouterLink :to="{ name: 'admin-payments' }" class="text-xs font-bold text-amber-900 hover:underline mt-1 inline-block no-underline">Ir →</RouterLink>
                </div>
                <div class="rounded-2xl bg-grad-card-emerald border border-emerald-200 p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-900">Retiros solicitados</p>
                    <p class="text-3xl font-black text-emerald-950 mt-2">{{ data.kpis.withdrawals_pending }}</p>
                    <p class="text-sm text-emerald-900 font-bold"><Money :amount="data.kpis.withdrawals_pending_amount" /></p>
                </div>
                <div class="rounded-2xl bg-grad-card-blue border border-blue-200 p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">Comisión acumulada</p>
                    <p class="text-2xl font-black text-[#003874] mt-2"><Money :amount="data.kpis.commission_total" /></p>
                </div>
                <div class="rounded-2xl bg-grad-card-violet border border-violet-200 p-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-violet-900">En custodia</p>
                    <p class="text-2xl font-black text-violet-950 mt-2"><Money :amount="data.kpis.gross_in_escrow" /></p>
                </div>
            </section>

            <!-- Bloque de usuarios siempre visible -->
            <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-[#003874] flex items-center justify-center"><span class="material-symbols-outlined">person</span></div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Clientes</p>
                    </div>
                    <p class="text-3xl font-black text-[#003874] mt-3">{{ data.kpis.users_clients }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-orange-50 text-[#ff7a2b] flex items-center justify-center"><span class="material-symbols-outlined">handyman</span></div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Negocios</p>
                    </div>
                    <p class="text-3xl font-black text-[#003874] mt-3">{{ data.kpis.users_providers }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ data.kpis.providers_with_profile }} con perfil</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center"><span class="material-symbols-outlined">trending_up</span></div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Solicitudes activas</p>
                    </div>
                    <p class="text-3xl font-black text-[#003874] mt-3">{{ data.kpis.requests_active }}</p>
                </div>
                <div v-if="escrow" class="rounded-2xl border border-slate-200 bg-white p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center"><span class="material-symbols-outlined">account_balance_wallet</span></div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Saldo negocios</p>
                    </div>
                    <p class="text-2xl font-black text-emerald-700 mt-3"><Money :amount="data.kpis.wallets_total_balance" /></p>
                    <p class="text-xs text-slate-500 mt-1">Por retirar</p>
                </div>
                <div v-else class="rounded-2xl border border-slate-200 bg-white p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center"><span class="material-symbols-outlined">card_membership</span></div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Free activos</p>
                    </div>
                    <p class="text-3xl font-black text-slate-700 mt-3">{{ data.kpis.subs_free_active || 0 }}</p>
                    <p class="text-xs text-slate-500 mt-1">Sin pagar membresía</p>
                </div>
            </section>

            <!-- Últimos pagos de membresías -->
            <section v-if="subscriptionsActive" class="rounded-2xl border border-slate-200 bg-white p-6 mb-6">
                <header class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-slate-900">Últimos pagos de membresía</h2>
                    <RouterLink :to="{ name: 'admin-subscriptions' }" class="text-sm font-bold text-[#003874] hover:underline no-underline">Ver todos →</RouterLink>
                </header>
                <p v-if="!data.latest_subscription_payments?.length" class="text-slate-500">Sin pagos de membresía aún.</p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[600px]">
                        <thead class="text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="text-left pb-2">#</th>
                                <th class="text-left pb-2">Usuario</th>
                                <th class="text-left pb-2">Plan</th>
                                <th class="text-right pb-2">Monto</th>
                                <th class="text-left pb-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in data.latest_subscription_payments" :key="p.id" class="border-t border-slate-100">
                                <td class="py-2 font-bold">#{{ p.id }}</td>
                                <td class="py-2">{{ p.user_name }} <span class="text-xs text-slate-500">({{ p.user_role }})</span></td>
                                <td class="py-2">{{ p.plan_name }}</td>
                                <td class="py-2 text-right font-bold"><Money :amount="p.amount" /></td>
                                <td class="py-2"><StatusPill :status="p.status" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Últimos pagos en custodia (solo si escrow=on) -->
            <section v-if="escrow" class="rounded-2xl border border-slate-200 bg-white p-6">
                <header class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-slate-900">Últimos pagos en custodia</h2>
                    <RouterLink :to="{ name: 'admin-payments' }" class="text-sm font-bold text-[#003874] hover:underline no-underline">Ver todos →</RouterLink>
                </header>
                <p v-if="!data.latest_payments?.length" class="text-slate-500">Sin pagos.</p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[700px]">
                        <thead class="text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="text-left pb-2">#</th>
                                <th class="text-left pb-2">Servicio</th>
                                <th class="text-left pb-2">Cliente</th>
                                <th class="text-left pb-2">Negocio</th>
                                <th class="text-right pb-2">Monto</th>
                                <th class="text-right pb-2">Comisión</th>
                                <th class="text-left pb-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in data.latest_payments" :key="p.id" class="border-t border-slate-100">
                                <td class="py-2 font-bold">#{{ p.id }}</td>
                                <td class="py-2">{{ p.service_title }}</td>
                                <td class="py-2">{{ p.client_name }}</td>
                                <td class="py-2">{{ p.provider_name }}</td>
                                <td class="py-2 text-right font-bold"><Money :amount="p.amount" /></td>
                                <td class="py-2 text-right text-red-700"><Money :amount="p.commission_amount" /></td>
                                <td class="py-2"><StatusPill :status="p.status" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>
