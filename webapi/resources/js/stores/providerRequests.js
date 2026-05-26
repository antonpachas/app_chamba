import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useProviderRequestsStore = defineStore('providerRequests', {
    state: () => ({
        items: [],
        loading: false,
        error: null,
    }),
    actions: {
        async load() {
            this.loading = true;
            this.error = null;
            try {
                const r = await api.get('/provider/service-requests', { auth: true });
                this.items = r.data || [];
            } catch (e) {
                this.error = e.message || 'No se pudo cargar.';
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        async setStatus(id, status) {
            await api.patch(`/provider/service-requests/${id}/status`, { status }, { auth: true });
            await this.load();
        },
        async sendQuote(serviceRequestId, payload) {
            await api.post(
                '/provider/quotes',
                { service_request_id: serviceRequestId, ...payload },
                { auth: true },
            );
            await this.load();
        },
    },
});
