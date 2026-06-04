import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useUserNotificationsStore = defineStore('userNotifications', {
    state: () => ({
        unreadCount: 0,
        items: [],
        loading: false,
        pollTimer: null,
    }),
    actions: {
        async fetch({ silent = false } = {}) {
            if (!silent) this.loading = true;
            try {
                const r = await api.get('/me/notifications', { auth: true, params: { unread: 0 } });
                this.items = r.data || [];
                this.unreadCount = Number(r.unread_count) || 0;
            } catch {
                if (!silent) {
                    this.items = [];
                    this.unreadCount = 0;
                }
            } finally {
                if (!silent) this.loading = false;
            }
        },
        startPolling(intervalMs = 45000) {
            this.stopPolling();
            void this.fetch({ silent: true });
            this.pollTimer = setInterval(() => {
                void this.fetch({ silent: true });
            }, intervalMs);
        },
        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        async markAllRead() {
            try {
                const r = await api.post('/me/notifications/read-all', {}, { auth: true });
                this.unreadCount = Number(r.unread_count) || 0;
                this.items = this.items.map((n) => ({ ...n, read_at: n.read_at || new Date().toISOString() }));
            } catch {
                /* noop */
            }
        },
        async markRead(id) {
            try {
                const r = await api.post(`/me/notifications/${id}/read`, {}, { auth: true });
                this.unreadCount = Number(r.unread_count) || 0;
                const row = this.items.find((n) => Number(n.id) === Number(id));
                if (row) row.read_at = new Date().toISOString();
            } catch {
                /* noop */
            }
        },
    },
});
