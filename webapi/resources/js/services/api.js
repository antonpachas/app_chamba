/**
 * Cliente HTTP único para Chamba.
 * Adjunta el token Bearer si existe; lanza Error con `message` legible.
 */
const TOKEN_KEY = 'chamba_web_token';

function apiBase() {
    if (typeof window !== 'undefined' && window.CHAMBA_API_BASE) {
        return String(window.CHAMBA_API_BASE).replace(/\/$/, '');
    }
    return '/api/v1';
}

export function getStoredToken() {
    try {
        return localStorage.getItem(TOKEN_KEY) || null;
    } catch {
        return null;
    }
}

export function setStoredToken(token) {
    try {
        if (token) localStorage.setItem(TOKEN_KEY, token);
        else localStorage.removeItem(TOKEN_KEY);
    } catch {
        /* noop */
    }
}

export async function request(method, path, { body, auth = false, params } = {}) {
    const url = new URL(apiBase() + path, window.location.origin);
    if (params && typeof params === 'object') {
        Object.entries(params).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== '') {
                url.searchParams.set(k, String(v));
            }
        });
    }
    const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
    const headers = { Accept: 'application/json' };
    if (body !== undefined && !isForm) headers['Content-Type'] = 'application/json';
    if (auth) {
        const t = getStoredToken();
        if (t) headers.Authorization = `Bearer ${t}`;
    }
    let payload;
    if (body === undefined) payload = undefined;
    else if (isForm) payload = body;
    else payload = JSON.stringify(body);
    const res = await fetch(url.toString().replace(window.location.origin, ''), {
        method,
        headers,
        body: payload,
    });
    const text = await res.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch {
        data = { message: text || 'Respuesta no válida' };
    }
    if (!res.ok) {
        const msg =
            data?.message ||
            (data?.errors && Object.values(data.errors).flat().join(' ')) ||
            `Error ${res.status}`;
        const err = new Error(msg);
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data;
}

export const api = {
    get: (p, opts) => request('GET', p, opts),
    post: (p, body, opts = {}) => request('POST', p, { ...opts, body }),
    put: (p, body, opts = {}) => request('PUT', p, { ...opts, body }),
    patch: (p, body, opts = {}) => request('PATCH', p, { ...opts, body }),
    del: (p, opts) => request('DELETE', p, opts),
};
