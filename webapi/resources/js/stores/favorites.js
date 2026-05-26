import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({
        items: [],
        loading: false,
    }),
    actions: {
        async load() {
            this.loading = true;
            try {
                const r = await api.get('/client/favorites', { auth: true });
                this.items = r.data || [];
            } catch {
                this.items = [];
            } finally {
                this.loading = false;
            }
        },
        async toggle(providerProfileId) {
            const r = await api.post(
                '/client/favorites/toggle',
                { provider_profile_id: providerProfileId },
                { auth: true },
            );
            await this.load();
            return r.action;
        },
    },
});
