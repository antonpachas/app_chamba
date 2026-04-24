import '../css/app.css';

const LS_TOKEN = 'chamba_web_token';
const LS_USER = 'chamba_web_user';
const LS_GUEST = 'chamba_web_guest';

function apiBase() {
    return window.CHAMBA_API_BASE || '';
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

function loadJson(key) {
    try {
        const v = localStorage.getItem(key);
        return v ? JSON.parse(v) : null;
    } catch {
        return null;
    }
}

const state = {
    view: 'gate',
    mainTab: 'search',
    token: localStorage.getItem(LS_TOKEN),
    user: loadJson(LS_USER),
    guest: localStorage.getItem(LS_GUEST) === '1',
    resetToken: '',
    resetEmail: '',
    flashMessage: '',
    categories: [],
    selectedCategoryId: null,
    keyword: '',
    results: [],
    searched: false,
    loading: false,
    error: null,
};

function parseResetFromUrl() {
    const qs = new URLSearchParams(window.location.search);
    const t = qs.get('token');
    const em = qs.get('email');
    if (t && em) {
        state.resetToken = t;
        state.resetEmail = em;
        state.view = 'reset';
        return true;
    }
    return false;
}

function clearResetQueryFromUrl() {
    try {
        const u = new URL(window.location.href);
        if (!u.searchParams.has('token')) return;
        u.searchParams.delete('token');
        u.searchParams.delete('email');
        const q = u.searchParams.toString();
        const path = u.pathname + (q ? `?${q}` : '') + u.hash;
        window.history.replaceState({}, '', path);
    } catch {
        /* noop */
    }
}

function persistAuth() {
    if (state.token) localStorage.setItem(LS_TOKEN, state.token);
    else localStorage.removeItem(LS_TOKEN);
    if (state.user) localStorage.setItem(LS_USER, JSON.stringify(state.user));
    else localStorage.removeItem(LS_USER);
    localStorage.setItem(LS_GUEST, state.guest ? '1' : '0');
}

async function api(method, path, { body, auth } = {}) {
    const headers = { Accept: 'application/json' };
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    if (auth && state.token) headers.Authorization = `Bearer ${state.token}`;
    const res = await fetch(`${apiBase()}${path}`, {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
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
        throw new Error(msg);
    }
    return data;
}

function setError(msg) {
    state.error = msg;
    render();
}

function clearError() {
    state.error = null;
}

function enterGuest() {
    state.token = null;
    state.user = null;
    state.guest = true;
    state.view = 'main';
    state.mainTab = 'search';
    persistAuth();
    void loadCategories();
    render();
}

function exitGuest() {
    state.guest = false;
    localStorage.setItem(LS_GUEST, '0');
    state.view = 'gate';
    render();
}

async function loadCategories() {
    try {
        const data = await api('GET', '/categories', {});
        state.categories = data.data || [];
    } catch (e) {
        state.categories = [];
        setError(e.message);
    }
}

async function runSearch() {
    state.loading = true;
    state.error = null;
    state.searched = true;
    render();
    const params = new URLSearchParams();
    if (state.selectedCategoryId != null) params.set('category_id', String(state.selectedCategoryId));
    if (state.keyword.trim()) params.set('keyword', state.keyword.trim());
    const q = params.toString();
    try {
        const data = await api('GET', `/services/search${q ? `?${q}` : ''}`, {});
        state.results = data.data || [];
    } catch (e) {
        state.results = [];
        state.error = e.message;
    } finally {
        state.loading = false;
        render();
    }
}

async function submitLogin(email, password) {
    state.loading = true;
    state.error = null;
    state.flashMessage = '';
    render();
    try {
        const data = await api('POST', '/auth/login', { body: { email, password } });
        state.token = data.token;
        state.user = data.user;
        state.guest = false;
        persistAuth();
        state.view = 'main';
        state.mainTab = 'search';
        await loadCategories();
    } catch (e) {
        state.error = e.message;
    } finally {
        state.loading = false;
        render();
    }
}

async function submitForgot(email) {
    state.loading = true;
    state.error = null;
    render();
    try {
        await api('POST', '/auth/forgot-password', { body: { email } });
        state.flashMessage =
            'Si ese correo está en Chamba, te enviamos un enlace para restablecer la contraseña. Revisa también spam.';
        state.view = 'gate';
    } catch (e) {
        state.error = e.message;
    } finally {
        state.loading = false;
        render();
    }
}

async function submitReset(email, token, password, passwordConfirmation) {
    state.loading = true;
    state.error = null;
    render();
    try {
        await api('POST', '/auth/reset-password', {
            body: {
                email,
                token,
                password,
                password_confirmation: passwordConfirmation,
            },
        });
        clearResetQueryFromUrl();
        state.resetToken = '';
        state.resetEmail = '';
        state.flashMessage = 'Contraseña actualizada. Ya puedes iniciar sesión.';
        state.view = 'gate';
    } catch (e) {
        state.error = e.message;
    } finally {
        state.loading = false;
        render();
    }
}

async function submitRegister(payload) {
    state.loading = true;
    state.error = null;
    render();
    try {
        const data = await api('POST', '/auth/register', { body: payload });
        state.token = data.token;
        state.user = data.user;
        state.guest = false;
        persistAuth();
        state.view = 'main';
        state.mainTab = 'search';
        await loadCategories();
    } catch (e) {
        state.error = e.message;
    } finally {
        state.loading = false;
        render();
    }
}

async function logout() {
    if (state.token) {
        try {
            await api('POST', '/auth/logout', { auth: true });
        } catch {
            /* ignorar */
        }
    }
    state.token = null;
    state.user = null;
    state.guest = false;
    localStorage.removeItem(LS_GUEST);
    persistAuth();
    state.view = 'gate';
    state.results = [];
    state.searched = false;
    render();
}

async function boot() {
    const fromReset = parseResetFromUrl();
    if (!fromReset && state.token) {
        try {
            const data = await api('GET', '/auth/me', { auth: true });
            state.user = data.user || data;
            localStorage.setItem(LS_USER, JSON.stringify(state.user));
            state.view = 'main';
            await loadCategories();
        } catch {
            state.token = null;
            state.user = null;
            persistAuth();
            state.view = 'gate';
        }
    } else if (!fromReset && state.guest) {
        state.view = 'main';
        await loadCategories();
    }
    render();
}

function renderGate(root) {
    root.innerHTML = `
        <div class="min-h-screen bg-gradient-to-b from-stone-100 via-stone-50 to-teal-50/30 flex flex-col items-center justify-start sm:justify-center px-4 py-8 sm:py-12">
            <div class="w-full max-w-md">
                <div class="flex items-center gap-3 mb-6 sm:mb-8">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-600 text-2xl shadow-lg shadow-teal-600/25 ring-4 ring-white">🛠</div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-teal-700">Chamba</p>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-stone-900">Servicios locales</h1>
                        <p class="text-sm text-stone-600 mt-0.5">Inicia sesión o explora sin cuenta.</p>
                    </div>
                </div>
                <div class="rounded-3xl bg-white p-6 sm:p-8 shadow-xl shadow-stone-900/5 ring-1 ring-stone-200/80">
                    ${state.flashMessage ? `<div class="mb-5 rounded-2xl bg-emerald-50 text-emerald-900 text-sm font-medium px-4 py-3 border border-emerald-100/80">${escapeHtml(state.flashMessage)}</div>` : ''}
                    ${state.error ? `<div class="mb-5 rounded-2xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100/80">${escapeHtml(state.error)}</div>` : ''}
                    <form id="gate-login-form" class="space-y-4">
                        <div>
                            <label for="gate-email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-500">Correo electrónico</label>
                            <input id="gate-email" name="email" type="email" required autocomplete="email" placeholder="tu@correo.com"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-3.5 text-stone-900 placeholder:text-stone-400 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <label for="gate-password" class="text-xs font-bold uppercase tracking-wide text-stone-500">Contraseña</label>
                                <button type="button" data-act="forgot" class="text-xs font-semibold text-teal-700 hover:text-teal-800 hover:underline">¿Olvidaste tu contraseña?</button>
                            </div>
                            <input id="gate-password" name="password" type="password" required autocomplete="current-password"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-3.5 text-stone-900 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                        </div>
                        <button type="submit" ${state.loading ? 'disabled' : ''} class="mt-2 w-full rounded-xl bg-teal-600 py-3.5 text-[15px] font-bold text-white shadow-md shadow-teal-600/25 transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60">
                            ${state.loading ? 'Entrando…' : 'Entrar'}
                        </button>
                    </form>
                    <div class="relative my-7">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-t border-stone-200"></div></div>
                        <div class="relative flex justify-center"><span class="bg-white px-3 text-xs font-semibold uppercase tracking-wide text-stone-400">Otras opciones</span></div>
                    </div>
                    <div class="space-y-3">
                        <button type="button" data-act="register" class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-stone-200 bg-white py-3.5 text-[15px] font-bold text-stone-800 transition hover:border-teal-300 hover:bg-teal-50/40">
                            <span>Crear cuenta</span>
                        </button>
                        <button type="button" data-act="guest" class="flex w-full items-center justify-center gap-2 rounded-xl border border-stone-200 py-3.5 text-sm font-semibold text-stone-600 transition hover:bg-stone-50">
                            Explorar como invitado
                        </button>
                    </div>
                    <p class="mt-6 text-center text-sm text-stone-500">
                        <a href="${escapeHtml(window.CHAMBA_HOME_URL || '/')}" class="font-medium text-teal-700 hover:underline">Volver al sitio</a>
                    </p>
                </div>
            </div>
        </div>
    `;
    root.querySelector('[data-act="forgot"]').addEventListener('click', () => {
        state.flashMessage = '';
        clearError();
        state.view = 'forgot';
        render();
    });
    root.querySelector('[data-act="register"]').addEventListener('click', () => {
        clearError();
        state.view = 'register';
        render();
    });
    root.querySelector('[data-act="guest"]').addEventListener('click', () => {
        clearError();
        enterGuest();
    });
    root.querySelector('#gate-login-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        void submitLogin(String(fd.get('email') || '').trim(), String(fd.get('password') || ''));
    });
}

function renderForgot(root) {
    root.innerHTML = `
        <div class="min-h-screen flex flex-col bg-stone-100">
            <header class="bg-gradient-to-br from-teal-600 to-teal-800 text-white px-6 pt-8 pb-12 rounded-b-[2rem]">
                <button type="button" data-back class="text-white/90 text-sm font-semibold mb-6 hover:text-white">← Volver</button>
                <h1 class="text-2xl font-extrabold">Recuperar contraseña</h1>
                <p class="text-white/85 mt-2 text-sm">Te enviaremos un enlace a tu correo.</p>
            </header>
            <form class="max-w-lg mx-auto w-full -mt-6 px-4 pb-10 flex-1 space-y-4" id="forgot-form">
                ${state.error ? `<div class="rounded-xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                <div class="bg-white rounded-2xl shadow-lg border border-stone-200/80 p-6 space-y-4">
                    <label class="block text-sm font-bold text-stone-700">Correo</label>
                    <input name="email" type="email" required autocomplete="email"
                        class="w-full rounded-xl border border-stone-200 px-4 py-3.5 text-stone-900 focus:ring-2 focus:ring-teal-500 outline-none" placeholder="tu@correo.com" />
                    <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white font-bold py-3.5">
                        ${state.loading ? 'Enviando…' : 'Enviar enlace'}
                    </button>
                </div>
            </form>
        </div>
    `;
    root.querySelector('[data-back]').addEventListener('click', () => {
        clearError();
        state.view = 'gate';
        render();
    });
    root.querySelector('#forgot-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        void submitForgot(String(fd.get('email') || '').trim());
    });
}

function renderReset(root) {
    const em = escapeHtml(state.resetEmail);
    root.innerHTML = `
        <div class="min-h-screen flex flex-col bg-stone-100">
            <header class="bg-gradient-to-br from-teal-600 to-teal-800 text-white px-6 pt-8 pb-12 rounded-b-[2rem]">
                <button type="button" data-back class="text-white/90 text-sm font-semibold mb-6 hover:text-white">← Ir a iniciar sesión</button>
                <h1 class="text-2xl font-extrabold">Nueva contraseña</h1>
                <p class="text-white/85 mt-2 text-sm">Elige una contraseña segura.</p>
            </header>
            <form class="max-w-lg mx-auto w-full -mt-6 px-4 pb-10 flex-1 space-y-4" id="reset-form">
                ${state.error ? `<div class="rounded-xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                <div class="bg-white rounded-2xl shadow-lg border border-stone-200/80 p-6 space-y-4">
                    <p class="text-sm text-stone-600">Correo: <strong>${em}</strong></p>
                    <label class="block text-sm font-bold text-stone-700">Nueva contraseña</label>
                    <input name="password" type="password" required minlength="8" autocomplete="new-password"
                        class="w-full rounded-xl border border-stone-200 px-4 py-3.5 outline-none focus:ring-2 focus:ring-teal-500" />
                    <label class="block text-sm font-bold text-stone-700">Confirmar contraseña</label>
                    <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                        class="w-full rounded-xl border border-stone-200 px-4 py-3.5 outline-none focus:ring-2 focus:ring-teal-500" />
                    <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white font-bold py-3.5">
                        ${state.loading ? 'Guardando…' : 'Guardar contraseña'}
                    </button>
                </div>
            </form>
        </div>
    `;
    root.querySelector('[data-back]').addEventListener('click', () => {
        clearResetQueryFromUrl();
        state.resetToken = '';
        state.resetEmail = '';
        clearError();
        state.view = 'gate';
        render();
    });
    root.querySelector('#reset-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        void submitReset(
            state.resetEmail,
            state.resetToken,
            String(fd.get('password') || ''),
            String(fd.get('password_confirmation') || ''),
        );
    });
}

