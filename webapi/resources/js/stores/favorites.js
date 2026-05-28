import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        items: [],
        ids: [],
        loaded: false,
        loading: false,
    }),
    getters: {
        isFavorite: (s) => (providerServiceId) => {
            const id = Number(providerServiceId);
            if (!Number.isFinite(id) || id <= 0) return false;
            return s.ids.includes(id);
        },
    },
    actions: {
        normalizeFavoriteId(row) {
            if (!row || typeof row !== 'object') return null;
            const raw = row.provider_service_id ?? row.providerServiceId ?? null;
            const id = Number(raw);
            return Number.isFinite(id) && id > 0 ? id : null;
        },
        syncIds() {
            const unique = new Set();
            for (const item of this.items || []) {
                const id = this.normalizeFavoriteId(item);
                if (id != null) unique.add(id);
            }
            this.ids = Array.from(unique);
        },
        async ensureLoaded() {
            if (!this.loaded && !this.loading) {
                await this.load();
            }
        },
        async load() {
            this.loading = true;
            try {
                const r = await api.get('/client/favorites', { auth: true });
                this.items = r.data || [];
                this.syncIds();
                this.loaded = true;
            } catch {
                this.items = [];
                this.ids = [];
            } finally {
                this.loading = false;
            }
        },
        async toggle(providerServiceId) {
            const id = Number(providerServiceId);
            if (!Number.isFinite(id) || id <= 0) {
                throw new Error('ID de anuncio inválido para favorito.');
            }
            const r = await api.post(
                '/client/favorites/toggle',
                { provider_service_id: id, provider_profile_id: null },
                { auth: true },
            );
            if (r.action === 'added' && !this.ids.includes(id)) {
                this.ids.push(id);
            } else if (r.action === 'removed') {
                this.ids = this.ids.filter((x) => x !== id);
            }
            await this.load();
            return r.action;
        },
    },
});
