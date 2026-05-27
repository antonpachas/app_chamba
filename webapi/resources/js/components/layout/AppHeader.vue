<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';
import { asset } from '@/utils/asset';
import { useProviderNotificationsStore } from '@/stores/providerNotifications';

const auth = useAuthStore();
const notifications = useProviderNotificationsStore();
const route = useRoute();
const menuOpen = ref(false);
const escrow = escrowEnabled();

const initials = computed(() => {
    const n = auth.user?.full_name?.trim();
    if (!n) return '?';
    const parts = n.split(/\s+/).filter(Boolean);
    const a = (parts[0]?.[0] || '').toUpperCase();
    const b = (parts.length > 1 ? parts[parts.length - 1]?.[0] : parts[0]?.[1] || '').toUpperCase();
    return (a + b).slice(0, 2) || '?';
});

const navLinks = computed(() => {
    if (auth.user?.role === 'admin') {
        return [
            { name: 'admin-dashboard', label: 'Panel' },
            { name: 'admin-subscriptions', label: 'Membresías' },
            { name: 'admin-settings', label: 'Configuración' },
            ...(escrow ? [
                { name: 'admin-payments', label: 'Pagos' },
                { name: 'admin-withdrawals', label: 'Retiros' },
            ] : []),
        ];
    }
    if (auth.isProveedor) {
        return [
            { name: 'provider-dashboard', label: 'Panel' },
            { name: 'provider-listings', label: 'Mis anuncios' },
            { name: 'provider-locations', label: 'Mis sedes' },
            { name: 'provider-requests', label: 'Solicitudes' },
            { name: 'provider-subscription', label: 'Mi plan' },
            ...(escrow ? [{ name: 'provider-wallet', label: 'Ingresos' }] : []),
        ];
    }
    if (auth.isCliente) {
        return [
            { name: 'search', label: 'Buscar anuncios' },
            { name: 'client-requests', label: 'Mis solicitudes' },
            { name: 'client-history', label: 'Historial' },
            { name: 'client-favorites', label: 'Favoritos' },
        ];
    }
    return [
        { name: 'search', label: 'Buscar anuncios' },
        { name: 'home', label: 'Cómo funciona', hash: '#como-funciona' },
    ];
});
</script>

