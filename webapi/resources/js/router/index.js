import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';

// Permite servir el SPA bajo cualquier subcarpeta (ej: /v1/chamba/app)
// sin necesidad de recompilar. Lee la URL inyectada por Laravel en blade.
function resolveAppBase() {
    try {
        if (typeof window !== 'undefined' && window.CHAMBA_APP_URL) {
            const u = new URL(window.CHAMBA_APP_URL, window.location.origin);
            return u.pathname || '/app';
        }
    } catch { /* noop */ }
    return '/app';
}

const routes = [
    {
        path: '/',
        name: 'home',
        alias: '/buscar',
        component: () => import('@/views/SearchView.vue'),
        meta: { layout: 'default', title: 'Explorar' },
    },
    {
        path: '/anuncio/:id',
        name: 'listing-detail',
        component: () => import('@/views/ServiceDetailView.vue'),
        props: true,
        meta: { layout: 'default', title: 'Detalle del anuncio' },
    },
    {
        path: '/negocio/:id',
        name: 'provider-public',
        component: () => import('@/views/ProviderPublicProfileView.vue'),
        props: true,
        meta: { layout: 'default', title: 'Perfil del negocio' },
    },
    {
        path: '/servicio/:id',
        redirect: (to) => ({ name: 'listing-detail', params: { id: to.params.id } }),
    },
    {
        path: '/acceder',
        name: 'login',
        component: () => import('@/views/LoginView.vue'),
        meta: { layout: 'plain', guestOnly: true, title: 'Iniciar sesión' },
    },
    {
        path: '/registro',
        name: 'register',
        component: () => import('@/views/RegisterView.vue'),
        meta: { layout: 'plain', guestOnly: true, title: 'Crear cuenta' },
    },
    {
        path: '/recuperar',
        name: 'forgot',
        component: () => import('@/views/ForgotView.vue'),
        meta: { layout: 'plain', title: 'Recuperar contraseña' },
    },
    {
        path: '/restablecer',
        name: 'reset',
        component: () => import('@/views/ResetView.vue'),
        meta: { layout: 'plain', title: 'Restablecer contraseña' },
    },
    {
        path: '/cuenta',
        name: 'account',
        component: () => import('@/views/AccountView.vue'),
        meta: { layout: 'default', requiresAuth: true, title: 'Mi cuenta' },
    },
    {
        path: '/soporte',
        name: 'support',
        component: () => import('@/views/support/SupportView.vue'),
        meta: { layout: 'default', requiresAuth: true, title: 'Soporte' },
    },
    {
        path: '/cliente/solicitudes',
        name: 'client-requests',
        component: () => import('@/views/client/RequestsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'cliente', title: 'Mis contactos' },
    },
    {
        path: '/cliente/favoritos',
        name: 'client-favorites',
        component: () => import('@/views/client/FavoritesView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'cliente', title: 'Mis favoritos' },
    },
    {
        path: '/cliente/wallet',
        name: 'client-wallet',
        component: () => import('@/views/client/WalletView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'cliente', title: 'Mis pagos', feature: 'escrow' },
    },
    {
        path: '/cliente/membresia',
        name: 'client-subscription',
        component: () => import('@/views/client/SubscriptionView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'cliente', title: 'Mi membresía' },
    },
    {
        path: '/cliente/historial',
        name: 'client-history',
        component: () => import('@/views/client/HistoryView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'cliente', title: 'Historial de pagos' },
    },
    {
        path: '/proveedor/panel',
        name: 'provider-dashboard',
        component: () => import('@/views/provider/DashboardView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Panel del negocio' },
    },
    {
        path: '/proveedor/perfil',
        name: 'provider-profile',
        component: () => import('@/views/provider/ProfileView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Mi perfil' },
    },
    {
        path: '/proveedor/anuncios',
        name: 'provider-listings',
        component: () => import('@/views/provider/ServicesView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Mis fichas' },
    },
    {
        path: '/proveedor/servicios',
        redirect: { name: 'provider-listings' },
    },
    {
        path: '/proveedor/sedes',
        redirect: { name: 'provider-listings' },
    },
    {
        path: '/proveedor/solicitudes',
        name: 'provider-requests',
        component: () => import('@/views/provider/RequestsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Contactos recibidos' },
    },
    {
        path: '/proveedor/wallet',
        name: 'provider-wallet',
        component: () => import('@/views/provider/WalletView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Mis ingresos', feature: 'escrow' },
    },
    {
        path: '/proveedor/membresia',
        name: 'provider-subscription',
        component: () => import('@/views/provider/SubscriptionView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Mi plan Pro' },
    },
    {
        path: '/admin',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/DashboardView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Panel' },
    },
    {
        path: '/admin/pagos',
        name: 'admin-payments',
        component: () => import('@/views/admin/PaymentsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Pagos' },
    },
    {
        path: '/admin/retiros',
        name: 'admin-withdrawals',
        component: () => import('@/views/admin/WithdrawalsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Retiros', feature: 'escrow' },
    },
    {
        path: '/admin/membresias',
        name: 'admin-subscriptions',
        component: () => import('@/views/admin/SubscriptionsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Membresías' },
    },
    {
        path: '/admin/moderacion',
        name: 'admin-moderation',
        component: () => import('@/views/admin/ModerationView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Moderación' },
    },
    {
        path: '/admin/destacados',
        name: 'admin-featured-listings',
        component: () => import('@/views/admin/FeaturedListingsAdminView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Destacados inicio' },
    },
    {
        path: '/admin/usuarios',
        name: 'admin-users',
        component: () => import('@/views/admin/UsersAdminView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Usuarios' },
    },
    {
        path: '/admin/sistema',
        name: 'admin-system',
        component: () => import('@/views/admin/SystemView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Sistema' },
    },
    {
        path: '/admin/soporte',
        name: 'admin-support',
        component: () => import('@/views/admin/SupportAdminView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Soporte' },
    },
    {
        path: '/admin/configuracion',
        name: 'admin-settings',
        component: () => import('@/views/admin/SettingsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Configuración' },
    },
    {
        path: '/admin/categorias-sugeridas',
        name: 'admin-category-suggestions',
        component: () => import('@/views/admin/CategorySuggestionsAdminView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Categorías' },
    },
    {
        path: '/admin/ubicacion',
        name: 'admin-geo',
        component: () => import('@/views/admin/GeoAdminView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Ubicación' },
    },
    {
        path: '/admin/reportes',
        name: 'admin-reports',
        component: () => import('@/views/admin/ReportsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Reportes' },
    },
    {
        path: '/admin/kardex',
        name: 'admin-ledger',
        component: () => import('@/views/admin/LedgerView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Kardex' },
    },
    {
        path: '/admin/publicidad',
        name: 'admin-platform-ads',
        component: () => import('@/views/admin/PlatformAdsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Publicidad' },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/NotFoundView.vue'),
        meta: { layout: 'default', title: 'No encontrado' },
    },
];

const router = createRouter({
    history: createWebHistory(resolveAppBase()),
    routes,
    scrollBehavior(to, _from, saved) {
        if (saved) return saved;
        if (to.hash) {
            return {
                el: to.hash,
                behavior: 'smooth',
                top: 88,
            };
        }
        return { top: 0, left: 0, behavior: 'instant' };
    },
});

function homeRouteForRole(role) {
    if (role === 'admin') return { name: 'admin-dashboard' };
    if (role === 'proveedor') return { name: 'provider-dashboard' };
    return { name: 'home' };
}

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (!auth.booted) {
        try {
            await auth.bootstrap();
        } catch {
            /* noop */
        }
    }
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { next: to.fullPath } };
    }
    if (to.meta.guestOnly && auth.isAuthenticated) {
        return homeRouteForRole(auth.user?.role);
    }
    if (to.name === 'support' && auth.user?.role === 'admin') {
        return { name: 'admin-support' };
    }
    if (to.name === 'support' && auth.isAuthenticated && !auth.isCliente && !auth.isProveedor) {
        return homeRouteForRole(auth.user?.role);
    }
    if (
        to.name === 'home' &&
        auth.isAuthenticated &&
        (auth.user?.role === 'admin' || auth.user?.role === 'proveedor')
    ) {
        return homeRouteForRole(auth.user?.role);
    }
    if (
        to.meta.role &&
        auth.user?.role &&
        auth.user.role !== to.meta.role &&
        auth.user.role !== 'admin'
    ) {
        return homeRouteForRole(auth.user?.role);
    }
    if (to.meta.feature === 'escrow' && !escrowEnabled()) {
        return homeRouteForRole(auth.user?.role);
    }
    return true;
});

router.afterEach((to) => {
    const base = 'Busca PE';
    const t = to.meta?.title;
    if (typeof document !== 'undefined') {
        document.title = t ? `${t} · ${base}` : `${base} — Encuentra negocios cerca de ti`;
    }
});

export default router;
