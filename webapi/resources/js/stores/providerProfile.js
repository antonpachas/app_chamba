import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useProviderProfileStore = defineStore('providerProfile', {
    state: () => ({
        profile: null,
        loading: false,
        error: null,
        services: [],
        servicesLoading: false,
        dashboard: null,
    }),
    actions: {
        async loadProfile() {
            this.loading = true;
            this.error = null;
            try {
                const r = await api.get('/provider/profile', { auth: true });
                this.profile = r.data || null;
            } catch (e) {
                if (e.status !== 404) this.error = e.message;
                this.profile = null;
            } finally {
                this.loading = false;
            }
        },
        async saveProfile(payload) {
            const has = !!this.profile;
            const r = has
                ? await api.put('/provider/profile', payload, { auth: true })
                : await api.post('/provider/profile', payload, { auth: true });
            this.profile = r.data;
            return r.data;
        },
        async loadServices() {
            this.servicesLoading = true;
            try {
                const r = await api.get('/provider/services', { auth: true });
                this.services = r.data || [];
            } catch {
                this.services = [];
            } finally {
                this.servicesLoading = false;
            }
        },
        async createService(payload) {
            const r = await api.post('/provider/services', payload, { auth: true });
            this.services = [r.data, ...this.services];
            return r.data;
        },
        async updateService(id, payload) {
            const r = await api.put(`/provider/services/${id}`, payload, { auth: true });
            this.services = this.services.map((s) => (s.id === id ? r.data : s));
            return r.data;
        },
        async toggleServiceActive(id, isActive) {
            const r = await api.patch(`/provider/services/${id}/status`, { is_active: isActive }, { auth: true });
            this.services = this.services.map((s) => (s.id === id ? r.data : s));
            return r.data;
        },
        async addServiceImage(serviceId, file) {
            const { resizeImageFile } = await import('@/services/imageResize');
            const ready = await resizeImageFile(file, { maxDimension: 1600 });
            const fd = new FormData();
            fd.append('image', ready);
            const r = await api.post(`/provider/services/${serviceId}/images`, fd, { auth: true });
            this.services = this.services.map((s) => {
                if (s.id !== serviceId) return s;
                const images = [...(s.images || []), r.data];
                return {
                    ...s,
                    images,
                    cover_image_url: s.cover_image_url || r.data.url,
                };
            });
            return r.data;
        },
        async removeServiceImage(serviceId, imageId) {
            await api.del(`/provider/services/${serviceId}/images/${imageId}`, { auth: true });
            this.services = this.services.map((s) => {
                if (s.id !== serviceId) return s;
                const images = (s.images || []).filter((i) => i.id !== imageId);
                return {
                    ...s,
                    images,
                    cover_image_url: images[0]?.url || null,
                };
            });
        },
        async loadDashboard() {
            try {
                const r = await api.get('/provider/dashboard', { auth: true });
                this.dashboard = r.data || null;
            } catch {
                this.dashboard = null;
            }
        },
    },
});
