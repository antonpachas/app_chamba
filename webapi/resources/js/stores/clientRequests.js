import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useClientRequestsStore = defineStore('clientRequests', {
    state: () => ({
        items: [],
        loading: false,
        error: null,
        payments: [],
        platformPayoutInfo: null,
        proofRequired: true,
        history: [],
        historyTotals: null,
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
                this.proofRequired = r.proof_required !== false;
            } catch {
                this.payments = [];
            }
        },
        async loadHistory(type = 'all') {
            try {
                const r = await api.get('/client/history', { auth: true, params: { type } });
                this.history = r.data || [];
                this.historyTotals = r.totals || null;
            } catch {
                this.history = [];
                this.historyTotals = null;
            }
        },
        async decideQuote(quoteId, decision) {
            await api.patch(`/client/quotes/${quoteId}`, { decision }, { auth: true });
            await this.load();
        },
        /**
         * Paga una cotización. payload soporta { payment_method, payment_reference, notes, proof: File }.
         */
        async pay(quoteId, payload) {
            const { resizeImageFile } = await import('@/services/imageResize');
            const fd = new FormData();
            fd.append('service_quote_id', String(quoteId));
            if (payload?.payment_method) fd.append('payment_method', payload.payment_method);
            if (payload?.payment_reference) fd.append('payment_reference', payload.payment_reference);
            if (payload?.notes) fd.append('notes', payload.notes);
            if (payload?.proof instanceof File) {
                const ready = await resizeImageFile(payload.proof, { maxDimension: 1600 });
                fd.append('proof', ready);
            }

            await api.post('/client/payments', fd, { auth: true });
            await this.load();
            await this.loadPayments();
        },
        async confirmCompleted(paymentId) {
            await api.post(`/client/payments/${paymentId}/confirm-completed`, undefined, { auth: true });
            await this.load();
            await this.loadPayments();
        },
        async disputePayment(paymentId, reason) {
            await api.post(`/client/payments/${paymentId}/dispute`, { reason }, { auth: true });
            await this.load();
            await this.loadPayments();
        },
        async closeRequest(id) {
            await api.post(`/client/service-requests/${id}/close`, undefined, { auth: true });
            await this.load();
        },
        async submitReview(serviceRequestId, { rating, comment }) {
            await api.post(
                '/client/reviews',
                {
                    service_request_id: serviceRequestId,
                    rating,
                    comment: comment || null,
                },
                { auth: true },
            );
            await this.load();
        },
    },
});
