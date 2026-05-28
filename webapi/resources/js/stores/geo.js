import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useGeoStore = defineStore('geo', {
    state: () => ({
        departments: [],
        provinces: [],
        districts: [],
        selectedDepartmentId: null,
        selectedProvinceId: null,
        selectedDistrictId: null,
        ubigeoInput: '',
        userLat: null,
        userLng: null,
        mapLat: null,
        mapLng: null,
        mapLocationLoading: false,
        useGps: false,
        loading: false,
    }),
    getters: {
        mapDisplayLat(state) {
            if (state.mapLat != null) return state.mapLat;
            if (state.useGps && state.userLat != null) return state.userLat;
            return null;
        },
        mapDisplayLng(state) {
            if (state.mapLng != null) return state.mapLng;
            if (state.useGps && state.userLng != null) return state.userLng;
            return null;
        },
        hasMapLocation() {
            return this.mapDisplayLat != null && this.mapDisplayLng != null;
        },
    },
    actions: {
        async ensureDepartments() {
            if (this.departments.length) return;
            const r = await api.get('/geo/departments');
            this.departments = r.data || [];
        },
        async setDepartment(id) {
            this.selectedDepartmentId = id ? Number(id) : null;
            this.selectedProvinceId = null;
            this.selectedDistrictId = null;
            this.provinces = [];
            this.districts = [];
            if (!this.selectedDepartmentId) return;
            const r = await api.get('/geo/provinces', { params: { department_id: this.selectedDepartmentId } });
            this.provinces = r.data || [];
        },
        async setProvince(id) {
            this.selectedProvinceId = id ? Number(id) : null;
            this.selectedDistrictId = null;
            this.districts = [];
            if (!this.selectedProvinceId) return;
            const r = await api.get('/geo/districts', { params: { province_id: this.selectedProvinceId } });
            this.districts = r.data || [];
        },
        setDistrict(id) {
            this.selectedDistrictId = id ? Number(id) : null;
        },
        clearSelection() {
            this.selectedDepartmentId = null;
            this.selectedProvinceId = null;
            this.selectedDistrictId = null;
            this.provinces = [];
            this.districts = [];
        },
        async resolveUbigeo(code) {
            const c = String(code || '').replace(/\D/g, '').slice(0, 6);
            if (c.length !== 6) return false;
            this.loading = true;
            try {
                const r = await api.get('/geo/ubigeo', { params: { ubigeo: c } });
                const d = r.data;
                this.ubigeoInput = c;
                await this.setDepartment(d.department_id);
                await this.setProvince(d.province_id);
                this.setDistrict(d.district_id);
                if (d.latitude != null) {
                    this.userLat = d.latitude;
                    this.userLng = d.longitude;
                }
                return true;
            } catch {
                return false;
            } finally {
                this.loading = false;
            }
        },
        _getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Tu navegador no soporta geolocalización.'));
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                    () => reject(new Error('No se pudo obtener tu ubicación.')),
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 },
                );
            });
        },
        useMyLocation() {
            return this._getCurrentPosition().then(({ lat, lng }) => {
                this.userLat = lat;
                this.userLng = lng;
                this.mapLat = lat;
                this.mapLng = lng;
                this.useGps = true;
            });
        },
        /** Obtiene GPS para el mapa sin cambiar filtros de búsqueda. */
        async ensureMapLocation() {
            if (this.mapLat != null && this.mapLng != null) {
                return true;
            }
            if (this.useGps && this.userLat != null && this.userLng != null) {
                this.mapLat = this.userLat;
                this.mapLng = this.userLng;
                return true;
            }
            this.mapLocationLoading = true;
            try {
                const { lat, lng } = await this._getCurrentPosition();
                this.mapLat = lat;
                this.mapLng = lng;
                return true;
            } catch {
                return false;
            } finally {
                this.mapLocationLoading = false;
            }
        },
        clearGps() {
            this.useGps = false;
            this.userLat = null;
            this.userLng = null;
            this.mapLat = null;
            this.mapLng = null;
        },
    },
});
