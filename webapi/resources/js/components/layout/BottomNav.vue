<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';

const auth = useAuthStore();
const route = useRoute();
const escrow = escrowEnabled();

const items = computed(() => {
    if (auth.user?.role === 'admin') {
        return [
            { name: 'admin-dashboard', label: 'Panel', icon: 'dashboard' },
            { name: 'admin-subscriptions', label: 'Membresías', icon: 'workspace_premium' },
            ...(escrow ? [
                { name: 'admin-payments', label: 'Pagos', icon: 'receipt_long' },
            ] : []),
            { name: 'account', label: 'Cuenta', icon: 'person' },
        ];
    }
    if (auth.isProveedor) {
        return [
            { name: 'provider-dashboard', label: 'Panel', icon: 'dashboard' },
            { name: 'provider-services', label: 'Servicios', icon: 'home_repair_service' },
            { name: 'provider-requests', label: 'Solicitudes', icon: 'inbox' },
            { name: 'provider-subscription', label: 'Pro', icon: 'workspace_premium' },
        ];
    }
    if (auth.isCliente) {
        return [
            { name: 'home', label: 'Inicio', icon: 'home' },
            { name: 'search', label: 'Buscar', icon: 'search' },
            { name: 'client-requests', label: 'Solicitudes', icon: 'history' },
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
            class="flex flex-col items-center px-2 py-1 no-underline"
            :class="route.name === item.name ? 'text-[#003874]' : 'text-slate-400'"
        >
            <span class="material-symbols-outlined text-[22px]">{{ item.icon }}</span>
            <span class="text-[10px] font-bold uppercase mt-0.5">{{ item.label }}</span>
        </RouterLink>
    </nav>
</template>
