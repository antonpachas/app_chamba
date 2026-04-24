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
        <div class="min-h-screen bg-stone-100">
            <div class="border-b border-stone-200/80 bg-white">
                <div class="max-w-6xl mx-auto px-5 sm:px-8 lg:px-10 py-4 flex items-center justify-between gap-4">
                    <button type="button" data-back class="text-sm font-semibold text-teal-800 hover:text-teal-950 hover:underline">← Volver al acceso</button>
                    <span class="text-xs font-bold uppercase tracking-widest text-stone-400">Chamba</span>
                </div>
            </div>
            <div class="max-w-6xl mx-auto px-5 sm:px-8 lg:px-10 py-10 lg:py-16">
                <div class="lg:grid lg:grid-cols-12 lg:gap-14 xl:gap-16 items-start">
                    <div class="lg:col-span-5 mb-10 lg:mb-0">
                        <h1 class="text-3xl sm:text-4xl font-black text-stone-900 tracking-tight leading-tight">Recuperar contraseña</h1>
                        <p class="mt-4 text-lg text-stone-600 leading-relaxed">Indica el correo de tu cuenta y te enviaremos un enlace para elegir una nueva contraseña.</p>
                        <ul class="mt-8 space-y-3 text-stone-600 text-[15px] leading-relaxed">
                            <li class="flex gap-3"><span class="text-teal-600 font-bold shrink-0">·</span> Revisa también la carpeta de spam.</li>
                            <li class="flex gap-3"><span class="text-teal-600 font-bold shrink-0">·</span> Por seguridad no decimos si el correo existe o no.</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <form id="forgot-form" class="bg-white rounded-2xl border border-stone-200/90 shadow-sm shadow-stone-900/5 p-8 sm:p-10 space-y-6">
                            ${state.error ? `<div class="rounded-xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Correo electrónico</label>
                                <input name="email" type="email" required autocomplete="email" placeholder="tu@correo.com"
                                    class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 text-stone-900 text-[15px] outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                            </div>
                            <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full sm:w-auto min-w-[200px] rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white font-bold py-3.5 px-8 shadow-md shadow-teal-600/20">
                                ${state.loading ? 'Enviando…' : 'Enviar enlace'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
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
        <div class="min-h-screen bg-stone-100">
            <div class="border-b border-stone-200/80 bg-white">
                <div class="max-w-6xl mx-auto px-5 sm:px-8 lg:px-10 py-4 flex items-center justify-between gap-4">
                    <button type="button" data-back class="text-sm font-semibold text-teal-800 hover:text-teal-950 hover:underline">← Volver al acceso</button>
                    <span class="text-xs font-bold uppercase tracking-widest text-stone-400">Chamba</span>
                </div>
            </div>
            <div class="max-w-6xl mx-auto px-5 sm:px-8 lg:px-10 py-10 lg:py-16">
                <div class="lg:grid lg:grid-cols-12 lg:gap-14 xl:gap-16 items-start">
                    <div class="lg:col-span-5 mb-10 lg:mb-0">
                        <h1 class="text-3xl sm:text-4xl font-black text-stone-900 tracking-tight leading-tight">Nueva contraseña</h1>
                        <p class="mt-4 text-lg text-stone-600 leading-relaxed">Define una contraseña segura para tu cuenta.</p>
                        <div class="mt-8 rounded-2xl bg-teal-50/80 border border-teal-100/80 px-5 py-4 text-sm text-stone-700">
                            <p class="font-bold text-teal-900 text-xs uppercase tracking-wide mb-1">Correo vinculado</p>
                            <p class="text-[15px] font-medium break-all">${em}</p>
                        </div>
                    </div>
                    <div class="lg:col-span-7">
                        <form id="reset-form" class="bg-white rounded-2xl border border-stone-200/90 shadow-sm shadow-stone-900/5 p-8 sm:p-10 space-y-6">
                            ${state.error ? `<div class="rounded-xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                            <div class="grid sm:grid-cols-2 gap-5 sm:gap-6">
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Nueva contraseña</label>
                                    <input name="password" type="password" required minlength="8" autocomplete="new-password"
                                        class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Confirmar</label>
                                    <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                                        class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                                </div>
                            </div>
                            <p class="text-sm text-stone-500">Mínimo 8 caracteres. Combina letras y números si puedes.</p>
                            <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full sm:w-auto min-w-[220px] rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white font-bold py-3.5 px-8 shadow-md shadow-teal-600/20">
                                ${state.loading ? 'Guardando…' : 'Guardar contraseña'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
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
        <div class="min-h-screen bg-stone-100">
            <div class="border-b border-stone-200/80 bg-white">
                <div class="max-w-6xl mx-auto px-5 sm:px-8 lg:px-10 py-4 flex items-center justify-between gap-4">
                    <button type="button" data-back class="text-sm font-semibold text-teal-800 hover:text-teal-950 hover:underline">← Volver al acceso</button>
                    <span class="text-xs font-bold uppercase tracking-widest text-stone-400">Chamba</span>
                </div>
            </div>
            <div class="max-w-6xl mx-auto px-5 sm:px-8 lg:px-10 py-10 lg:py-16">
                <div class="lg:grid lg:grid-cols-12 lg:gap-14 xl:gap-16 items-start">
                    <div class="lg:col-span-5 mb-10 lg:mb-0">
                        <h1 class="text-3xl sm:text-4xl font-black text-stone-900 tracking-tight leading-tight">Crear cuenta</h1>
                        <p class="mt-4 text-lg text-stone-600 leading-relaxed">Regístrate como cliente para contratar servicios, o como proveedor para publicar tu negocio.</p>
                        <ul class="mt-8 space-y-3 text-stone-600 text-[15px] leading-relaxed">
                            <li class="flex gap-3"><span class="text-teal-600 font-bold shrink-0">·</span> Una sola cuenta para la web y la app.</li>
                            <li class="flex gap-3"><span class="text-teal-600 font-bold shrink-0">·</span> Puedes explorar como invitado sin registrarte.</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <form id="reg-form" class="bg-white rounded-2xl border border-stone-200/90 shadow-sm shadow-stone-900/5 p-8 sm:p-10 space-y-8">
                            ${state.error ? `<div class="rounded-xl bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-3">Tipo de cuenta</p>
                                <div class="grid grid-cols-2 sm:grid-cols-2 gap-3">
                                    <label class="cursor-pointer"><input type="radio" name="role" value="cliente" checked class="peer sr-only" />
                                        <span class="block text-center rounded-xl border-2 border-stone-200 peer-checked:border-teal-600 peer-checked:bg-teal-50 py-4 font-bold text-sm sm:text-base text-stone-800 transition">Cliente</span></label>
                                    <label class="cursor-pointer"><input type="radio" name="role" value="proveedor" class="peer sr-only" />
                                        <span class="block text-center rounded-xl border-2 border-stone-200 peer-checked:border-teal-600 peer-checked:bg-teal-50 py-4 font-bold text-sm sm:text-base text-stone-800 transition">Proveedor</span></label>
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-5 sm:gap-6">
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Nombre completo</label>
                                    <input name="full_name" required class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Correo electrónico</label>
                                    <input name="email" type="email" required class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Teléfono <span class="font-normal text-stone-400">(opcional)</span></label>
                                <input name="phone" type="tel" class="w-full max-w-xl rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                            </div>
                            <div class="grid sm:grid-cols-2 gap-5 sm:gap-6">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Contraseña <span class="font-normal text-stone-400">(mín. 8)</span></label>
                                    <input name="password" type="password" required minlength="8" class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Confirmar contraseña</label>
                                    <input name="password_confirmation" type="password" required class="w-full rounded-xl border border-stone-200 bg-stone-50/40 px-4 py-3.5 outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2">
                                <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full sm:w-auto min-w-[200px] rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white font-bold py-3.5 px-8 shadow-md shadow-teal-600/20">
                                    ${state.loading ? 'Registrando…' : 'Crear mi cuenta'}
                                </button>
                                <button type="button" data-to-forgot-reg class="text-sm font-semibold text-teal-700 hover:underline text-center sm:text-left">¿Olvidaste tu contraseña?</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
            <button type="button" data-cat="" class="rounded-full border px-3.5 py-2 text-sm font-semibold ${chipClass(state.selectedCategoryId == null)}">Todas</button>
            ${state.categories
                .map(
                    (c) => `
                <button type="button" data-cat="${c.id}" class="rounded-full border px-3.5 py-2 text-sm font-semibold ${chipClass(state.selectedCategoryId === c.id)}">${escapeHtml(c.name)}</button>
            `,
                )
                .join('')}
        </div>`;

    const tabSearchDesktop = `rounded-lg px-5 py-2.5 text-sm font-bold transition ${
        state.mainTab === 'search' ? 'bg-white text-teal-800 shadow-sm' : 'text-stone-600 hover:text-stone-900'
    }`;
    const tabAccountDesktop = `rounded-lg px-5 py-2.5 text-sm font-bold transition ${
        state.mainTab === 'account' ? 'bg-white text-teal-800 shadow-sm' : 'text-stone-600 hover:text-stone-900'
    }`;

    const resultsHtml =
        !state.searched
            ? `<div class="rounded-2xl border border-dashed border-stone-200/90 bg-stone-50/60 py-16 lg:py-20 px-6 lg:px-10 text-center">
                <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto rounded-2xl bg-teal-100 flex items-center justify-center text-teal-700 text-2xl lg:text-3xl mb-5">🔍</div>
                <p class="font-black text-xl lg:text-2xl text-stone-900 tracking-tight">Busca por categoría o palabra clave</p>
                <p class="text-stone-600 mt-3 max-w-xl mx-auto text-[15px] lg:text-base leading-relaxed">Usa el panel izquierdo para filtrar. Los resultados aparecerán aquí con más espacio para revisarlos.</p>
               </div>`
            : state.loading
              ? `<div class="py-20 lg:py-28 text-center text-stone-500 font-medium text-lg">Buscando…</div>`
              : state.results.length === 0
                ? `<div class="rounded-2xl border border-stone-200 bg-white py-16 lg:py-20 px-6 text-center text-stone-600">Sin resultados. Prueba otras palabras o categoría.</div>`
                : `<ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 pb-4">
                    ${state.results
                        .map((r) => {
                            const titleS = escapeHtml(r.title || '');
                            const prov = escapeHtml(r.provider_name || 'Proveedor');
                            const loc = escapeHtml([r.district_name, r.province_name].filter(Boolean).join(' · '));
                            const cat = escapeHtml(r.category_name || '');
                            const rating =
                                r.avg_rating && String(r.avg_rating) !== '0.00'
                                    ? `<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-900 text-xs font-bold px-2.5 py-1 shrink-0">★ ${escapeHtml(String(r.avg_rating))}</span>`
                                    : '';
                            return `<li class="bg-white rounded-2xl border border-stone-200/90 shadow-sm shadow-stone-900/5 p-5 lg:p-6 h-full flex flex-col hover:border-teal-200/80 transition-colors">
                                <div class="flex gap-4">
                                    <div class="shrink-0 w-14 h-14 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-2xl">🛠</div>
                                    <div class="min-w-0 flex-1 flex flex-col">
                                        <div class="flex justify-between gap-3 items-start">
                                            <h3 class="font-extrabold text-stone-900 text-lg leading-snug">${titleS}</h3>
                                            ${rating}
                                        </div>
                                        ${cat ? `<p class="text-xs font-bold text-teal-700 mt-2 uppercase tracking-wide">${cat}</p>` : ''}
                                        <p class="text-sm font-semibold text-stone-700 mt-3">${prov}</p>
                                        <p class="text-sm text-stone-500 mt-auto pt-3 border-t border-stone-100">📍 ${loc}</p>
                                    </div>
                                </div>
                            </li>`;
                        })
                        .join('')}
                   </ul>`;

    const accountTab = state.guest
        ? `<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14 pb-28 md:pb-16">
                <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                    <div class="lg:col-span-7">
                        <h2 class="text-2xl lg:text-3xl font-black text-stone-900 tracking-tight">Modo invitado</h2>
                        <p class="text-stone-600 mt-4 text-lg leading-relaxed">Puedes buscar servicios sin crear cuenta. Para guardar favoritos, publicar como proveedor o más funciones, crea una cuenta o inicia sesión.</p>
                    </div>
                    <div class="lg:col-span-5 flex flex-col gap-3">
                        <button type="button" data-to-login class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold py-3.5 px-6 shadow-md shadow-teal-600/15">Iniciar sesión</button>
                        <button type="button" data-to-register class="w-full rounded-xl border-2 border-stone-200 hover:border-teal-300 bg-white font-bold py-3.5 text-teal-900">Crear cuenta</button>
                        <button type="button" data-exit-guest class="text-stone-500 text-sm font-semibold py-3 hover:text-stone-700">Salir del modo invitado</button>
                    </div>
                </div>
           </div>`
        : `<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14 pb-28 md:pb-16">
                <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                    <div class="lg:col-span-7 bg-gradient-to-br from-teal-100 to-emerald-50 rounded-2xl border border-teal-200/60 p-8 lg:p-10 shadow-sm">
                        <p class="text-xs font-bold text-teal-800 uppercase tracking-widest">${escapeHtml(u?.role === 'proveedor' ? 'Proveedor' : 'Cliente')}</p>
                        <h2 class="text-3xl lg:text-4xl font-black text-stone-900 mt-2 tracking-tight">${escapeHtml(u?.full_name || '')}</h2>
                        <p class="text-stone-700 text-base mt-4">${escapeHtml(u?.email || '')}</p>
                        <p class="text-sm text-stone-600 mt-6 font-medium">Estado: <span class="text-stone-900">${escapeHtml(u?.status || '')}</span></p>
                    </div>
                    <div class="lg:col-span-5 flex flex-col justify-end gap-3">
                        <button type="button" data-logout class="w-full rounded-xl bg-stone-900 hover:bg-stone-800 text-white font-bold py-3.5 px-6">Cerrar sesión</button>
                    </div>
                </div>
           </div>`;

    const searchPanel =
        state.mainTab === 'search'
            ? `<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10 pb-28 md:pb-12">
                <div class="lg:grid lg:grid-cols-12 lg:gap-10 xl:gap-12 items-start">
                    <aside class="lg:col-span-4 xl:col-span-3">
                        <div class="bg-white rounded-2xl border border-stone-200/90 shadow-sm shadow-stone-900/5 p-6 lg:p-7 space-y-6 lg:sticky lg:top-24">
                            <div>
                                <h2 class="font-black text-xl text-stone-900 tracking-tight">Buscar servicios</h2>
                                <p class="text-sm text-stone-600 mt-2 leading-relaxed">Filtra por rubro y describe lo que necesitas.</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-3">Categoría</p>
                                ${catChips}
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wide text-stone-500" for="kw">Palabras clave</label>
                                <input type="search" id="kw" value="${escapeHtml(state.keyword)}" placeholder="Ej. electricista, pintura…"
                                    class="mt-2 w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-3.5 text-[15px] outline-none focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20" />
                            </div>
                            <button type="button" id="btn-search" class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold py-3.5 shadow-md shadow-teal-600/15">Buscar</button>
                        </div>
                    </aside>
                    <section class="lg:col-span-8 xl:col-span-9 mt-8 lg:mt-0 min-h-[280px]">
                        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-5 lg:mb-6">
                            <h3 class="text-xl lg:text-2xl font-black text-stone-900 tracking-tight">Resultados</h3>
                            ${
                                state.searched && !state.loading
                                    ? `<span class="text-sm font-semibold text-stone-500">${state.results.length} encontrado(s)</span>`
                                    : ''
                            }
                        </div>
                        ${resultsHtml}
                    </section>
                </div>
            </div>`
            : accountTab;

    root.innerHTML = `
        <div class="min-h-screen flex flex-col bg-stone-100 md:pb-0 pb-24">
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-stone-200/90 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 lg:py-4 flex flex-wrap items-center gap-3 lg:gap-4">
                    <div class="flex items-center gap-3 min-w-0 flex-1 md:flex-none">
                        <div class="w-11 h-11 shrink-0 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-lg font-black">C</div>
                        <div class="min-w-0">
                            <p class="font-black text-stone-900 leading-tight truncate text-lg">${escapeHtml(title)}</p>
                            <p class="text-sm text-stone-500 font-medium truncate">${subtitle}</p>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-1 p-1 rounded-xl bg-stone-100/90 border border-stone-200/80 ml-auto">
                        <button type="button" data-tab="search" class="${tabSearchDesktop}">Buscar</button>
                        <button type="button" data-tab="account" class="${tabAccountDesktop}">Cuenta</button>
                    </div>
                    ${state.guest ? '<span class="text-xs font-bold text-amber-800 bg-amber-100 px-3 py-1.5 rounded-lg border border-amber-200/60 shrink-0">Invitado</span>' : ''}
                </div>
            </header>
            <main class="flex-1 w-full">${searchPanel}</main>
            <nav class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/98 backdrop-blur border-t border-stone-200 flex justify-around py-2.5 safe-area-pb shadow-[0_-8px_30px_rgba(0,0,0,0.06)]">
                <button type="button" data-tab="search" class="flex flex-col items-center gap-0.5 px-8 py-1.5 rounded-xl ${state.mainTab === 'search' ? 'text-teal-700 font-extrabold' : 'text-stone-500 font-semibold'}">
                    <span class="text-xl leading-none">🔍</span><span class="text-[11px] uppercase tracking-wide">Buscar</span>
                </button>
                <button type="button" data-tab="account" class="flex flex-col items-center gap-0.5 px-8 py-1.5 rounded-xl ${state.mainTab === 'account' ? 'text-teal-700 font-extrabold' : 'text-stone-500 font-semibold'}">
                    <span class="text-xl leading-none">👤</span><span class="text-[11px] uppercase tracking-wide">Cuenta</span>
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
