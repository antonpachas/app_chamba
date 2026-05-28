/**
 * Normaliza teléfono peruano para wa.me (solo dígitos, con código 51 si aplica).
 */
export function normalizeWhatsAppPhone(raw) {
    let d = String(raw || '').replace(/\D/g, '');
    if (!d) return '';
    if (d.length === 9 && d.startsWith('9')) {
        d = `51${d}`;
    }
    if (d.startsWith('0') && d.length === 10) {
        d = `51${d.slice(1)}`;
    }
    return d;
}

export function formatPhoneDisplay(raw) {
    const d = String(raw || '').replace(/\D/g, '');
    if (!d) return '';
    if (d.length === 9) {
        return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6)}`;
    }
    if (d.length === 11 && d.startsWith('51')) {
        return `+51 ${d.slice(2, 5)} ${d.slice(5, 8)} ${d.slice(8)}`;
    }
    return raw;
}

/**
 * URL pública del detalle de un anuncio en Busca PE.
 */
export function listingDetailUrl(serviceId) {
    const id = Number(serviceId);
    if (!Number.isFinite(id) || id <= 0) {
        return typeof window !== 'undefined' ? window.location.href : '';
    }
    if (typeof window === 'undefined') {
        return '';
    }
    try {
        const appPath = window.CHAMBA_APP_URL
            ? new URL(window.CHAMBA_APP_URL, window.location.origin).pathname.replace(/\/$/, '')
            : '/app';
        return `${window.location.origin}${appPath}/anuncio/${id}`;
    } catch {
        return `${window.location.origin}/app/anuncio/${id}`;
    }
}

export function providerProfileUrl(profileId) {
    const id = Number(profileId);
    if (!Number.isFinite(id) || id <= 0 || typeof window === 'undefined') {
        return typeof window !== 'undefined' ? window.location.href : '';
    }
    try {
        const appPath = window.CHAMBA_APP_URL
            ? new URL(window.CHAMBA_APP_URL, window.location.origin).pathname.replace(/\/$/, '')
            : '/app';
        return `${window.location.origin}${appPath}/negocio/${id}`;
    } catch {
        return `${window.location.origin}/app/negocio/${id}`;
    }
}

/**
 * Texto prellenado para WhatsApp al contactar por un anuncio.
 */
export function listingWhatsAppMessage({ title, pageUrl, providerName }) {
    const t = String(title || 'su anuncio').trim();
    const url = String(pageUrl || '').trim();
    const biz = String(providerName || '').trim();
    if (biz && url) {
        return `Hola, vi el anuncio «${t}» de ${biz} en Busca PE (${url}). Me interesa obtener más información.`;
    }
    if (url) {
        return `Hola, vi su anuncio «${t}» en Busca PE (${url}). Me interesa obtener más información.`;
    }
    return `Hola, vi su anuncio «${t}» en Busca PE. Me interesa obtener más información.`;
}

/**
 * @returns {string|null} enlace wa.me con mensaje, o null si no hay número válido
 */
export function buildListingWhatsAppUrl(service) {
    if (!service) return null;
    const phone = service.whatsapp || service.contact_phone;
    const digits = normalizeWhatsAppPhone(phone);
    if (!digits) return null;
    const pageUrl = listingDetailUrl(service.service_id);
    const text = listingWhatsAppMessage({
        title: service.title || service.service_title,
        pageUrl,
        providerName: service.provider_name || service.business_name,
    });
    return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`;
}

export function buildTelUrl(service) {
    if (!service) return null;
    const d =
        String(service.contact_phone || '').replace(/\D/g, '') ||
        String(service.whatsapp || '').replace(/\D/g, '');
    return d ? `tel:${d}` : null;
}

export function listingContactPhoneDisplay(service) {
    if (!service) return '';
    const raw = service.whatsapp || service.contact_phone;
    return formatPhoneDisplay(raw) || String(raw || '').trim();
}

export function hasListingContact(service) {
    return !!(buildListingWhatsAppUrl(service) || buildTelUrl(service));
}
