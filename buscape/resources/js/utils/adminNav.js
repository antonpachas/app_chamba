/**
 * Navegación admin agrupada (desktop: dropdowns; móvil: lista plana).
 * @returns {{ groups: Array, flatLinks: Array }}
 */
export function buildAdminNav() {
    const groups = [
        {
            id: 'overview',
            label: 'Inicio',
            icon: 'dashboard',
            links: [{ name: 'admin-dashboard', label: 'Panel' }],
        },
        {
            id: 'catalog',
            label: 'Catálogo',
            icon: 'storefront',
            links: [
                { name: 'admin-moderation',         label: 'Moderación' },
                { name: 'admin-featured-listings',  label: 'Destacados' },
                { name: 'admin-geo',                label: 'Ubicación Perú' },
            ],
        },
        {
            id: 'users',
            label: 'Usuarios',
            icon: 'group',
            links: [{ name: 'admin-users', label: 'Cuentas' }],
        },
        {
            id: 'reports',
            label: 'Reportes',
            icon: 'bar_chart',
            links: [
                { name: 'admin-reports',          label: 'Búsquedas' },
                { name: 'admin-reports-users',    label: 'Usuarios' },
                { name: 'admin-reports-listings', label: 'Anuncios' },
            ],
        },
        {
            id: 'system',
            label: 'Sistema',
            icon: 'settings',
            links: [
                { name: 'admin-support',      label: 'Soporte' },
                { name: 'admin-system',       label: 'Logs' },
                { name: 'admin-settings',     label: 'Configuración' },
            ],
        },
    ];

    const flatLinks = groups.flatMap((g) => g.links);

    return { groups, flatLinks };
}

/** ¿Algún enlace del grupo coincide con la ruta actual? */
export function isAdminGroupActive(group, routeName) {
    return group.links.some((l) => l.name === routeName);
}
