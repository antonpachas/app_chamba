// Genera capturas de pantalla del flujo Chamba para la documentación.
// Uso: node scripts/qa-screenshots.mjs
// Requiere que `php artisan serve` esté corriendo en localhost:8000.
// Antes de ejecutar: `php artisan chamba:seed-test-users --password=12345678`

import { chromium } from 'playwright';
import { mkdirSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = resolve(__dirname, '../../docs/screenshots');
const BASE = process.env.CHAMBA_BASE || 'http://localhost:8000/app';

if (!existsSync(outDir)) mkdirSync(outDir, { recursive: true });

const VIEWPORT = { width: 1440, height: 900 };

const accounts = {
    admin: { email: 'jesusalexander96@hotmail.com', password: '12345678' },
    provider: { email: 'proveedor@gmail.com', password: '12345678' },
    client: { email: 'usuario@gmail.com', password: '12345678' },
};

async function shot(page, name) {
    const path = resolve(outDir, `${name}.png`);
    await page.screenshot({ path, fullPage: true });
    console.log(`  → ${name}.png`);
}

async function login(page, who) {
    const acc = accounts[who];
    await page.goto(`${BASE}/acceder`, { waitUntil: 'networkidle' });
    await page.fill('input[type="email"]', acc.email);
    await page.fill('input[type="password"]', acc.password);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/acceder'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
    await page.waitForLoadState('networkidle');
}

async function logout(page) {
    try {
        await page.evaluate(() => {
            localStorage.removeItem('chamba_web_token');
            localStorage.removeItem('chamba_web_user');
        });
    } catch {}
}

async function run() {
    const browser = await chromium.launch();
    const context = await browser.newContext({ viewport: VIEWPORT, locale: 'es-PE' });
    const page = await context.newPage();

    console.log('[1/12] Home pública (sin login)');
    await page.goto(BASE, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await shot(page, '01-home-publica');

    console.log('[2/12] Pantalla de Login');
    await page.goto(`${BASE}/acceder`, { waitUntil: 'networkidle' });
    await shot(page, '02-login');

    console.log('[3/12] Búsqueda con badge Pro');
    await page.goto(`${BASE}/buscar`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await shot(page, '03-buscar-con-pro');

    // ── CLIENTE
    console.log('[4/12] Cliente: login + home');
    await login(page, 'client');
    await page.goto(BASE, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await shot(page, '04-cliente-home');

    console.log('[5/12] Cliente: vista de membresía Premium + pago pendiente');
    await page.goto(`${BASE}/cliente/membresia`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    // Si el cliente está Free, abrimos el formulario de pago para mostrarlo
    const upgradeBtn = await page.$('button:has-text("Hacerme Premium")');
    if (upgradeBtn) {
        await upgradeBtn.click();
        await page.waitForTimeout(500);
        await page.fill('input[placeholder^="Ej:"]', 'DEMO-YAPE-001');
        await page.waitForTimeout(300);
    }
    await shot(page, '05-cliente-membresia');

    // Si pudimos abrir el formulario, enviamos el pago para tener uno pendiente
    const submitBtn = await page.$('button[type="submit"]:has-text("Registrar pago")');
    if (submitBtn) {
        await submitBtn.click();
        await page.waitForTimeout(2000);
        await shot(page, '05b-cliente-pago-enviado');
    }

    console.log('[6/12] Cliente: cuenta');
    await page.goto(`${BASE}/cuenta`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await shot(page, '06-cliente-cuenta');

    await logout(page);

    // ── PROVEEDOR
    console.log('[7/12] Proveedor: login + dashboard con banner trial');
    await login(page, 'provider');
    await page.goto(`${BASE}/proveedor/panel`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await shot(page, '07-proveedor-dashboard');

    console.log('[8/12] Proveedor: membresía Pro');
    await page.goto(`${BASE}/proveedor/membresia`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await shot(page, '08-proveedor-membresia');

    console.log('[9/12] Proveedor: solicitudes recibidas');
    await page.goto(`${BASE}/proveedor/solicitudes`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await shot(page, '09-proveedor-solicitudes');

    await logout(page);

    // ── ADMIN
    console.log('[10/12] Admin: login + panel general');
    await login(page, 'admin');
    await page.goto(`${BASE}/admin`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await shot(page, '10-admin-dashboard');

    console.log('[11/14] Admin: membresías');
    await page.goto(`${BASE}/admin/membresias`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(800);
    await shot(page, '11-admin-membresias');

    console.log('[12/14] Admin: configuración (planes y precios)');
    await page.goto(`${BASE}/admin/configuracion`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    await shot(page, '12-admin-config-planes');

    console.log('[13/14] Admin: configuración general (Yape, trial, gracia)');
    const tabSettings = await page.$('button:has-text("Configuración general")');
    if (tabSettings) {
        await tabSettings.click();
        await page.waitForTimeout(800);
    }
    await shot(page, '13-admin-config-settings');

    console.log('[14/14] Admin: cuenta');
    await page.goto(`${BASE}/cuenta`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await shot(page, '14-admin-cuenta');

    await browser.close();
    console.log(`\nListo. Capturas en: ${outDir}`);
}

run().catch((e) => {
    console.error(e);
    process.exit(1);
});
