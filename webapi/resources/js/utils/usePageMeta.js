const DEFAULT_TITLE = 'Busca PE — Negocios cerca de ti';
const DEFAULT_DESC =
    'Directorio de negocios y profesionales en Perú. Busca por ubicación y contacta directo.';

function getOrCreate(attr, name) {
    let el = document.querySelector(`meta[${attr}="${name}"]`);
    if (!el) {
        el = document.createElement('meta');
        el.setAttribute(attr, name);
        document.head.appendChild(el);
    }
    return el;
}

function applyMeta({ title, description, image, url }) {
    const fullTitle = title ? `${title} · Busca PE` : DEFAULT_TITLE;
    const desc = description || DEFAULT_DESC;
    const ogImage = image || '';
    const canonical = url || (typeof window !== 'undefined' ? window.location.href : '');

    document.title = fullTitle;
    getOrCreate('name', 'description').content = desc;
    getOrCreate('property', 'og:title').content = fullTitle;
    getOrCreate('property', 'og:description').content = desc;
    getOrCreate('property', 'og:image').content = ogImage;
    getOrCreate('property', 'og:url').content = canonical;
    getOrCreate('name', 'twitter:title').content = fullTitle;
    getOrCreate('name', 'twitter:image').content = ogImage;
}

function resetMeta() {
    document.title = DEFAULT_TITLE;
    getOrCreate('name', 'description').content = DEFAULT_DESC;
    getOrCreate('property', 'og:title').content = DEFAULT_TITLE;
    getOrCreate('property', 'og:description').content = DEFAULT_DESC;
    getOrCreate('property', 'og:image').content = '';
    getOrCreate('property', 'og:url').content =
        typeof window !== 'undefined' ? window.location.origin : '';
    getOrCreate('name', 'twitter:title').content = DEFAULT_TITLE;
    getOrCreate('name', 'twitter:image').content = '';
}

export function usePageMeta() {
    return { setMeta: applyMeta, resetMeta };
}
