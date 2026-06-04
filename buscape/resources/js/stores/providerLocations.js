import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useProviderLocationsStore = defineStore('providerLocations', {
    state: () => ({
        items: [],
        max: 1,
        activeCount: 0,
        loading: false,
        error: null,
    }),
    getters: {
        canAddMore: (s) => s.activeCount < s.max,
        remaining: (s) => Math.max(0, s.max - s.activeCount),
    },
    actions: {
        async load() {
            this.loading = true;
            this.error = null;
            try {
                const r = await api.get('/provider/locations', { auth: true });
                this.items = r.data || [];
                this.max = r.max_locations || 1;
                this.activeCount = r.active_count || 0;
            } catch (e) {
                this.error = e.message || 'No se pudo cargar.';
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        async create(payload) {
            const r = await api.post('/provider/locations', payload, { auth: true });
            await this.load();
            return r.data;
        },
        async update(id, payload) {
            const r = await api.put(`/provider/locations/${id}`, payload, { auth: true });
            await this.load();
            return r.data;
        },
        async remove(id) {
            await api.del(`/provider/locations/${id}`, { auth: true });
            await this.load();
        },
    },
});
