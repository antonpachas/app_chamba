import { defineStore } from 'pinia';
import { api } from '@/services/api';

function extractFavoriteRows(payload) {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
}

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        items: [],
        ids: [],
        loaded: false,
        loading: false,
        error: null,
    }),
    getters: {
        isFavorite: (s) => (providerServiceId) => {
            const id = Number(providerServiceId);
            if (!Number.isFinite(id) || id <= 0) return false;
            return s.ids.includes(id);
        },
    },
    actions: {
        reset() {
            this.items = [];
            this.ids = [];
            this.loaded = false;
            this.loading = false;
            this.error = null;
        },
        normalizeFavoriteId(row) {
            if (!row || typeof row !== 'object') return null;
            const attrs = row.attributes && typeof row.attributes === 'object' ? row.attributes : null;
            const raw = row.provider_service_id
                ?? row.providerServiceId
                ?? attrs?.provider_service_id
                ?? null;
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
            this.error = null;
            try {
                const r = await api.get('/client/favorites', { auth: true });
                this.items = extractFavoriteRows(r);
                this.syncIds();
                this.loaded = true;
            } catch (e) {
                this.items = [];
                this.ids = [];
                this.loaded = false;
                this.error = e.message || 'No se pudieron cargar los favoritos.';
                throw e;
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
                { provider_service_id: id },
                { auth: true },
            );
            if (r.action === 'added' && !this.ids.includes(id)) {
                this.ids.push(id);
            } else if (r.action === 'removed') {
                this.ids = this.ids.filter((x) => x !== id);
            }
            try {
                await this.load();
            } catch {
                /* Mantener ids locales si el listado falla tras toggle exitoso. */
            }
            return r.action;
        },
    },
});