<template>
    <header class="sticky top-0 z-30 bg-white border-b border-slate-200 shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4 justify-between">
            <div class="flex items-center gap-6 lg:gap-10 min-w-0">
                <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2 shrink-0 no-underline">
                    <img :src="asset('img/chamba-icon.png')" alt="Busca PE" class="w-10 h-10 rounded-xl shadow-md shadow-[#003874]/15 ring-1 ring-slate-200" />
                    <span class="text-2xl font-black tracking-tighter text-grad-brand">Busca PE</span>
                </RouterLink>
                <div class="hidden md:flex items-center gap-6">
                    <RouterLink
                        v-for="link in navLinks"
                        :key="link.name + (link.hash || '')"
                        :to="{ name: link.name, hash: link.hash }"
                        class="text-sm font-medium tracking-tight pb-1 border-b-2 transition no-underline relative"
                        :class="
                            route.name === link.name
                                ? 'text-[#003874] border-[#003874]'
                                : 'text-slate-500 border-transparent hover:text-[#003874]'
                        "
                    >
                        {{ link.label }}
                        <span
                            v-if="link.name === 'provider-requests' && notifications.unreadCount > 0"
                            class="absolute -top-1 -right-3 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-black flex items-center justify-center"
                        >{{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}</span>
                    </RouterLink>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                <RouterLink
                    v-if="!auth.isAuthenticated"
                    :to="{ name: 'register', query: { cuenta: 'proveedor' } }"
                    class="hidden lg:inline text-sm font-semibold text-[#003874] hover:underline whitespace-nowrap"
                >
                    Soy proveedor
                </RouterLink>
                <RouterLink
                    v-if="!auth.isAuthenticated"
                    :to="{ name: 'login' }"
                    class="rounded-full border border-[#003874]/30 px-4 py-2 text-sm font-bold text-[#003874] hover:bg-[#003874]/5 no-underline"
                >
                    Acceder
                </RouterLink>
                <div v-else class="relative">
                    <button
                        type="button"
                        @click="menuOpen = !menuOpen"
                        class="flex items-center gap-2 rounded-full px-2 py-1 hover:bg-slate-100 transition"
                    >
                        <span class="relative">
                            <img
                                v-if="auth.avatarUrl"
                                :src="auth.avatarUrl"
                                alt="Avatar"
                                class="h-9 w-9 rounded-full object-cover border border-slate-200 shadow-sm"
                            />
                            <span
                                v-else
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-[#003874] text-white font-bold text-sm border border-slate-200 shadow-sm"
                            >{{ initials }}</span>
                            <span
                                v-if="auth.isPro"
                                class="absolute -top-1 -right-1 text-[9px] font-black uppercase rounded-full px-1.5 py-0.5 text-white bg-grad-warm shadow"
                                title="Pro"
                            >PRO</span>
                        </span>
                        <span class="hidden md:flex flex-col items-start min-w-0 max-w-[180px]">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-[#003874] flex items-center gap-1.5">
                                {{ auth.roleLabel }}
                                <span v-if="auth.inTrial" class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 normal-case text-[10px]">trial · {{ auth.trialDaysLeft }}d</span>
                            </span>
                            <span class="text-sm font-semibold truncate text-slate-900 leading-tight">{{ auth.user?.full_name || '' }}</span>
                        </span>
                        <span class="material-symbols-outlined text-slate-500 hidden md:inline">expand_more</span>
                    </button>
                    <div
                        v-if="menuOpen"
                        class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden z-40"
                        @click.stop
                    >
                        <RouterLink
                            v-if="auth.user?.role === 'admin'"
                            :to="{ name: 'admin-dashboard' }"
                            class="block px-4 py-3 text-sm hover:bg-slate-50 no-underline text-slate-800"
                            @click="menuOpen = false"
                        >Panel admin</RouterLink>
                        <RouterLink
                            v-if="auth.isProveedor"
                            :to="{ name: 'provider-profile' }"
                            class="block px-4 py-3 text-sm hover:bg-slate-50 no-underline text-slate-800"
                            @click="menuOpen = false"
                        >Mi perfil de proveedor</RouterLink>
                        <RouterLink
                            v-if="auth.isProveedor"
                            :to="{ name: 'provider-subscription' }"
                            class="block px-4 py-3 text-sm hover:bg-slate-50 no-underline text-slate-800 flex items-center justify-between"
                            @click="menuOpen = false"
                        >
                            <span>Mi plan Pro</span>
                            <span v-if="!auth.isPro" class="text-[10px] font-black uppercase rounded-full px-1.5 py-0.5 text-white bg-grad-warm">Upgrade</span>
                        </RouterLink>
                        <RouterLink
                            v-if="auth.isCliente"
                            :to="{ name: 'client-subscription' }"
                            class="block px-4 py-3 text-sm hover:bg-slate-50 no-underline text-slate-800 flex items-center justify-between"
                            @click="menuOpen = false"
                        >
                            <span>Hazte Premium</span>
                            <span v-if="!auth.isPro" class="text-[10px] font-black uppercase rounded-full px-1.5 py-0.5 text-white bg-grad-warm">9/m</span>
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'account' }"
                            class="block px-4 py-3 text-sm hover:bg-slate-50 no-underline text-slate-800"
                            @click="menuOpen = false"
                        >Mi cuenta</RouterLink>
                        <button
                            type="button"
                            class="w-full text-left px-4 py-3 text-sm hover:bg-slate-50 text-red-700 font-semibold border-t border-slate-100"
                            @click="async () => { menuOpen = false; await auth.logout(); $router.push({ name: 'home' }); }"
                        >Cerrar sesión</button>
                    </div>
                </div>
            </div>
        </nav>
    </header>
</template>
