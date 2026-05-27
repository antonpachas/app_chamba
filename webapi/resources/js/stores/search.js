import { defineStore } from 'pinia';
import { api } from '@/services/api';
import { useGeoStore } from '@/stores/geo';

export const useSearchStore = defineStore('search', {
    state: () => ({
        keyword: '',
        selectedCategoryId: null,
        results: [],
        loading: false,
        searched: false,
        error: null,
    }),
    actions: {
        setKeyword(v) {
            this.keyword = String(v || '');
        },
        setCategory(id) {
            this.selectedCategoryId = id ? Number(id) : null;
        },
        async run() {
            const geo = useGeoStore();
            this.loading = true;
            this.error = null;
            this.searched = true;
            try {
                const params = {};
                if (this.selectedCategoryId != null) params.category_id = this.selectedCategoryId;
                if (geo.selectedDistrictId != null) params.district_id = geo.selectedDistrictId;
                const kw = this.keyword.trim();
                if (kw) params.keyword = kw;
                if (geo.useGps && geo.userLat != null && geo.userLng != null) {
                    params.user_lat = geo.userLat;
                    params.user_lng = geo.userLng;
                    params.radius_km = 25;
                }
                const r = await api.get('/listings/search', { params });
                this.results = r.data || [];
            } catch (e) {
                this.results = [];
                this.error = e.message || 'No se pudo buscar.';
            } finally {
                this.loading = false;
            }
        },
        findById(listingId) {
            const id = Number(listingId);
            return this.results.find((row) => Number(row.service_id) === id) || null;
        },
    },
});
