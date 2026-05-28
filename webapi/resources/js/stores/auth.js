import { defineStore } from 'pinia';
import { api, getStoredToken, setStoredToken } from '@/services/api';
import { resizeImageFile } from '@/services/imageResize';
import { useSearchStore } from '@/stores/search';

const USER_KEY = 'chamba_web_user';

function loadStoredUser() {
    try {
        const raw = localStorage.getItem(USER_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function persistUser(user) {
    try {
        if (user) localStorage.setItem(USER_KEY, JSON.stringify(user));
        else localStorage.removeItem(USER_KEY);
    } catch {
        /* noop */
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: getStoredToken(),
        user: loadStoredUser(),
        entitlements: {},
        booted: false,
        loading: false,
    }),
    getters: {
        isAuthenticated: (s) => !!s.token,
        isCliente: (s) => s.user?.role === 'cliente',
        isProveedor: (s) => s.user?.role === 'proveedor',
        isAdmin: (s) => s.user?.role === 'admin',
        subscription: (s) => s.user?.subscription || null,
        isPro: (s) => !!s.user?.subscription?.is_pro,
        inTrial: (s) => !!s.user?.subscription?.in_trial,
        planTier: (s) => s.user?.subscription?.tier || 'free',
        planName: (s) => s.user?.subscription?.plan_name || '',
        trialDaysLeft: (s) => {
            const end = s.user?.subscription?.trial_ends_at;
            if (!end) return 0;
            const ms = new Date(end).getTime() - Date.now();
            return Math.max(0, Math.ceil(ms / (1000 * 60 * 60 * 24)));
        },
        roleLabel: (s) =>
            s.user?.role === 'proveedor'
                ? 'Proveedor'
                : s.user?.role === 'cliente'
                ? 'Cliente'
                : s.user?.role === 'admin'
                ? 'Admin'
                : '',
        avatarUrl: (s) => s.user?.avatar_url || null,
        showsAds: (s) => s.entitlements?.shows_ads !== false,
    },
    actions: {
        refreshGuestBrowseAfterAuth() {
            const search = useSearchStore();
            search.clearGuestState();
            if (search.searched) {
                search.run();
            }
        },
        async bootstrap() {
            if (this.booted) return;
            if (this.token) {
                try {
                    const data = await api.get('/auth/me', { auth: true });
                    this.user = data.user || data;
                    this.entitlements = data.entitlements || {};
                    persistUser(this.user);
                } catch {
                    this.token = null;
                    this.user = null;
                    setStoredToken(null);
                    persistUser(null);
                }
            }
            this.booted = true;
        },
        async login({ email, password }) {
            this.loading = true;
            try {
                const data = await api.post('/auth/login', { email, password });
                this.token = data.token;
                this.user = data.user;
                setStoredToken(this.token);
                persistUser(this.user);
                return data.user;
            } finally {
                this.loading = false;
            }
        },
        async register(payload) {
            this.loading = true;
            try {
                const data = await api.post('/auth/register', payload);
                this.token = data.token;
                this.user = data.user;
                setStoredToken(this.token);
                persistUser(this.user);
                this.refreshGuestBrowseAfterAuth();
                return data.user;
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            if (this.token) {
                try {
                    await api.post('/auth/logout', undefined, { auth: true });
                } catch {
                    /* ignorar */
                }
            }
            this.token = null;
            this.user = null;
            setStoredToken(null);
            persistUser(null);
            useSearchStore().clearGuestState();
        },
        async forgotPassword(email) {
            return api.post('/auth/forgot-password', { email });
        },
        async resetPassword(payload) {
            return api.post('/auth/reset-password', payload);
        },
        async uploadAvatar(file) {
            const ready = await resizeImageFile(file, { maxDimension: 1024 });
            const fd = new FormData();
            fd.append('avatar', ready);
            const r = await api.post('/me/avatar', fd, { auth: true });
            if (this.user && r?.data) {
                this.user = {
                    ...this.user,
                    avatar_path: r.data.avatar_path,
                    avatar_url: r.data.avatar_url,
                };
                persistUser(this.user);
            }
            return r.data;
        },
        async removeAvatar() {
            await api.del('/me/avatar', { auth: true });
            if (this.user) {
                this.user = { ...this.user, avatar_path: null, avatar_url: null };
                persistUser(this.user);
            }
        },
        async updateProfile(payload) {
            const data = await api.put('/me', payload, { auth: true });
            if (data?.user) {
                this.user = data.user;
                persistUser(this.user);
            }
            return data;
        },
    },
});
