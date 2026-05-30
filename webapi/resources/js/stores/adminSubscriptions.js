import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useAdminSubscriptionsStore = defineStore('adminSubscriptions', {
    state: () => ({
        payments: [],
        subscriptions: [],
        status: 'pendiente_revision',
        loading: false,
        meta: { current_page: 1, last_page: 1, per_page: 25, total: 0 },
        subsMeta: { current_page: 1, last_page: 1, per_page: 25, total: 0 },
    }),
    actions: {
        async loadPayments(status = 'pendiente_revision', page = 1) {
            this.loading = true;
            this.status = status;
            try {
                const r = await api.get('/admin/subscriptions/payments', {
                    params: { status, page, per_page: 25 },
                    auth: true,
                });
                this.payments = r.data || [];
                this.meta = r.meta || this.meta;
            } finally {
                this.loading = false;
            }
        },
        async confirm(id) {
            await api.post(`/admin/subscriptions/payments/${id}/confirm`, {}, { auth: true });
            await this.loadPayments(this.status, this.meta.current_page);
        },
        async reject(id, reason = null) {
            await api.post(`/admin/subscriptions/payments/${id}/reject`, { reason }, { auth: true });
            await this.loadPayments(this.status, this.meta.current_page);
        },
        async loadSubscriptions(filters = {}, page = 1) {
            this.loading = true;
            try {
                const r = await api.get('/admin/subscriptions', {
                    params: { ...filters, page, per_page: 25 },
                    auth: true,
                });
                this.subscriptions = r.data || [];
                this.subsMeta = r.meta || this.subsMeta;
            } finally {
                this.loading = false;
            }
        },
    },
});
