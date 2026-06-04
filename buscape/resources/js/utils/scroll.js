/**
 * Desplaza suavemente a un ancla (#id) respetando el header fijo.
 */
export function scrollToHash(hash) {
    const id = String(hash || '').replace(/^#/, '');
    if (!id || typeof document === 'undefined') return;

    const el = document.getElementById(id);
    if (!el) return;

    const top = el.getBoundingClientRect().top + window.scrollY - 88;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
}
