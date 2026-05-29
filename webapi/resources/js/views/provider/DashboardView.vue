<script setup>
import { onMounted, computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useProviderProfileStore } from '@/stores/providerProfile';
import { useWalletStore } from '@/stores/wallet';
import { useProviderRequestsStore } from '@/stores/providerRequests';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';
import Money from '@/components/common/Money.vue';
import StatusPill from '@/components/common/StatusPill.vue';

const profileStore = useProviderProfileStore();
const walletStore = useWalletStore();
const requestsStore = useProviderRequestsStore();
const auth = useAuthStore();
const escrow = escrowEnabled();

onMounted(async () => {
    const tasks = [profileStore.loadProfile(), profileStore.loadDashboard(), requestsStore.load()];
    if (escrow) tasks.push(walletStore.load());
    await Promise.all(tasks);
});

const pendingNew = computed(() => requestsStore.items.filter((r) => r.status === 'nuevo' || r.status === 'contactado').length);
const dashboard = computed(() => profileStore.dashboard || {});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 rounded-3xl bg-grad-hero text-white p-6 md:p-10 relative overflow-hidden">
            <div class="relative z-10 flex flex-wrap justify-between items-end gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-white/70">Bienvenido</p>
                    <h1 class="text-3xl md:text-4xl font-black tracking-tight mt-1">
                        Hola, {{ profileStore.profile?.business_name || profileStore.profile?.user?.full_name || 'Negocio' }}
                    </h1>
                    <p class="text-white/80 mt-1">Resumen de tu actividad en Busca PE.</p>
                </div>
                <RouterLink
                    v-if="!profileStore.profile"
                    :to="{ name: 'provider-profile' }"
                    class="rounded-full bg-white text-[#003874] font-bold px-5 py-2.5 text-sm shadow no-underline hover:brightness-105"
                >
                    Crear mi perfil
                </RouterLink>
            </div>
            <div class="pointer-events-none absolute -bottom-20 -right-20 w-72 h-72 bg-[#0ea5e9]/30 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -top-20 -left-20 w-72 h-72 bg-[#ff7a2b]/30 rounded-full blur-3xl"></div>
        </header>

        <RouterLink
            v-if="auth.inTrial"
            :to="{ name: 'provider-subscription' }"
            class="block mb-6 rounded-2xl bg-grad-warm text-white p-5 shadow-lg shadow-orange-500/20 hover:brightness-105 transition no-underline"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[28px]">schedule</span>
                    <div>
                        <p class="font-black text-lg leading-tight">Tu periodo de prueba termina en {{ auth.trialDaysLeft }} día(s)</p>
                        <p class="text-sm text-white/85">Suscríbete a Pro para no perder visibilidad ni contactos ilimitados.</p>
                    </div>
                </div>
                <span class="rounded-full bg-white text-orange-700 font-bold px-4 py-2 text-sm">Activar Pro</span>
            </div>
        </RouterLink>

        <RouterLink
            v-else-if="!auth.isPro"
            :to="{ name: 'provider-subscription' }"
            class="block mb-6 rounded-2xl bg-grad-violet text-white p-5 shadow-lg shadow-violet-500/20 hover:brightness-105 transition no-underline"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[28px]">workspace_premium</span>
                    <div>
                        <p class="font-black text-lg leading-tight">Estás en plan Free</p>
                        <p class="text-sm text-white/85">Pásate a Pro por S/ 29/mes y accede a contactos ilimitados, top en búsquedas y badge "Pro Verificado".</p>
                    </div>
                </div>
                <span class="rounded-full bg-white text-violet-700 font-bold px-4 py-2 text-sm">Hacerme Pro</span>
            </div>
        </RouterLink>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div v-if="escrow" class="rounded-2xl bg-grad-card-emerald border border-emerald-200 p-5 glow-emerald relative overflow-hidden">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-emerald flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">account_balance_wallet</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-900">Saldo disponible</p>
                <p class="text-3xl font-black text-emerald-950 mt-2"><Money :amount="walletStore.wallet?.balance" /></p>
                <RouterLink :to="{ name: 'provider-wallet' }" class="text-xs font-bold text-emerald-900 hover:underline mt-1 inline-block no-underline">Retirar →</RouterLink>
            </div>
            <div v-else class="rounded-2xl bg-grad-card-emerald border border-emerald-200 p-5 glow-emerald relative overflow-hidden">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-emerald flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">workspace_premium</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-900">Tu plan</p>
                <p class="text-xl font-black text-emerald-950 mt-2 capitalize">
                    {{ auth.planName || 'Free' }}
                </p>
                <RouterLink :to="{ name: 'provider-subscription' }" class="text-xs font-bold text-emerald-900 hover:underline mt-1 inline-block no-underline">Gestionar →</RouterLink>
            </div>
            <div v-if="escrow" class="rounded-2xl bg-grad-card-blue border border-blue-200 p-5 glow-blue relative overflow-hidden">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-brand flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">savings</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">Total ganado</p>
                <p class="text-2xl font-black text-[#003874] mt-2"><Money :amount="walletStore.wallet?.total_earned" /></p>
            </div>
            <div v-else class="rounded-2xl bg-grad-card-blue border border-blue-200 p-5 glow-blue relative overflow-hidden">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-brand flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">visibility</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">Visibilidad</p>
                <p class="text-2xl font-black text-[#003874] mt-2">{{ auth.isPro ? 'Top' : 'Estándar' }}</p>
                <p class="text-xs text-[#003874]/70 mt-1">{{ auth.isPro ? 'Apareces primero' : 'Hazte Pro para destacar' }}</p>
            </div>
            <div class="rounded-2xl bg-grad-card-amber border border-amber-200 p-5 glow-amber relative overflow-hidden">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-warm flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">notifications_active</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-900">Solicitudes nuevas</p>
                <p class="text-4xl font-black text-amber-950 mt-2">{{ pendingNew }}</p>
                <RouterLink :to="{ name: 'provider-requests' }" class="text-xs font-bold text-amber-900 hover:underline mt-1 inline-block no-underline">Ver →</RouterLink>
            </div>
            <div class="rounded-2xl bg-grad-card-violet border border-violet-200 p-5 relative overflow-hidden" style="box-shadow: 0 16px 40px -16px rgba(124, 58, 237, 0.30)">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-grad-violet flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">travel_explore</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-violet-900">Apariciones en búsqueda (30d)</p>
                <p class="text-4xl font-black text-violet-950 mt-2">{{ dashboard.search_impressions_30d || 0 }}</p>
                <p class="text-xs text-violet-900/80 mt-1">Total histórico: {{ dashboard.search_impressions_total || 0 }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-5 relative overflow-hidden">
                <div class="absolute top-3 right-3 w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-[22px]">visibility</span>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-700">Vistas a tu negocio (30d)</p>
                <p class="text-3xl font-black text-slate-900 mt-2">{{ (dashboard.profile_views_30d || 0) + (dashboard.listing_views_30d || 0) }}</p>
                <p class="text-xs text-slate-500 mt-1">
                    Perfil: {{ dashboard.profile_views_30d || 0 }} · Anuncios: {{ dashboard.listing_views_30d || 0 }}
                </p>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <header class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-slate-900">Últimas solicitudes</h2>
                <RouterLink :to="{ name: 'provider-requests' }" class="text-sm font-bold text-[#003874] hover:underline no-underline">Ver todas →</RouterLink>
            </header>
            <p v-if="requestsStore.loading" class="text-slate-500">Cargando…</p>
            <p v-else-if="!requestsStore.items.length" class="text-slate-500">Sin solicitudes aún.</p>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="r in requestsStore.items.slice(0, 5)" :key="r.id" class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-900 truncate">#{{ r.id }} · {{ r.service?.title }}</p>
                        <p class="text-xs text-slate-500">{{ r.client?.name || '—' }}</p>
                    </div>
                    <StatusPill :status="r.status" />
                </li>
            </ul>
        </section>
    </div>
</template>
