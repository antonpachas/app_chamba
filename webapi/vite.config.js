import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Deriva el base de Vite a partir del path de APP_URL del .env.
// Esto es CRÍTICO para deploys en subdirectorios como /v1/chamba/: sin esto
// los chunks lazy-loaded del router se piden como `/build/assets/...` (sin
// prefijo) y dan 404 en producción.
function deriveBase(env) {
    if (!env.APP_URL) return '/';
    try {
        const u = new URL(env.APP_URL);
        const p = u.pathname.endsWith('/') ? u.pathname : u.pathname + '/';
        return p === '//' ? '/' : p;
    } catch {
        return '/';
    }
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    return {
        base: deriveBase(env),
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/main.js'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: { base: null, includeAbsolute: false },
                },
            }),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
