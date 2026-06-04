<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUserNotificationsStore } from '@/stores/userNotifications';
import AppHeader from '@/components/layout/AppHeader.vue';
import AppFooter from '@/components/layout/AppFooter.vue';
import BottomNav from '@/components/layout/BottomNav.vue';
import LoginModal from '@/components/auth/LoginModal.vue';

const route = useRoute();
const auth = useAuthStore();
const notifications = useUserNotificationsStore();
const isPlain = computed(() => route.meta.layout === 'plain');

function syncNotificationPolling() {
    if (auth.isAuthenticated && (auth.isProveedor || auth.isCliente)) {
        notifications.startPolling();
    } else {
        notifications.stopPolling();
    }
}

onMounted(() => {
    syncNotificationPolling();
});

onUnmounted(() => {
    notifications.stopPolling();
});

watch(
    () => [auth.isAuthenticated, auth.isProveedor, auth.isCliente],
    () => syncNotificationPolling(),
);
</script>

<template>
    <div class="chamba-app">
        <template v-if="isPlain">
            <router-view v-slot="{ Component }">
                <transition name="page-fade" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </template>
        <template v-else>
            <div class="min-h-screen flex flex-col app-shell-bg pb-[4.75rem] md:pb-0">
                <AppHeader />
                <main class="flex-1 w-full">
                    <router-view v-slot="{ Component }">
                        <transition name="page-fade" mode="out-in">
                            <component :is="Component" :key="route.path" />
                        </transition>
                    </router-view>
                </main>
                <AppFooter />
                <BottomNav />
                <LoginModal />
            </div>
        </template>
    </div>
</template>

<style>
.page-fade-enter-active,
.page-fade-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}
.page-fade-enter-from {
    opacity: 0;
    transform: translateY(6px);
}
.page-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
@media (prefers-reduced-motion: reduce) {
    .page-fade-enter-active,
    .page-fade-leave-active {
        transition: opacity 120ms ease;
    }
    .page-fade-enter-from,
    .page-fade-leave-to {
        transform: none;
    }
}
</style>
