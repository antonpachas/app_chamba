import { escrowEnabled } from '@/services/features';

/**
 * Navegación admin agrupada (desktop: dropdowns; móvil: lista plana).
 * @returns {{ groups: Array<{ id: string, label: string, icon?: string, links: Array<{ name: string, label: string, hash?: string }> }>, flatLinks: Array }}
 */
export function buildAdminNav() {
    const escrow = escrowEnabled();

    const groups = [
        {
            id: 'overview',
            label: 'Inicio',
            icon: 'dashboard',
            links: [{ name: 'admin-dashboard', label: 'Panel' }],
        },
        {
            id: 'directory',
            label: 'Directorio',
            icon: 'storefront',
            links: [
                { name: 'admin-moderation', label: 'Moderación' },
                { name: 'admin-category-suggestions', label: 'Categorías' },
                { name: 'admin-geo', label: 'Ubicación' },
                { name: 'admin-platform-ads', label: 'Publicidad' },
            ],
        },
        {
            id: 'users',
            label: 'Usuarios',
            icon: 'group',
            links: [{ name: 'admin-users', label: 'Cuentas' }],
        },
        {
            id: 'finance',
            label: 'Finanzas',
            icon: 'payments',
            links: [
                { name: 'admin-subscriptions', label: 'Membresías' },
                { name: 'admin-ledger', label: 'Kardex' },
                ...(escrow
                    ? [
                          { name: 'admin-payments', label: 'Pagos' },
                          { name: 'admin-withdrawals', label: 'Retiros' },
                      ]
                    : []),
            ],
        },
        {
            id: 'system',
            label: 'Sistema',
            icon: 'settings',
            links: [
                { name: 'admin-support', label: 'Soporte' },
                { name: 'admin-system', label: 'Logs' },
                { name: 'admin-settings', label: 'Configuración' },
                { name: 'admin-reports', label: 'Reportes' },
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
