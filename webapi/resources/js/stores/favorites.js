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
        isFavorite: (s) => (providerProfileId) => {
            const id = Number(providerProfileId);
            return s.ids.includes(id);
        },
    },
    actions: {
        syncIds() {
            this.ids = (this.items || [])
                .map((f) => Number(f.provider_profile_id))
                .filter((id) => Number.isFinite(id) && id > 0);
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
        async toggle(providerProfileId) {
            const id = Number(providerProfileId);
            const r = await api.post(
                '/client/favorites/toggle',
                { provider_profile_id: id },
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