function renderRegister(root) {
    root.innerHTML = `
        <div class="min-h-screen flex flex-col bg-stone-100">
            <header class="bg-gradient-to-br from-teal-600 to-teal-800 text-white px-6 pt-8 pb-10 rounded-b-[2rem]">
                <button type="button" data-back class="text-white/90 text-sm font-semibold mb-6 hover:text-white">← Volver</button>
                <h1 class="text-2xl font-extrabold">Crear cuenta</h1>
                <p class="text-white/85 mt-2 text-sm">Cliente o proveedor de servicios.</p>
            </header>
            <form class="max-w-lg mx-auto w-full -mt-6 px-4 pb-12 flex-1" id="reg-form">
                ${state.error ? `<div class="mb-4 rounded-xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                <div class="bg-white rounded-2xl shadow-lg border border-stone-200/80 p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer"><input type="radio" name="role" value="cliente" checked class="peer sr-only" />
                            <span class="block text-center rounded-xl border-2 border-stone-200 peer-checked:border-teal-600 peer-checked:bg-teal-50 py-3 font-bold text-sm">Cliente</span></label>
                        <label class="cursor-pointer"><input type="radio" name="role" value="proveedor" class="peer sr-only" />
                            <span class="block text-center rounded-xl border-2 border-stone-200 peer-checked:border-teal-600 peer-checked:bg-teal-50 py-3 font-bold text-sm">Proveedor</span></label>
                    </div>
                    <label class="block text-sm font-bold text-stone-700">Nombre completo</label>
                    <input name="full_name" required class="w-full rounded-xl border border-stone-200 px-4 py-3 outline-none focus:ring-2 focus:ring-teal-500" />
                    <label class="block text-sm font-bold text-stone-700">Correo</label>
                    <input name="email" type="email" required class="w-full rounded-xl border border-stone-200 px-4 py-3 outline-none focus:ring-2 focus:ring-teal-500" />
                    <label class="block text-sm font-bold text-stone-700">Teléfono (opcional)</label>
                    <input name="phone" type="tel" class="w-full rounded-xl border border-stone-200 px-4 py-3 outline-none focus:ring-2 focus:ring-teal-500" />
                    <label class="block text-sm font-bold text-stone-700">Contraseña (mín. 8)</label>
                    <input name="password" type="password" required minlength="8" class="w-full rounded-xl border border-stone-200 px-4 py-3 outline-none focus:ring-2 focus:ring-teal-500" />
                    <label class="block text-sm font-bold text-stone-700">Confirmar contraseña</label>
                    <input name="password_confirmation" type="password" required class="w-full rounded-xl border border-stone-200 px-4 py-3 outline-none focus:ring-2 focus:ring-teal-500" />
                    <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white font-bold py-3.5">
                        ${state.loading ? 'Registrando…' : 'Registrarme'}
                    </button>
                    <button type="button" data-to-forgot-reg class="w-full text-center text-sm font-semibold text-teal-700 hover:underline pt-1">¿Olvidaste tu contraseña?</button>
                </div>
            </form>
        </div>
    `;
    root.querySelector('[data-back]').addEventListener('click', () => {
        clearError();
        state.view = 'gate';
        render();
    });
    root.querySelector('[data-to-forgot-reg]').addEventListener('click', () => {
        clearError();
        state.view = 'forgot';
        render();
    });
    root.querySelector('#reg-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const phone = String(fd.get('phone') || '').trim();
        void submitRegister({
            full_name: String(fd.get('full_name') || '').trim(),
            email: String(fd.get('email') || '').trim(),
            password: String(fd.get('password') || ''),
            password_confirmation: String(fd.get('password_confirmation') || ''),
            role: String(fd.get('role') || 'cliente'),
            phone: phone || null,
        });
    });
}

function chipClass(active) {
    return active
        ? 'bg-teal-600 text-white border-teal-600'
        : 'bg-white text-stone-700 border-stone-200 hover:border-teal-300';
}

function renderMain(root) {
    const u = state.user;
    const subtitle = state.guest ? 'Modo invitado' : escapeHtml(u?.full_name || '');
    const title = state.guest ? 'Explorar' : u?.role === 'proveedor' ? 'Tu negocio' : 'Explorar';

    const catChips =
        state.categories.length === 0
            ? '<p class="text-sm text-stone-500">Cargando categorías…</p>'
            : `<div class="flex flex-wrap gap-2">
            <button type="button" data-cat="" class="rounded-full border px-3 py-1.5 text-sm font-semibold ${chipClass(state.selectedCategoryId == null)}">Todas</button>
            ${state.categories
                .map(
                    (c) => `
                <button type="button" data-cat="${c.id}" class="rounded-full border px-3 py-1.5 text-sm font-semibold ${chipClass(state.selectedCategoryId === c.id)}">${escapeHtml(c.name)}</button>
            `,
                )
                .join('')}
        </div>`;

    const resultsHtml =
        !state.searched
            ? `<div class="text-center py-14 px-4">
                <div class="w-20 h-20 mx-auto rounded-full bg-teal-100 flex items-center justify-center text-teal-700 text-3xl mb-4">🔍</div>
                <p class="font-bold text-lg text-stone-800">Busca por categoría o palabra</p>
                <p class="text-stone-600 mt-2 max-w-sm mx-auto">Te mostramos servicios y proveedores que coincidan con lo que necesitas.</p>
               </div>`
            : state.loading
              ? `<div class="py-16 text-center text-stone-600 font-medium">Buscando…</div>`
              : state.results.length === 0
                ? `<div class="text-center py-14 text-stone-600">Sin resultados. Prueba otras palabras o categoría.</div>`
                : `<ul class="space-y-3 pb-24">
                    ${state.results
                        .map((r) => {
                            const titleS = escapeHtml(r.title || '');
                            const prov = escapeHtml(r.provider_name || 'Proveedor');
                            const loc = escapeHtml([r.district_name, r.province_name].filter(Boolean).join(' · '));
                            const cat = escapeHtml(r.category_name || '');
                            const rating =
                                r.avg_rating && String(r.avg_rating) !== '0.00'
                                    ? `<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-900 text-xs font-bold px-2 py-1">★ ${escapeHtml(String(r.avg_rating))}</span>`
                                    : '';
                            return `<li class="bg-white rounded-2xl border border-stone-200 shadow-sm p-4">
                                <div class="flex gap-3">
                                    <div class="shrink-0 w-12 h-12 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl">🛠</div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex justify-between gap-2 items-start">
                                            <h3 class="font-extrabold text-stone-900 leading-tight">${titleS}</h3>
                                            ${rating}
                                        </div>
                                        ${cat ? `<p class="text-xs font-bold text-teal-700 mt-1">${cat}</p>` : ''}
                                        <p class="text-sm font-semibold text-stone-700 mt-2">${prov}</p>
                                        <p class="text-xs text-stone-500 mt-1">📍 ${loc}</p>
                                    </div>
                                </div>
                            </li>`;
                        })
                        .join('')}
                   </ul>`;

    const accountTab = state.guest
        ? `<div class="p-6 max-w-lg mx-auto space-y-4">
                <div class="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm">
                    <h2 class="text-xl font-extrabold text-stone-900">Modo invitado</h2>
                    <p class="text-stone-600 mt-3 leading-relaxed">Puedes buscar sin cuenta. Para más funciones, inicia sesión o regístrate.</p>
                </div>
                <button type="button" data-to-login class="w-full rounded-xl bg-teal-600 text-white font-bold py-3.5">Iniciar sesión</button>
                <button type="button" data-to-register class="w-full rounded-xl border-2 border-stone-200 font-bold py-3.5 text-teal-800">Crear cuenta</button>
                <button type="button" data-exit-guest class="text-stone-500 text-sm font-semibold w-full py-2">Salir del modo invitado</button>
           </div>`
        : `<div class="p-6 max-w-lg mx-auto space-y-4">
                <div class="bg-gradient-to-br from-teal-100 to-emerald-50 rounded-2xl border border-teal-200/60 p-6 shadow-sm">
                    <p class="text-xs font-bold text-teal-800 uppercase tracking-wide">${escapeHtml(u?.role === 'proveedor' ? 'Proveedor' : 'Cliente')}</p>
                    <h2 class="text-2xl font-extrabold text-stone-900 mt-1">${escapeHtml(u?.full_name || '')}</h2>
                    <p class="text-stone-600 text-sm mt-2">${escapeHtml(u?.email || '')}</p>
                    <p class="text-xs text-stone-500 mt-2">Estado: ${escapeHtml(u?.status || '')}</p>
                </div>
                <button type="button" data-logout class="w-full rounded-xl bg-stone-800 text-white font-bold py-3.5">Cerrar sesión</button>
           </div>`;

    const searchPanel =
        state.mainTab === 'search'
            ? `<div class="p-4 max-w-lg mx-auto pb-28">
                <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-5 space-y-4">
                    <h2 class="font-extrabold text-lg text-stone-900">Encuentra un servicio</h2>
                    <p class="text-sm text-stone-600">Elige rubro y palabras clave.</p>
                    <div><p class="text-xs font-bold text-stone-500 mb-2">Categoría</p>${catChips}</div>
                    <div>
                        <label class="text-xs font-bold text-stone-500">¿Qué necesitas?</label>
                        <input type="search" id="kw" value="${escapeHtml(state.keyword)}" placeholder="Ej. electricista, pintura…"
                            class="mt-1 w-full rounded-xl border border-stone-200 px-4 py-3 outline-none focus:ring-2 focus:ring-teal-500" />
                    </div>
                    <button type="button" id="btn-search" class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold py-3.5">Buscar</button>
                </div>
                <h3 class="font-extrabold text-stone-800 mt-6 mb-2 px-1">Resultados</h3>
                ${resultsHtml}
            </div>`
            : accountTab;

    root.innerHTML = `
        <div class="min-h-screen flex flex-col bg-stone-100 pb-20">
            <header class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-stone-200 px-4 py-3 flex items-center gap-3 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-lg font-bold">C</div>
                <div class="min-w-0 flex-1">
                    <p class="font-extrabold text-stone-900 leading-tight truncate">${escapeHtml(title)}</p>
                    <p class="text-xs text-stone-500 font-medium truncate">${subtitle}</p>
                </div>
                ${state.guest ? '<span class="text-xs font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded-lg">Invitado</span>' : ''}
            </header>
            <main class="flex-1">${searchPanel}</main>
            <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-stone-200 flex justify-around py-2 safe-area-pb shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
                <button type="button" data-tab="search" class="flex flex-col items-center gap-1 px-6 py-2 rounded-xl ${state.mainTab === 'search' ? 'text-teal-700 font-extrabold' : 'text-stone-500 font-semibold'}">
                    <span class="text-xl">🔍</span><span class="text-xs">Buscar</span>
                </button>
                <button type="button" data-tab="account" class="flex flex-col items-center gap-1 px-6 py-2 rounded-xl ${state.mainTab === 'account' ? 'text-teal-700 font-extrabold' : 'text-stone-500 font-semibold'}">
                    <span class="text-xl">👤</span><span class="text-xs">Cuenta</span>
                </button>
            </nav>
        </div>
    `;

    root.querySelectorAll('[data-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.mainTab = btn.getAttribute('data-tab');
            render();
        });
    });

    root.querySelectorAll('[data-cat]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const v = btn.getAttribute('data-cat');
            state.selectedCategoryId = v === '' || v == null ? null : Number(v);
            render();
        });
    });

    const kw = root.querySelector('#kw');
    if (kw) {
        kw.addEventListener('input', () => {
            state.keyword = kw.value;
        });
    }
    const bs = root.querySelector('#btn-search');
    if (bs) {
        bs.addEventListener('click', () => void runSearch());
    }

    const logoutBtn = root.querySelector('[data-logout]');
    if (logoutBtn) logoutBtn.addEventListener('click', () => void logout());

    const toL = root.querySelector('[data-to-login]');
    if (toL)
        toL.addEventListener('click', () => {
            exitGuest();
            state.view = 'gate';
            render();
        });
    const toR = root.querySelector('[data-to-register]');
    if (toR)
        toR.addEventListener('click', () => {
            exitGuest();
            state.view = 'register';
            render();
        });
    const ex = root.querySelector('[data-exit-guest]');
    if (ex)
        ex.addEventListener('click', () => {
            exitGuest();
            render();
        });
}

function render() {
    const root = document.getElementById('chamba-root');
    if (!root) return;
    if (state.view === 'gate') renderGate(root);
    else if (state.view === 'forgot') renderForgot(root);
    else if (state.view === 'reset') renderReset(root);
    else if (state.view === 'register') renderRegister(root);
    else if (state.view === 'main') renderMain(root);
}

void boot();
