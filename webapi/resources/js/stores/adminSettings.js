import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useAdminSettingsStore = defineStore('adminSettings', {
    state: () => ({
        settings: [],
        plans: [],
        settingsLogs: [],
        planLogs: {},
        loading: false,
        saving: false,
    }),
    getters: {
        settingsByGroup(state) {
            const out = {};
            for (const s of state.settings) {
                const g = s.group || 'general';
                if (!out[g]) out[g] = [];
                out[g].push(s);
            }
            return out;
        },
        planByCode: (state) => (code) => state.plans.find((p) => p.code === code) || null,
    },
    actions: {
        async loadAll() {
            this.loading = true;
            try {
                const [s, p] = await Promise.all([
                    api.get('/admin/settings', { auth: true }),
                    api.get('/admin/plans', { auth: true }),
                ]);
                this.settings = s.data || [];
                this.plans = p.data || [];
            } finally {
                this.loading = false;
            }
        },
        async loadSettingsLogs(key = null) {
            const r = await api.get('/admin/settings-logs', { params: key ? { key } : {}, auth: true });
            this.settingsLogs = r.data || [];
        },
        async loadPlanLogs(planId) {
            const r = await api.get(`/admin/plans/${planId}/logs`, { auth: true });
            this.planLogs[planId] = r.data || [];
            return this.planLogs[planId];
        },
        async saveSetting(key, value) {
            this.saving = true;
            try {
                const r = await api.put(`/admin/settings/${encodeURIComponent(key)}`, { value }, { auth: true });
                const idx = this.settings.findIndex((s) => s.key === key);
                if (idx >= 0 && r.data) {
                    this.settings[idx] = { ...this.settings[idx], ...r.data };
                }
                return r.data;
            } finally {
                this.saving = false;
            }
        },
        async savePlan(plan, payload) {
            this.saving = true;
            try {
                const r = await api.put(`/admin/plans/${plan.id}`, payload, { auth: true });
                const idx = this.plans.findIndex((p) => p.id === plan.id);
                if (idx >= 0 && r.data) {
                    this.plans[idx] = r.data;
                }
                return r.data;
            } finally {
                this.saving = false;
            }
        },
    },
});
