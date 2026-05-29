import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useProviderProfileStore = defineStore('providerProfile', {
    state: () => ({
        profile: null,
        loading: false,
        error: null,
        services: [],
        servicesQuota: {
            active: 0,
            max: 2,
            available: 2,
            presencia: { active: 0, max: 2, available: 2 },
            promocion: { active: 0, max: 0, available: 0 },
        },
        defaultDurationDays: 5,
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
                const r = await api.get('/provider/listings', { auth: true });
                const payload = r?.data;
                this.services = Array.isArray(payload)
                    ? payload
                    : Array.isArray(payload?.data)
                      ? payload.data
                      : [];
                if (r.quota) this.servicesQuota = r.quota;
                if (r.default_duration_days) this.defaultDurationDays = r.default_duration_days;
            } catch {
                this.services = [];
            } finally {
                this.servicesLoading = false;
            }
        },
        async createService(payload) {
            const r = await api.post('/provider/listings', payload, { auth: true });
            await this.loadServices();
            return r.data;
        },
        async updateService(id, payload) {
            const r = await api.put(`/provider/listings/${id}`, payload, { auth: true });
            this.services = this.services.map((s) => (s.id === id ? r.data : s));
            return r.data;
        },
        async toggleServiceActive(id, isActive) {
            const r = await api.patch(`/provider/listings/${id}/status`, { is_active: isActive }, { auth: true });
            await this.loadServices();
            return r.data;
        },
        async renewService(id) {
            const r = await api.post(`/provider/listings/${id}/renew`, {}, { auth: true });
            await this.loadServices();
            return r.data;
        },
        async addServiceImage(serviceId, file) {
            const { resizeImageFile } = await import('@/services/imageResize');
            const ready = await resizeImageFile(file, { maxDimension: 1600 });
            const fd = new FormData();
            fd.append('image', ready);
            const r = await api.post(`/provider/listings/${serviceId}/images`, fd, { auth: true });
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
            await api.del(`/provider/listings/${serviceId}/images/${imageId}`, { auth: true });
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
        async uploadCover(file) {
            const fd = new FormData();
            fd.append('cover', file);
            const r = await api.post('/provider/profile/cover', fd, { auth: true });
            if (this.profile) {
                this.profile = { ...this.profile, cover_url: r.data?.cover_url };
            }
            return r.data;
        },
        async removeCover() {
            await api.del('/provider/profile/cover', { auth: true });
            if (this.profile) {
                this.profile = { ...this.profile, cover_url: null };
            }
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
