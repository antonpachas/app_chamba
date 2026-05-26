import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useClientRequestsStore = defineStore('clientRequests', {
    state: () => ({
        items: [],
        loading: false,
        error: null,
        payments: [],
        platformPayoutInfo: null,
    }),
    actions: {
        async load() {
            this.loading = true;
            this.error = null;
            try {
                const r = await api.get('/client/service-requests', { auth: true });
                this.items = r.data || [];
            } catch (e) {
                this.error = e.message || 'No se pudo cargar.';
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        async loadPayments() {
            try {
                const r = await api.get('/client/payments', { auth: true });
                this.payments = r.data || [];
                this.platformPayoutInfo = r.platform_payout_info || null;
            } catch {
                this.payments = [];
            }
        },
        async decideQuote(quoteId, decision) {
            await api.patch(`/client/quotes/${quoteId}`, { decision }, { auth: true });
            await this.load();
        },
        async pay(quoteId, payload) {
            await api.post(
                '/client/payments',
                { service_quote_id: quoteId, ...payload },
                { auth: true },
            );
            await this.load();
            await this.loadPayments();
        },
        async confirmCompleted(paymentId) {
            await api.post(`/client/payments/${paymentId}/confirm-completed`, undefined, { auth: true });
            await this.load();
            await this.loadPayments();
        },
        async closeRequest(id) {
            await api.post(`/client/service-requests/${id}/close`, undefined, { auth: true });
            await this.load();
        },
    },
});
