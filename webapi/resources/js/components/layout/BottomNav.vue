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
            { name: 'admin-users', label: 'Usuarios', icon: 'group' },
            { name: 'admin-moderation', label: 'Moderar', icon: 'gavel' },
            { name: 'account', label: 'Cuenta', icon: 'person' },
        ];
    }
    if (auth.isProveedor) {
        return [
            { name: 'provider-dashboard', label: 'Panel', icon: 'dashboard' },
            { name: 'provider-listings', label: 'Anuncios', icon: 'campaign' },
            { name: 'provider-requests', label: 'Inbox', icon: 'inbox' },
            { name: 'account', label: 'Cuenta', icon: 'person' },
        ];
    }
    if (auth.isCliente) {
        return [
            { name: 'search', label: 'Buscar', icon: 'search' },
            { name: 'client-favorites', label: 'Fav', icon: 'favorite' },
            { name: 'client-requests', label: 'Chat', icon: 'forum' },
            { name: 'account', label: 'Cuenta', icon: 'person' },
        ];
    }
    return [
        { name: 'home', label: 'Inicio', icon: 'home' },
        { name: 'search', label: 'Buscar', icon: 'search' },
        { name: 'login', label: 'Entrar', icon: 'login' },
    ];
});
</script>

<template>
    <nav
        class="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-lg border-t border-slate-200/90 flex justify-around items-stretch py-1.5 px-1 shadow-[0_-8px_32px_-8px_rgba(0,56,116,0.15)] rounded-t-2xl safe-area-pb"
    >
        <RouterLink
            v-for="item in items"
            :key="item.name"
            :to="{ name: item.name }"
            class="flex flex-1 flex-col items-center justify-center gap-0.5 py-1.5 px-1 no-underline rounded-xl mx-0.5 transition-colors min-w-0"
            :class="route.name === item.name ? 'nav-pill-active bg-chamba-50' : 'text-slate-400'"
        >
            <span class="relative">
                <span class="material-symbols-outlined text-[24px]">{{ item.icon }}</span>
                <span
                    v-if="item.name === 'provider-requests' && notifications.unreadCount > 0"
                    class="absolute -top-0.5 -right-1.5 min-w-[1rem] h-4 px-1 rounded-full bg-rose-600 text-white text-[9px] font-black flex items-center justify-center ring-2 ring-white"
                >{{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}</span>
            </span>
            <span class="text-[9px] font-bold uppercase tracking-wide truncate max-w-full">{{ item.label }}</span>
        </RouterLink>
    </nav>
</template>
