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
        async setStatus(id, status, note = null) {
            await api.patch(
                `/provider/service-requests/${id}/status`,
                note ? { status, note } : { status },
                { auth: true },
            );
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
        /** Sube hasta N fotos como evidencia de entrega. files: File[]. */
        async uploadEvidence(serviceRequestId, files, caption = null) {
            const { resizeImageFile } = await import('@/services/imageResize');
            const fd = new FormData();
            for (const f of files) {
                const ready = await resizeImageFile(f, { maxDimension: 1600 });
                fd.append('photos[]', ready);
            }
            if (caption) fd.append('caption', caption);
            const r = await api.post(
                `/provider/service-requests/${serviceRequestId}/evidence`,
                fd,
                { auth: true },
            );
            await this.load();
            return r.data || [];
        },
        async deleteEvidence(serviceRequestId, evidenceId) {
            await api.del(
                `/provider/service-requests/${serviceRequestId}/evidence/${evidenceId}`,
                { auth: true },
            );
            await this.load();
        },
        async markDelivered(serviceRequestId) {
            await api.post(
                `/provider/service-requests/${serviceRequestId}/deliver`,
                undefined,
                { auth: true },
            );
            await this.load();
        },
    },
});
