<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useUserNotificationsStore } from '@/stores/userNotifications';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const notifications = useUserNotificationsStore();
const route = useRoute();

const latest = computed(() => {
    const unread = (notifications.items || []).filter((n) => !n.read_at);
    return unread.slice(0, 3);
});

const requestsRoute = computed(() =>
    auth.isProveedor ? { name: 'provider-requests' } : { name: 'client-requests' },
);

const hiddenOnRequests = computed(() =>
    route.name === 'client-requests' || route.name === 'provider-requests',
);
</script>

<template>
    <div
        v-if="notifications.unreadCount > 0 && latest.length && !hiddenOnRequests"
        class="mb-6 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3"
    >
        <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
            <p class="text-sm font-bold text-violet-950 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">notifications</span>
                Tienes {{ notifications.unreadCount }} aviso(s) nuevo(s)
            </p>
            <RouterLink
                :to="requestsRoute"
                class="text-xs font-bold text-[#003874] hover:underline no-underline"
            >
                Ver todo
            </RouterLink>
        </div>
        <ul class="space-y-2">
            <li
                v-for="n in latest"
                :key="n.id"
                class="text-sm text-violet-950/90 leading-snug"
            >
                <strong>{{ n.title }}</strong>
                <span v-if="n.body" class="block text-violet-900/80 text-xs mt-0.5">{{ n.body }}</span>
            </li>
        </ul>
    </div>
</template>
