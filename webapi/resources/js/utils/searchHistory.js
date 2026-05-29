const KEY = 'buscape_recent_searches';
const MAX = 8;

export function loadRecentSearches() {
    try {
        const raw = localStorage.getItem(KEY);
        const list = raw ? JSON.parse(raw) : [];
        return Array.isArray(list) ? list.filter((x) => x && x.label) : [];
    } catch {
        return [];
    }
}

export function pushRecentSearch(entry) {
    if (!entry?.label) return loadRecentSearches();
    const label = String(entry.label).trim().slice(0, 80);
    if (!label) return loadRecentSearches();

    const item = {
        label,
        keyword: entry.keyword ?? '',
        category_id: entry.category_id ?? null,
        district_id: entry.district_id ?? null,
        ts: Date.now(),
    };

    const prev = loadRecentSearches().filter((x) => x.label.toLowerCase() !== label.toLowerCase());
    const next = [item, ...prev].slice(0, MAX);
    try {
        localStorage.setItem(KEY, JSON.stringify(next));
    } catch {
        /* noop */
    }
    return next;
}

export function clearRecentSearches() {
    try {
        localStorage.removeItem(KEY);
    } catch {
        /* noop */
    }
}
