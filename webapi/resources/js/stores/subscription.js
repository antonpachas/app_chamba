import { defineStore } from 'pinia';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

export const useSubscriptionStore = defineStore('subscription', {
    state: () => ({
        plans: [],
        platform: { yape: null, bank_name: null, bank_account: null, bank_holder: null },
        subscription: null,
        payments: [],
        usage: { contacts_this_month: null, free_contacts_limit: 3 },
        loading: false,
        loadingPlans: false,
    }),
    getters: {
        proPlanForRole: (s) => (role) => {
            const audience = role === 'proveedor' ? 'proveedor' : 'cliente';
            return s.plans.find((p) => p.audience === audience && (p.tier === 'pro' || p.tier === 'premium')) || null;
        },
        freePlanForRole: (s) => (role) => {
            const audience = role === 'proveedor' ? 'proveedor' : 'cliente';
            return s.plans.find((p) => p.audience === audience && p.tier === 'free') || null;
        },
        isPro: (s) => !!s.subscription?.is_pro,
        inTrial: (s) => !!s.subscription?.in_trial,
    },
    actions: {
        async loadPlans(audience = null) {
            this.loadingPlans = true;
            try {
                const r = await api.get('/subscriptions/plans', { params: audience ? { audience } : {} });
                this.plans = r.data || [];
                this.platform = {
                    yape: r.platform_yape,
                    bank_name: r.platform_bank_name,
                    bank_account: r.platform_bank_account,
                    bank_holder: r.platform_bank_holder,
                };
            } finally {
                this.loadingPlans = false;
            }
        },
        async loadMine() {
            this.loading = true;
            try {
                const r = await api.get('/subscriptions/me', { auth: true });
                this.subscription = r.subscription;
                this.payments = r.payments || [];
                this.usage = r.usage || this.usage;
            } finally {
                this.loading = false;
            }
        },
        async pay(payload) {
            const { resizeImageFile } = await import('@/services/imageResize');
            const fd = new FormData();
            for (const [k, v] of Object.entries(payload)) {
                if (v === null || v === undefined) continue;
                if (k === 'proof' && v instanceof File) {
                    const ready = await resizeImageFile(v, { maxDimension: 1600 });
                    fd.append('proof', ready);
                } else {
                    fd.append(k, String(v));
                }
            }
            await api.post('/subscriptions/pay', fd, { auth: true });
            await this.loadMine();
            const auth = useAuthStore();
            try {
                const me = await api.get('/auth/me', { auth: true });
                auth.user = me.user || auth.user;
            } catch {
                /* noop */
            }
        },
        async cancel() {
            await api.post('/subscriptions/cancel', {}, { auth: true });
            await this.loadMine();
        },
    },
});
