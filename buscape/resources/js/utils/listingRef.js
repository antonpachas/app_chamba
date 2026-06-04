/** ID de ruta para detalle de anuncio: slug SEO > ref opaca > número legacy. */
export function listingRouteParam(service) {
    if (!service) return '';
    const slug = service.slug;
    if (slug && String(slug).trim()) return String(slug).trim();
    const ref = service.listing_ref;
    if (ref && String(ref).trim()) return String(ref).trim();
    const id = Number(service.service_id ?? service.id);
    return Number.isFinite(id) && id > 0 ? String(id) : '';
}

export function listingDetailTo(serviceOrId) {
    if (serviceOrId && typeof serviceOrId === 'object') {
        const p = listingRouteParam(serviceOrId);
        return p ? { name: 'listing-detail', params: { id: p } } : { name: 'home' };
    }
    const raw = String(serviceOrId ?? '').trim();
    return raw ? { name: 'listing-detail', params: { id: raw } } : { name: 'home' };
}
