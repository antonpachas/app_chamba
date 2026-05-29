/** ID de ruta para detalle de anuncio (ref opaca del API o número legacy). */
export function listingRouteParam(service) {
    if (!service) return '';
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
