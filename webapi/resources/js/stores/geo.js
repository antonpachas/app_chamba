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
        loading: false,
    }),
    actions: {
        async ensureDepartments() {
            if (this.departments.length) return;
            try {
                const r = await api.get('/geo/departments');
                this.departments = r.data || [];
            } catch {
                this.departments = [];
            }
        },
        async setDepartment(id) {
            this.selectedDepartmentId = id ? Number(id) : null;
            this.selectedProvinceId = null;
            this.selectedDistrictId = null;
            this.provinces = [];
            this.districts = [];
            if (!this.selectedDepartmentId) return;
            try {
                const r = await api.get('/geo/provinces', {
                    params: { department_id: this.selectedDepartmentId },
                });
                this.provinces = r.data || [];
            } catch {
                this.provinces = [];
            }
        },
        async setProvince(id) {
            this.selectedProvinceId = id ? Number(id) : null;
            this.selectedDistrictId = null;
            this.districts = [];
            if (!this.selectedProvinceId) return;
            try {
                const r = await api.get('/geo/districts', {
                    params: { province_id: this.selectedProvinceId },
                });
                this.districts = r.data || [];
            } catch {
                this.districts = [];
            }
        },
        setDistrict(id) {
            this.selectedDistrictId = id ? Number(id) : null;
        },
    },
});
