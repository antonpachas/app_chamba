<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';
import { useProviderNotificationsStore } from '@/stores/providerNotifications';

const auth = useAuthStore();
const notifications = useProviderNotificationsStore();
const route = useRoute();
const escrow = escrowEnabled();

const items = computed(() => {
    if (auth.user?.role === 'admin') {
        return [
            { name: 'admin-dashboard', label: 'Panel', icon: 'dashboard' },
            { name: 'admin-subscriptions', label: 'Membresías', icon: 'workspace_premium' },
            { name: 'admin-users', label: 'Usuarios', icon: 'group' },
            { name: 'admin-moderation', label: 'Moderar', icon: 'gavel' },
            { name: 'admin-reports', label: 'Reportes', icon: 'analytics' },
            { name: 'admin-ledger', label: 'Kardex', icon: 'account_balance' },
            { name: 'admin-platform-ads', label: 'Ads', icon: 'campaign' },
            { name: 'admin-settings', label: 'Config', icon: 'settings' },
            ...(escrow ? [
                { name: 'admin-payments', label: 'Pagos', icon: 'receipt_long' },
            ] : []),
        ];
    }
    if (auth.isProveedor) {
        return [
            { name: 'provider-dashboard', label: 'Panel', icon: 'dashboard' },
            { name: 'provider-listings', label: 'Anuncios', icon: 'campaign' },
            { name: 'provider-requests', label: 'Solicitudes', icon: 'inbox' },
            { name: 'provider-subscription', label: 'Pro', icon: 'workspace_premium' },
        ];
    }
    if (auth.isCliente) {
        return [
            { name: 'search', label: 'Buscar', icon: 'search' },
            { name: 'client-favorites', label: 'Favoritos', icon: 'favorite' },
            { name: 'client-requests', label: 'Solicitudes', icon: 'inbox' },
            { name: 'account', label: 'Cuenta', icon: 'person' },
        ];
    }
    return [
        { name: 'home', label: 'Inicio', icon: 'home' },
        { name: 'search', label: 'Buscar', icon: 'search' },
        { name: 'login', label: 'Acceder', icon: 'login' },
    ];
});
</script>

<template>
    <nav
        class="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur border-t border-slate-200 flex justify-around py-2.5 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] rounded-t-xl safe-area-pb"
    >
        <RouterLink
            v-for="item in items"
            :key="item.name"
            :to="{ name: item.name }"
            class="flex flex-col items-center px-2 py-1 no-underline relative"
            :class="route.name === item.name ? 'text-[#003874]' : 'text-slate-400'"
        >
            <span class="relative">
                <span class="material-symbols-outlined text-[22px]">{{ item.icon }}</span>
                <span
                    v-if="item.name === 'provider-requests' && notifications.unreadCount > 0"
                    class="absolute -top-1 -right-2 min-w-[1rem] h-4 px-1 rounded-full bg-rose-600 text-white text-[9px] font-black flex items-center justify-center"
                >{{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}</span>
            </span>
            <span class="text-[10px] font-bold uppercase mt-0.5">{{ item.label }}</span>
        </RouterLink>
    </nav>
</template>
