import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    // ─── Público ──────────────────────────────────────────────────────────────
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
    // Alias de compatibilidad
    {
        path: '/servicio/:id',
        redirect: (to) => ({ name: 'listing-detail', params: { id: to.params.id } }),
    },

    // ─── Auth ─────────────────────────────────────────────────────────────────
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

    // ─── Cuenta (cualquier usuario autenticado) ───────────────────────────────
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

    // ─── Cliente ──────────────────────────────────────────────────────────────
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

    // ─── Proveedor ────────────────────────────────────────────────────────────
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
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Mis anuncios' },
    },
    {
        path: '/proveedor/solicitudes',
        name: 'provider-requests',
        component: () => import('@/views/provider/RequestsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Contactos recibidos' },
    },
    {
        path: '/proveedor/sedes',
        name: 'provider-locations',
        component: () => import('@/views/provider/LocationsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'proveedor', title: 'Mis ubicaciones' },
    },

    // ─── Admin ────────────────────────────────────────────────────────────────
    {
        path: '/admin',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/DashboardView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Panel' },
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
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Destacados' },
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
        path: '/admin/categorias',
        redirect: { name: 'admin-settings' },
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
        path: '/admin/reportes/usuarios',
        name: 'admin-reports-users',
        component: () => import('@/views/admin/ReportsUsersView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Usuarios' },
    },
    {
        path: '/admin/reportes/anuncios',
        name: 'admin-reports-listings',
        component: () => import('@/views/admin/ReportsListingsView.vue'),
        meta: { layout: 'default', requiresAuth: true, role: 'admin', title: 'Admin · Anuncios' },
    },

    // ─── Legal ────────────────────────────────────────────────────────────────
    {
        path: '/terminos',
        name: 'terms',
        component: () => import('@/views/TermsView.vue'),
        meta: { layout: 'default', title: 'Términos y Condiciones' },
    },
    {
        path: '/privacidad',
        name: 'privacy',
        component: () => import('@/views/PrivacyView.vue'),
        meta: { layout: 'default', title: 'Política de Privacidad' },
    },

    // ─── 404 ──────────────────────────────────────────────────────────────────
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/NotFoundView.vue'),
        meta: { layout: 'default', title: 'No encontrado' },
    },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior(to, _from, saved) {
        if (saved) return saved;
        if (to.hash) return { el: to.hash, behavior: 'smooth', top: 88 };
        return { top: 0, left: 0, behavior: 'instant' };
    },
});

function homeRouteForRole(role) {
    if (role === 'admin')    return { name: 'admin-dashboard' };
    if (role === 'proveedor') return { name: 'provider-dashboard' };
    return { name: 'home' };
}

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.booted) {
        try { await auth.bootstrap(); } catch { /* noop */ }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { next: to.fullPath } };
    }
    if (to.meta.guestOnly && auth.isAuthenticated) {
        return homeRouteForRole(auth.user?.role);
    }
    // Redirige soporte → panel admin si el usuario es admin
    if (to.name === 'support' && auth.user?.role === 'admin') {
        return { name: 'admin-support' };
    }
    // Redirige si el rol no coincide con la ruta
    if (to.meta.role && auth.user?.role && auth.user.role !== to.meta.role && auth.user.role !== 'admin') {
        return homeRouteForRole(auth.user?.role);
    }

    return true;
});

router.afterEach((to) => {
    const base = 'BuscaPE';
    const t = to.meta?.title;
    if (typeof document !== 'undefined') {
        document.title = t ? `${t} · ${base}` : `${base} — Encuentra negocios cerca de ti`;
    }
});

export default router;
