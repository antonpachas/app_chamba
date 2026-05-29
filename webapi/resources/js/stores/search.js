import { defineStore } from 'pinia';
import { api, getStoredToken } from '@/services/api';
import { useGeoStore } from '@/stores/geo';

export const useSearchStore = defineStore('search', {
    state: () => ({
        keyword: '',
        selectedCategoryId: null,
        results: [],
        guestMeta: null,
        searchMeta: null,
        loading: false,
        searched: false,
        error: null,
        sortBy: 'recent',
        minRating: null,
        viewMode: 'list',
        page: 1,
        perPage: 24,
    }),
    actions: {
        setKeyword(v) {
            this.keyword = String(v || '');
        },
        setCategory(id) {
            this.selectedCategoryId = id ? Number(id) : null;
        },
        setSortBy(v) {
            this.sortBy = v || 'recent';
        },
        setMinRating(v) {
            this.minRating = v == null || v === '' ? null : Number(v);
        },
        setViewMode(mode) {
            this.viewMode = mode === 'map' ? 'map' : 'list';
        },
        clearGuestState() {
            this.guestMeta = null;
        },
        resolveSortParam(geo) {
            if (this.sortBy === 'nearest' && geo.useGps && geo.userLat != null && geo.userLng != null) {
                return 'nearest';
            }
            if (this.sortBy === 'rating') {
                return 'rating';
            }
            if (geo.useGps && geo.userLat != null && geo.userLng != null && this.sortBy === 'recent') {
                return 'nearest';
            }
            return this.sortBy === 'nearest' ? 'recent' : this.sortBy;
        },
        async run(page = 1) {
            const geo = useGeoStore();
            this.page = Math.max(1, Number(page) || 1);
            this.loading = true;
            this.error = null;
            this.searched = true;
            try {
                const params = {
                    page: this.page,
                    per_page: this.perPage,
                };
                if (this.selectedCategoryId != null) params.category_id = this.selectedCategoryId;
                if (geo.selectedDistrictId != null) params.district_id = geo.selectedDistrictId;
                const kw = this.keyword.trim();
                if (kw) params.keyword = kw;
                if (geo.useGps && geo.userLat != null && geo.userLng != null) {
                    params.user_lat = geo.userLat;
                    params.user_lng = geo.userLng;
                    params.radius_km = 25;
                }
                params.sort = this.resolveSortParam(geo);
                if (this.minRating != null) {
                    params.min_rating = this.minRating;
                }
                const r = await api.get('/listings/search', { params, auth: true });
                this.results = r.data || [];
                this.searchMeta = r.meta || null;
                if (r.meta?.pagination?.current_page) {
                    this.page = r.meta.pagination.current_page;
                }
                this.guestMeta =
                    !getStoredToken() && r.meta?.guest_preview ? r.meta : null;
                if (r.meta?.sort && this.sortBy === 'recent' && r.meta.sort === 'nearest') {
                    this.sortBy = 'nearest';
                }
            } catch (e) {
                this.results = [];
                this.guestMeta = null;
                this.searchMeta = null;
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
