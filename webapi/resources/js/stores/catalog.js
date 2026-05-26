import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useCatalogStore = defineStore('catalog', {
    state: () => ({
        categories: [],
        categoriesLoading: false,
        categoriesLoaded: false,
    }),
    actions: {
        async ensureCategories() {
            if (this.categoriesLoaded || this.categoriesLoading) return;
            this.categoriesLoading = true;
            try {
                const res = await api.get('/categories');
                this.categories = res.data || [];
                this.categoriesLoaded = true;
            } catch {
                this.categories = [];
            } finally {
                this.categoriesLoading = false;
            }
        },
    },
});
