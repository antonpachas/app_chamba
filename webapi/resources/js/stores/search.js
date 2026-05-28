import { defineStore } from 'pinia';
import { api, getStoredToken } from '@/services/api';
import { useGeoStore } from '@/stores/geo';

export const useSearchStore = defineStore('search', {
    state: () => ({
        keyword: '',
        selectedCategoryId: null,
        results: [],
        guestMeta: null,
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
        clearGuestState() {
            this.guestMeta = null;
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
                const r = await api.get('/listings/search', { params, auth: true });
                this.results = r.data || [];
                this.guestMeta =
                    !getStoredToken() && r.meta?.guest_preview ? r.meta : null;
            } catch (e) {
                this.results = [];
                this.guestMeta = null;
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
