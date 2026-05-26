import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useAdminSubscriptionsStore = defineStore('adminSubscriptions', {
    state: () => ({
        payments: [],
        subscriptions: [],
        status: 'pendiente_revision',
        loading: false,
    }),
    actions: {
        async loadPayments(status = 'pendiente_revision') {
            this.loading = true;
            this.status = status;
            try {
                const r = await api.get('/admin/subscriptions/payments', { params: { status }, auth: true });
                this.payments = r.data || [];
            } finally {
                this.loading = false;
            }
        },
        async confirm(id) {
            await api.post(`/admin/subscriptions/payments/${id}/confirm`, {}, { auth: true });
            await this.loadPayments(this.status);
        },
        async reject(id, reason = null) {
            await api.post(`/admin/subscriptions/payments/${id}/reject`, { reason }, { auth: true });
            await this.loadPayments(this.status);
        },
        async loadSubscriptions(filters = {}) {
            this.loading = true;
            try {
                const r = await api.get('/admin/subscriptions', { params: filters, auth: true });
                this.subscriptions = r.data || [];
            } finally {
                this.loading = false;
            }
        },
    },
});
