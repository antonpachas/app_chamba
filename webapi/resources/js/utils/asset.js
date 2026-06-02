/**
 * Helper para construir URLs relativas a la base pública del SPA.
 *
 * En desarrollo `import.meta.env.BASE_URL` será `/` y en producción
 * se inyecta el subdirectorio configurado en `vite.config.js` (que a su
 * vez se deriva de `APP_URL`). Así, en `https://jaapsystem.com/v1/chamba`
 * un `asset('img/logo.png')` resuelve a `/v1/chamba/img/logo.png`.
 *
 * NUNCA escribas rutas absolutas como `/img/...` en el SPA: en producción
 * apuntan al dominio raíz (404). Usa siempre este helper.
 */
export function asset(path) {
    const raw = String(path ?? '');
    const clean = raw.startsWith('/') ? raw.slice(1) : raw;
    const base = import.meta.env.BASE_URL || '/';
    const sep = base.endsWith('/') ? '' : '/';
    return base + sep + clean;
}

export default asset;
