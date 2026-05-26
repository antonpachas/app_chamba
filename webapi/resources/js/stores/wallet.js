import { defineStore } from 'pinia';
import { api } from '@/services/api';

export const useWalletStore = defineStore('wallet', {
    state: () => ({
        wallet: null,
        recentPayments: [],
        withdrawals: [],
        loading: false,
    }),
    actions: {
        async load() {
            this.loading = true;
            try {
                const r = await api.get('/provider/wallet', { auth: true });
                this.wallet = r.data?.wallet || null;
                this.recentPayments = r.data?.recent_payments || [];
                this.withdrawals = r.data?.withdrawals || [];
            } finally {
                this.loading = false;
            }
        },
        async updateBank(payload) {
            const r = await api.patch('/provider/wallet', payload, { auth: true });
            this.wallet = r.data;
        },
        async requestWithdrawal(payload) {
            await api.post('/provider/wallet/withdrawals', payload, { auth: true });
            await this.load();
        },
    },
});
