import '../css/app.css';

const LS_TOKEN = 'chamba_web_token';
const LS_USER = 'chamba_web_user';

function apiBase() {
    return window.CHAMBA_API_BASE || '';
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

function digitsOnly(s) {
    return String(s ?? '').replace(/\D/g, '');
}

function parseCoord(v) {
    if (v == null || v === '') return null;
    const n = parseFloat(String(v).replace(',', '.'));
    return Number.isFinite(n) && Math.abs(n) > 0.0001 ? n : null;
}

function serviceWaUrl(r) {
    const d = digitsOnly(r.whatsapp);
    return d ? `https://wa.me/${d}` : null;
}

function serviceTelHref(r) {
    const d = digitsOnly(r.contact_phone) || digitsOnly(r.whatsapp);
    return d ? `tel:${d}` : null;
}

function osmEmbedSrc(lat, lng) {
    const pad = 0.014;
    const minlon = lng - pad;
    const minlat = lat - pad;
    const maxlon = lng + pad;
    const maxlat = lat + pad;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(`${minlon},${minlat},${maxlon},${maxlat}`)}&layer=mapnik&marker=${encodeURIComponent(`${lat},${lng}`)}`;
}

function ratingHtmlCompact(r) {
    const n = Number(r.total_reviews) || 0;
    const avg = parseFloat(String(r.avg_rating ?? '').replace(',', '.'));
    const has = n > 0 && Number.isFinite(avg);
    if (!has) {
        return `<span class="inline-flex items-center rounded-lg bg-stone-100 text-stone-600 text-xs font-semibold px-2.5 py-1">Sin reseñas aún</span>`;
    }
    return `<span class="inline-flex items-center gap-1 rounded-lg bg-amber-100 text-amber-950 text-sm font-bold px-2.5 py-1">★ ${avg.toFixed(1)}</span><span class="text-xs text-stone-500 font-medium ml-1">${n} reseña(s)</span>`;
}

function ratingHtmlDetail(r) {
    const n = Number(r.total_reviews) || 0;
    const avg = parseFloat(String(r.avg_rating ?? '').replace(',', '.'));
    const has = n > 0 && Number.isFinite(avg);
    if (!has) {
        return `<p class="text-stone-500 text-sm">Este proveedor aún no tiene reseñas publicadas.</p>`;
    }
    return `<div class="flex flex-wrap items-baseline gap-3">
        <span class="text-4xl font-black text-amber-600 tracking-tight">${avg.toFixed(1)}</span>
        <span class="text-stone-600 font-medium">de 5 · <strong class="text-stone-900">${n}</strong> reseña(s)</span>
    </div>`;
}

function priceLineHtml(r) {
    const p = r.base_price != null && r.base_price !== '' ? escapeHtml(String(r.base_price)) : '—';
    const t = r.price_type ? escapeHtml(String(r.price_type)) : '';
    return t ? `${p} <span class="text-slate-500 text-sm font-normal">(${t})</span>` : p;
}

function landingHref() {
    return (typeof window !== 'undefined' && window.CHAMBA_LANDING_URL) || '/';
}

function categoryLandingStyle(name) {
    const n = (name || '').toLowerCase();
    if (n.includes('plomer') || n.includes('gasfit') || n.includes('gasf')) {
        return { icon: 'plumbing', box: 'bg-blue-50 text-[#003874] group-hover:bg-blue-100' };
    }
    if (n.includes('carpin') || n.includes('mader')) {
        return { icon: 'carpenter', box: 'bg-orange-50 text-[#9f4200] group-hover:bg-orange-100' };
    }
    if (n.includes('electric')) {
        return { icon: 'electrical_services', box: 'bg-yellow-50 text-yellow-800 group-hover:bg-yellow-100' };
    }
    if (n.includes('limpie')) {
        return { icon: 'cleaning_services', box: 'bg-green-50 text-emerald-800 group-hover:bg-green-100' };
    }
    if (n.includes('pintur')) {
        return { icon: 'format_paint', box: 'bg-purple-50 text-purple-800 group-hover:bg-purple-100' };
    }
    if (n.includes('jardin') || n.includes('paisaj')) {
        return { icon: 'grass', box: 'bg-emerald-50 text-emerald-800 group-hover:bg-emerald-100' };
    }
    return { icon: 'handyman', box: 'bg-slate-50 text-[#003874] group-hover:bg-slate-100' };
}

function serviceListingImageUrl(serviceId) {
    const id = Number(serviceId) || 0;
    return `https://picsum.photos/seed/chamba_svc_${id}/800/480`;
}

function cardRatingBadgeHtml(r) {
    const n = Number(r.total_reviews) || 0;
    const avg = parseFloat(String(r.avg_rating ?? '').replace(',', '.'));
    if (n <= 0 || !Number.isFinite(avg)) return '';
    return `<div class="absolute top-3 right-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm">
        <span class="material-symbols-outlined text-amber-500 text-sm" style="font-variation-settings:'FILL'1">star</span>
        <span class="text-xs font-bold text-slate-900">${avg.toFixed(1)}</span>
    </div>`;
}

function cardPriceFooterHtml(r) {
    const pt = String(r.price_type || '');
    const has = r.base_price != null && String(r.base_price).trim() !== '';
    const pNum = has ? escapeHtml(String(r.base_price).trim()) : '';
    let label = 'Precio';
    let valueHtml = '';
    if (pt === 'cotizar') {
        label = 'COTIZAR';
        valueHtml = `<span class="text-lg font-black text-[#003874]">${has ? `S/&nbsp;${pNum}` : 'Consultar'}</span>`;
    } else if (pt === 'desde') {
        label = 'DESDE';
        valueHtml = `<span class="text-lg font-black text-[#003874]">${has ? `S/&nbsp;${pNum}` : 'Consultar'}</span>`;
    } else if (pt === 'fijo') {
        label = 'PRECIO FIJO';
        valueHtml = `<span class="text-lg font-black text-[#003874]">${has ? `S/&nbsp;${pNum}` : 'Consultar'}</span>`;
    } else {
        valueHtml = `<span class="text-lg font-black text-[#003874]">${has ? `S/&nbsp;${pNum}` : 'Consultar'}</span>`;
    }
    const ctaLabel = pt === 'cotizar' ? 'Cotizar' : pt === 'fijo' ? 'Ver más' : 'Consultar';
    return `<div class="flex justify-between items-center border-t border-slate-100 pt-4">
        <div class="flex flex-col min-w-0">
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">${escapeHtml(label)}</span>
            ${valueHtml}
        </div>
        <span class="shrink-0 text-[#9f4200] font-bold text-sm border border-[#9f4200] px-4 py-2 rounded-lg">${escapeHtml(ctaLabel)}</span>
    </div>`;
}

function serviceDetailModalHtml(s) {
    const lat = parseCoord(s.provider_latitude);
    const lng = parseCoord(s.provider_longitude);
    const locLine = [s.district_name, s.province_name, s.department_name].filter(Boolean).join(' · ');
    const addr = s.address_text ? String(s.address_text).trim() : '';
    const mapSection =
        lat != null && lng != null
            ? `<div class="mt-8">
                <h4 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-3">Ubicación en mapa</h4>
                <iframe class="w-full h-56 sm:h-72 lg:h-96 rounded-2xl border border-stone-200 bg-stone-100 shadow-inner" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa del servicio" src="${osmEmbedSrc(lat, lng)}"></iframe>
                <p class="text-xs text-stone-500 mt-2 leading-relaxed">Referencia aproximada según el distrito registrado del proveedor.</p>
                <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener noreferrer" class="inline-flex mt-3 text-sm font-bold text-[#003874] hover:underline">Abrir en Google Maps →</a>
              </div>`
            : `<p class="mt-6 text-sm text-stone-500">No hay coordenadas para mostrar mapa.</p>`;
    const dist =
        s.distance_km != null && String(s.distance_km) !== ''
            ? `<p class="text-sm text-stone-600 mt-2">Distancia aprox.: <strong>${escapeHtml(String(s.distance_km))}</strong> km</p>`
            : '';

    const loggedCliente = !!(state.token && state.user && state.user.role === 'cliente');
    const loggedProveedor = !!(state.token && state.user && state.user.role === 'proveedor');
    const anon = !state.token;

    let contactSection = '';
    if (anon) {
        contactSection = `<div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-3">Solicitud y contacto</h3>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-800 font-medium">Debes iniciar sesión como <strong>cliente</strong> para enviar una solicitud a este proveedor.</p>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">Sin cuenta no puedes crear solicitudes en Chamba. El registro solo toma un minuto.</p>
                        <button type="button" data-detail-to-login class="mt-4 w-full sm:w-auto rounded-lg bg-[#003874] text-white font-semibold px-6 py-2.5 hover:bg-[#052c65] shadow-sm transition">Iniciar sesión</button>
                        <button type="button" data-detail-to-register class="mt-2 w-full sm:w-auto rounded-lg border border-[#003874]/40 bg-white text-[#003874] font-semibold px-6 py-2.5 hover:bg-slate-50 transition">Crear cuenta</button>
                    </div>
                </div>`;
    } else if (loggedProveedor) {
        const waPu = serviceWaUrl(s);
        const telPu = serviceTelHref(s);
        contactSection = `<div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-3">Contacto</h3>
                    <p class="text-sm text-slate-600 mb-3">Con tu cuenta de proveedor no puedes registrar solicitudes de cliente. Solo puedes contactar directamente:</p>
                    <div class="flex flex-wrap gap-3">
                        ${waPu ? `<a href="${waPu}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-3 text-sm shadow-md">WhatsApp</a>` : ''}
                        ${telPu ? `<a href="${telPu}" class="inline-flex items-center justify-center rounded-xl border-2 border-slate-200 hover:border-[#003874]/40 font-bold px-5 py-3 text-sm text-slate-800">Llamar</a>` : ''}
                        ${!waPu && !telPu ? '<span class="text-stone-500 text-sm">Sin datos de contacto visibles.</span>' : ''}
                    </div>
                </div>`;
    } else if (loggedCliente) {
        const waC = serviceWaUrl(s);
        const telC = serviceTelHref(s);
        contactSection = `<div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-3">Solicitud de contacto</h3>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/90 p-4 space-y-3 mb-6">
                        <label class="block text-xs font-bold text-stone-500" for="svc-req-channel">Canal preferido</label>
                        <select id="svc-req-channel" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telefono">Teléfono</option>
                            <option value="app">Por la aplicación</option>
                        </select>
                        <label class="block text-xs font-bold text-stone-500" for="svc-req-msg">Mensaje <span class="font-normal text-stone-400">(opcional)</span></label>
                        <textarea id="svc-req-msg" rows="3" maxlength="800" placeholder="Describe lo que necesitas…" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15 resize-y min-h-[4.5rem]"></textarea>
                        ${state.detailSolicitudError ? `<p class="text-sm text-red-700 font-medium">${escapeHtml(state.detailSolicitudError)}</p>` : ''}
                        ${state.detailSolicitudOk ? `<p class="text-sm text-emerald-800 font-medium">${escapeHtml(state.detailSolicitudOk)}</p>` : ''}
                        <button type="button" id="svc-req-submit" class="w-full rounded-lg bg-[#003874] hover:bg-[#052c65] text-white font-bold py-3 text-sm shadow-sm disabled:opacity-55 disabled:cursor-not-allowed">${state.detailSolicitudLoading ? 'Enviando…' : 'Enviar solicitud'}</button>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Contacto directo</p>
                    <div class="flex flex-wrap gap-3">
                        ${waC ? `<a href="${waC}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-3 text-sm shadow-md">WhatsApp</a>` : ''}
                        ${telC ? `<a href="${telC}" class="inline-flex items-center justify-center rounded-xl border-2 border-slate-200 hover:border-[#003874]/40 font-bold px-5 py-3 text-sm text-slate-800">Llamar</a>` : ''}
                        ${!waC && !telC ? '<span class="text-stone-500 text-sm">Sin datos de teléfono visibles para contacto alternativo.</span>' : ''}
                    </div>
                </div>`;
    }

    return `<div class="fixed inset-0 z-[100] flex items-end md:items-center justify-center md:p-6" role="dialog" aria-modal="true" aria-labelledby="detail-title">
        <button type="button" class="absolute inset-0 bg-stone-900/55 backdrop-blur-[2px] cursor-default border-0 w-full h-full" data-close-detail aria-label="Cerrar"></button>
        <div class="relative z-10 bg-white rounded-t-3xl md:rounded-2xl shadow-2xl w-full md:max-w-2xl lg:max-w-4xl max-h-[min(92vh,900px)] flex flex-col border border-stone-200/90 md:max-h-[88vh]">
            <div class="shrink-0 flex items-start justify-between gap-4 px-6 sm:px-8 pt-6 pb-4 border-b border-stone-100">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">${escapeHtml(s.category_name || 'Servicio')}</p>
                    <h2 id="detail-title" class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight mt-1 leading-tight">${escapeHtml(s.title || '')}</h2>
                    <p class="text-base font-semibold text-stone-700 mt-2">${escapeHtml(s.provider_name || '')}</p>
                </div>
                <button type="button" data-close-detail class="shrink-0 rounded-xl border border-slate-200 w-10 h-10 flex items-center justify-center text-slate-600 hover:bg-slate-50 font-bold text-lg leading-none" aria-label="Cerrar">×</button>
            </div>
            <div class="overflow-y-auto flex-1 px-6 sm:px-8 py-6 space-y-6">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Calificación del proveedor</h3>
                    ${ratingHtmlDetail(s)}
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Descripción</h3>
                    <p class="text-stone-800 leading-relaxed whitespace-pre-wrap">${escapeHtml(s.description || 'Sin descripción.')}</p>
                </div>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Precio</h3>
                        <p class="text-xl font-black text-stone-900">${priceLineHtml(s)}</p>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wide text-stone-500 mb-2">Zona</h3>
                        <p class="text-stone-800 font-medium">${escapeHtml(locLine || '—')}</p>
                        ${addr ? `<p class="text-sm text-stone-600 mt-2 leading-relaxed">${escapeHtml(addr)}</p>` : ''}
                        ${dist}
                    </div>
                </div>
                ${contactSection}
                ${mapSection}
            </div>
        </div>
    </div>`;
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
    /** Por defecto se muestra el buscador; `gate` solo al elegir iniciar sesión o `?cuenta=` / `?login=1`. */
    view: 'main',
    /** Tipo de cuenta esperado en la pantalla de acceso: cliente | proveedor */
    gateRole: 'cliente',
    /** Se consume en boot: llegada desde marketing con cuenta o login. */
    openGateOnBoot: false,
    mainTab: 'search',
    token: localStorage.getItem(LS_TOKEN),
    user: loadJson(LS_USER),
    resetToken: '',
    resetEmail: '',
    flashMessage: '',
    categories: [],
    selectedCategoryId: null,
    departments: [],
    provinces: [],
    districts: [],
    selectedDepartmentId: null,
    selectedProvinceId: null,
    selectedDistrictId: null,
    keyword: '',
    results: [],
    searched: false,
    loading: false,
    error: null,
    /** Fila de resultado abierta en modal de detalle (solo vista principal). */
    detailService: null,
    detailSolicitudError: '',
    detailSolicitudOk: '',
    detailSolicitudLoading: false,
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

function parseGateRoleFromUrl() {
    try {
        const qs = new URLSearchParams(window.location.search);
        const c = qs.get('cuenta');
        if (c === 'proveedor' || c === 'cliente') {
            state.gateRole = c;
            state.openGateOnBoot = true;
        }
        if (qs.get('login') === '1') state.openGateOnBoot = true;
    } catch {
        /* noop */
    }
}

/** Texto opcional desde la portada (?q=) */
function parseLandingSearchFromUrl() {
    try {
        const qs = new URLSearchParams(window.location.search);
        const q = qs.get('q');
        if (q && q.trim()) state.keyword = q.trim();
    } catch {
        /* noop */
    }
}

/** Limpia query params propios del marketing sin tocar recuperación de contraseña */
function stripAppMarketingQueryFromUrl() {
    if (state.view === 'reset') return;
    try {
        const u = new URL(window.location.href);
        if (!u.search) return;
        ['cuenta', 'login', 'q', 'department', 'province', 'district'].forEach((k) => u.searchParams.delete(k));
        const qry = u.searchParams.toString();
        const path = u.pathname + (qry ? `?${qry}` : '') + u.hash;
        window.history.replaceState({}, '', path);
    } catch {
        /* noop */
    }
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

function clearDetailSolicitudState() {
    state.detailSolicitudError = '';
    state.detailSolicitudOk = '';
    state.detailSolicitudLoading = false;
}

/** Volver al buscador desde la pantalla de acceso sin iniciar sesión. */
function closeGateToBrowse() {
    clearError();
    state.flashMessage = '';
    state.view = 'main';
    state.mainTab = 'search';
    void loadCategories();
    render();
}

function openLoginGate() {
    clearError();
    state.flashMessage = '';
    state.view = 'gate';
    render();
}

async function loadCategories() {
    try {
        const [dataCat, dataDep] = await Promise.all([
            api('GET', '/categories', {}),
            api('GET', '/geo/departments', {}),
        ]);
        state.categories = dataCat.data || [];
        state.departments = dataDep.data || [];
    } catch (e) {
        state.categories = [];
        state.departments = [];
        setError(e.message);
    }
}

async function onGeoDepartmentChange(raw) {
    const id = raw ? Number(raw) : NaN;
    state.selectedDepartmentId = Number.isFinite(id) ? id : null;
    state.selectedProvinceId = null;
    state.selectedDistrictId = null;
    state.provinces = [];
    state.districts = [];
    render();
    if (!state.selectedDepartmentId) return;
    try {
        const data = await api('GET', `/geo/provinces?department_id=${state.selectedDepartmentId}`, {});
        state.provinces = data.data || [];
    } catch {
        state.provinces = [];
    }
    render();
}

async function onGeoProvinceChange(raw) {
    const id = raw ? Number(raw) : NaN;
    state.selectedProvinceId = Number.isFinite(id) ? id : null;
    state.selectedDistrictId = null;
    state.districts = [];
    render();
    if (!state.selectedProvinceId) return;
    try {
        const data = await api('GET', `/geo/districts?province_id=${state.selectedProvinceId}`, {});
        state.districts = data.data || [];
    } catch {
        state.districts = [];
    }
    render();
}

function onGeoDistrictChange(raw) {
    const id = raw ? Number(raw) : NaN;
    state.selectedDistrictId = Number.isFinite(id) ? id : null;
    render();
}

async function runSearch() {
    state.loading = true;
    state.error = null;
    state.searched = true;
    state.detailService = null;
    clearDetailSolicitudState();
    render();
    const params = new URLSearchParams();
    if (state.selectedCategoryId != null) params.set('category_id', String(state.selectedCategoryId));
    if (state.selectedDistrictId != null) params.set('district_id', String(state.selectedDistrictId));
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
        const u = data.user;
        const role = u?.role;
        const wants = state.gateRole;
        if (
            role &&
            wants &&
            role !== 'admin' &&
            role !== wants
        ) {
            throw new Error(
                wants === 'proveedor'
                    ? 'Este correo no pertenece a una cuenta de proveedor. Elige «Cliente» o usa el correo de tu negocio.'
                    : 'Este correo no pertenece a una cuenta de cliente. Elige «Proveedor» o registra otro usuario.',
            );
        }
        state.token = data.token;
        state.user = u;
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

async function submitDetailServiceRequest(root) {
    const svc = state.detailService;
    if (!svc || !state.token || state.user?.role !== 'cliente' || !root) return;
    const chanEl = root.querySelector('#svc-req-channel');
    const msgEl = root.querySelector('#svc-req-msg');
    const channel = String(chanEl?.value || 'whatsapp');
    const msg = msgEl?.value ? String(msgEl.value).trim() : '';
    state.detailSolicitudLoading = true;
    state.detailSolicitudError = '';
    render();
    try {
        await api('POST', '/client/service-requests', {
            auth: true,
            body: {
                provider_service_id: Number(svc.service_id),
                contact_channel: channel,
                message: msg || null,
            },
        });
        state.detailSolicitudOk = 'Solicitud enviada. El proveedor podrá ver tu solicitud.';
        state.detailSolicitudError = '';
    } catch (e) {
        state.detailSolicitudOk = '';
        state.detailSolicitudError = e.message || 'No se pudo enviar.';
    } finally {
        state.detailSolicitudLoading = false;
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
        state.view = 'main';
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
        state.view = 'main';
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
    persistAuth();
    state.view = 'main';
    state.results = [];
    state.searched = false;
    state.selectedDepartmentId = null;
    state.selectedProvinceId = null;
    state.selectedDistrictId = null;
    state.provinces = [];
    state.districts = [];
    await loadCategories();
    render();
}

async function boot() {
    try {
        localStorage.removeItem('chamba_web_guest');
    } catch {
        /* noop */
    }
    const fromReset = parseResetFromUrl();
    if (!fromReset) {
        parseGateRoleFromUrl();
        parseLandingSearchFromUrl();
        stripAppMarketingQueryFromUrl();
    }
    if (state.view === 'reset') {
        render();
        return;
    }
    let sessionOk = false;
    if (!fromReset && state.token) {
        try {
            const data = await api('GET', '/auth/me', { auth: true });
            state.user = data.user || data;
            localStorage.setItem(LS_USER, JSON.stringify(state.user));
            sessionOk = true;
        } catch {
            state.token = null;
            state.user = null;
            persistAuth();
            sessionOk = false;
        }
    }
    const forceGate = state.openGateOnBoot && !sessionOk;
    state.openGateOnBoot = false;
    if (forceGate) {
        state.view = 'gate';
        render();
        return;
    }
    state.view = 'main';
    await loadCategories();
    render();
}

function renderGate(root) {
    root.innerHTML = `
        <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center px-4 py-12">
            <div class="w-full max-w-[420px] rounded-xl bg-white shadow-lg shadow-slate-200/80 border border-slate-200/60 overflow-hidden">
                <div class="px-8 pt-10 pb-2">
                    <div class="flex items-center justify-center gap-3 mb-8">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#003874] text-white shadow-md shadow-[#003874]/25">
                            <span class="material-symbols-outlined text-[26px]">handyman</span>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-[#003874] select-none">Chamba</span>
                    </div>
                    <h1 class="text-center font-['Playfair_Display',Georgia,serif] text-[1.85rem] font-semibold text-slate-900 leading-tight tracking-tight mb-6">
                        Inicio de sesión
                    </h1>
                    <p class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Entrar como</p>
                    <div class="mb-8 grid grid-cols-2 gap-1.5 rounded-lg border border-slate-200 bg-slate-50 p-1">
                        <button type="button" data-gate-role="cliente" class="rounded-md py-2.5 text-sm font-semibold transition ${state.gateRole === 'cliente' ? 'bg-white text-[#003874] shadow-sm ring-1 ring-slate-200/80' : 'text-slate-600 hover:text-slate-900'}">Cliente</button>
                        <button type="button" data-gate-role="proveedor" class="rounded-md py-2.5 text-sm font-semibold transition ${state.gateRole === 'proveedor' ? 'bg-white text-[#003874] shadow-sm ring-1 ring-slate-200/80' : 'text-slate-600 hover:text-slate-900'}">Proveedor</button>
                    </div>
                    ${state.flashMessage ? `<div class="mb-5 rounded-lg bg-emerald-50 text-emerald-900 text-sm font-medium px-4 py-3 border border-emerald-100">${escapeHtml(state.flashMessage)}</div>` : ''}
                    ${state.error ? `<div class="mb-5 rounded-lg bg-red-50 text-red-800 text-sm font-medium px-4 py-3 border border-red-100">${escapeHtml(state.error)}</div>` : ''}
                    <form id="gate-login-form" class="space-y-5">
                        <div>
                            <label for="gate-email" class="mb-2 block text-sm font-bold text-slate-700">Correo electrónico o usuario</label>
                            <input id="gate-email" name="email" type="email" required autocomplete="email" placeholder="Correo electrónico o usuario"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15" />
                        </div>
                        <div>
                            <label for="gate-password" class="mb-2 block text-sm font-bold text-slate-700">Contraseña</label>
                            <input id="gate-password" name="password" type="password" required autocomplete="current-password" placeholder="Contraseña"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15" />
                            <button type="button" id="gate-forgot" class="mt-3 text-left text-sm font-medium text-[#003874]/90 hover:text-[#003874] hover:underline">
                                ¿Olvidaste tu contraseña?
                            </button>
                        </div>
                        <button type="submit" ${state.loading ? 'disabled' : ''} class="w-full rounded-lg bg-[#052c65] hover:bg-[#003874] py-3.5 text-[15px] font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-55">
                            ${state.loading ? 'Entrando…' : 'Iniciar sesión'}
                        </button>
                    </form>
                </div>
                <div class="bg-slate-50 border-t border-slate-200 px-8 py-5 text-center space-y-3">
                    <button type="button" data-act="register" class="text-[15px] font-medium text-slate-600 hover:text-[#003874] underline-offset-4 hover:underline w-full bg-transparent border-0 cursor-pointer p-0">
                        Crear una cuenta
                    </button>
                    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-slate-500 pt-1">
                        <button type="button" data-act="browse-no-login" class="font-medium text-slate-600 hover:text-[#003874] bg-transparent border-0 cursor-pointer hover:underline p-0">
                            Seguir explorando sin iniciar sesión
                        </button>
                        <span class="text-slate-300" aria-hidden="true">|</span>
                        <a href="${escapeHtml(window.CHAMBA_LANDING_URL || window.CHAMBA_HOME_URL || '/')}" class="font-medium text-slate-600 hover:text-[#003874] hover:underline">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    `;
    root.querySelectorAll('[data-gate-role]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const r = btn.getAttribute('data-gate-role');
            if (r === 'cliente' || r === 'proveedor') state.gateRole = r;
            clearError();
            render();
        });
    });
    root.querySelector('#gate-forgot').addEventListener('click', () => {
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
    root.querySelector('[data-act="browse-no-login"]').addEventListener('click', () => {
        closeGateToBrowse();
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
                                    <label class="cursor-pointer"><input type="radio" name="role" value="cliente" ${state.gateRole !== 'proveedor' ? 'checked' : ''} class="peer sr-only" />
                                        <span class="block text-center rounded-xl border-2 border-stone-200 peer-checked:border-teal-600 peer-checked:bg-teal-50 py-4 font-bold text-sm sm:text-base text-stone-800 transition">Cliente</span></label>
                                    <label class="cursor-pointer"><input type="radio" name="role" value="proveedor" ${state.gateRole === 'proveedor' ? 'checked' : ''} class="peer sr-only" />
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

function renderMain(root) {
    const u = state.user;
    const landing = landingHref();
    const portalBase =
        typeof window !== 'undefined' && window.CHAMBA_APP_URL
            ? String(window.CHAMBA_APP_URL).replace(/\/?$/, '')
            : '/app';
    const proveedorHref = `${portalBase}?cuenta=proveedor`;
    const initials = (() => {
        const n = u?.full_name ? String(u.full_name).trim() : '';
        if (!n) return '';
        const p = n.split(/\s+/).filter(Boolean);
        const a = (p[0]?.[0] || '').toUpperCase();
        const b = (p.length > 1 ? p[p.length - 1]?.[0] : p[0]?.[1] || '').toUpperCase();
        return (a + b).slice(0, 2) || '?';
    })();

    const navSearchCls = `text-sm font-medium tracking-tight pb-1 border-b-2 transition ${
        state.mainTab === 'search' ? 'text-[#003874] border-[#003874]' : 'text-slate-500 border-transparent hover:text-[#003874]'
    }`;
    const navAccountCls = `text-sm font-medium tracking-tight pb-1 border-b-2 transition ${
        state.mainTab === 'account' ? 'text-[#003874] border-[#003874]' : 'text-slate-500 border-transparent hover:text-[#003874]'
    }`;

    const categorySection =
        state.categories.length === 0
            ? '<p class="text-sm text-slate-500">Cargando categorías…</p>'
            : `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            ${state.categories
                .map((c) => {
                    const st = categoryLandingStyle(c.name);
                    const active = state.selectedCategoryId === c.id;
                    return `<button type="button" data-cat="${c.id}" class="group flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border ${active ? 'border-[#003874] ring-2 ring-[#003874]/30' : 'border-slate-100'} hover:shadow-md transition-all">
                    <div class="w-16 h-16 rounded-2xl ${st.box} flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined text-3xl">${st.icon}</span>
                    </div>
                    <span class="font-semibold text-slate-900 text-center text-sm leading-snug">${escapeHtml(c.name)}</span>
                </button>`;
                })
                .join('')}
        </div>`;

    const depOpts = `<option value="">Departamento</option>${state.departments
        .map(
            (d) =>
                `<option value="${d.id}" ${state.selectedDepartmentId === Number(d.id) ? 'selected' : ''}>${escapeHtml(d.name)}</option>`,
        )
        .join('')}`;
    const provOpts = `<option value="">Provincia</option>${state.provinces
        .map(
            (p) =>
                `<option value="${p.id}" ${state.selectedProvinceId === Number(p.id) ? 'selected' : ''}>${escapeHtml(p.name)}</option>`,
        )
        .join('')}`;
    const distOpts = `<option value="">Distrito</option>${state.districts
        .map(
            (d) =>
                `<option value="${d.id}" ${state.selectedDistrictId === Number(d.id) ? 'selected' : ''}>${escapeHtml(d.name)}</option>`,
        )
        .join('')}`;

    const resultsBlock = (() => {
        if (!state.searched) {
            return `<p class="text-center text-slate-500 py-12 text-sm max-w-md mx-auto">Escribe lo que necesitas y pulsa <strong class="text-slate-700">Buscar</strong>, o elige una categoría y vuelve a buscar.</p>`;
        }
        if (state.loading) {
            return `<div class="py-20 text-center text-slate-500 font-medium">Buscando…</div>`;
        }
        if (state.error) {
            return `<div class="rounded-xl border border-red-100 bg-red-50 text-red-800 text-sm font-medium px-4 py-3">${escapeHtml(state.error)}</div>`;
        }
        if (state.results.length === 0) {
            return `<div class="rounded-xl border border-slate-200 bg-white py-16 px-6 text-center text-slate-600">Sin resultados. Prueba otras palabras, otra categoría o otra zona.</div>`;
        }
        return `<div id="results-scroll" class="-mx-4 px-4 overflow-x-auto md:mx-0 md:px-0 md:overflow-visible pb-1 scroll-smooth">
            <ul id="results-list" class="flex gap-6 md:grid md:grid-cols-2 lg:grid-cols-4 md:w-full min-w-0">
            ${state.results
                .map((r, idx) => {
                    const sid = Number(r.service_id);
                    const titleS = escapeHtml(r.title || '');
                    const prov = escapeHtml(r.provider_name || 'Proveedor');
                    const loc = escapeHtml([r.district_name, r.province_name].filter(Boolean).join(', '));
                    const thumb = serviceListingImageUrl(sid);
                    const badge = idx < 2
                        ? `<span class="bg-blue-100 text-[#003874] text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shrink-0">Destacado</span>`
                        : '';
                    return `<li tabindex="0" role="link" data-service-id="${sid}" class="group w-[min(20rem,calc(100vw-4rem))] shrink-0 md:w-auto md:shrink md:min-w-0 bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-lg transition-all cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-[#003874] focus-visible:ring-offset-2">
                    <div class="relative h-48 bg-slate-200 overflow-hidden">
                        <img src="${thumb}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
                        ${cardRatingBadgeHtml(r)}
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <h3 class="font-bold text-[#0b1c30] leading-snug">${titleS}</h3>
                            ${badge}
                        </div>
                        <p class="text-sm font-semibold text-slate-800 mb-0.5">${prov}</p>
                        <div class="flex items-center gap-1 text-slate-500 text-sm mb-4">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            <span>${loc || '—'}</span>
                        </div>
                        ${cardPriceFooterHtml(r)}
                    </div>
                </li>`;
                })
                .join('')}
        </ul></div>`;
    })();

    const accountTab = !state.token
        ? `<div class="max-w-7xl mx-auto px-4 md:px-8 py-12 pb-32 md:pb-16">
                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    <div class="lg:col-span-7">
                        <h2 class="text-2xl lg:text-3xl font-bold text-[#0b1c30] tracking-tight">Tu cuenta</h2>
                        <p class="text-slate-600 mt-4 text-lg leading-relaxed">Puedes explorar servicios sin iniciar sesión. Para enviar solicitudes guardadas como cliente, inicia sesión o créate una cuenta.</p>
                    </div>
                    <div class="lg:col-span-5 flex flex-col gap-3">
                        <button type="button" data-to-login class="w-full rounded-full bg-[#003874] hover:bg-[#08458b] text-white font-bold py-3.5 px-6 shadow-lg shadow-[#003874]/20">Iniciar sesión</button>
                        <button type="button" data-to-register class="w-full rounded-full border-2 border-slate-200 hover:border-[#003874]/40 bg-white font-bold py-3.5 text-[#003874]">Crear cuenta</button>
                    </div>
                </div>
           </div>`
        : `<div class="max-w-7xl mx-auto px-4 md:px-8 py-12 pb-32 md:pb-16">
                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    <div class="lg:col-span-7 rounded-3xl border border-slate-200 bg-white p-8 lg:p-10 shadow-sm">
                        <p class="text-xs font-bold text-[#003874] uppercase tracking-widest">${escapeHtml(u?.role === 'proveedor' ? 'Proveedor' : 'Cliente')}</p>
                        <h2 class="text-3xl lg:text-4xl font-black text-[#0b1c30] mt-2 tracking-tight">${escapeHtml(u?.full_name || '')}</h2>
                        <p class="text-slate-700 text-base mt-4">${escapeHtml(u?.email || '')}</p>
                        <p class="text-sm text-slate-600 mt-6 font-medium">Estado: <span class="text-[#0b1c30]">${escapeHtml(u?.status || '')}</span></p>
                    </div>
                    <div class="lg:col-span-5 flex flex-col justify-end gap-3">
                        <button type="button" data-logout class="w-full rounded-full bg-[#003874] hover:bg-[#08458b] text-white font-bold py-3.5 px-6">Cerrar sesión</button>
                    </div>
                </div>
           </div>`;

    const searchBody = `<div class="pb-8">
        <section id="chamba-hero-search" class="py-10 md:py-16 text-center flex flex-col items-center px-4 scroll-mt-28">
            <h1 class="text-3xl md:text-4xl font-bold text-[#0b1c30] mb-8 max-w-2xl leading-tight tracking-tight">Encuentra al experto ideal para tu hogar</h1>
            <div class="w-full max-w-4xl bg-white p-4 md:p-2 rounded-xl md:rounded-full shadow-lg border border-slate-100 flex flex-col md:flex-row items-center gap-3 md:gap-2">
                <div class="flex-1 w-full flex items-center px-4 gap-3">
                    <span class="material-symbols-outlined text-slate-400">search</span>
                    <input type="search" id="kw" value="${escapeHtml(state.keyword)}" placeholder="¿Qué servicio necesitas?"
                        class="w-full border-none focus:ring-0 text-base placeholder:text-slate-400 bg-transparent outline-none min-w-0" />
                </div>
                <div class="hidden md:block w-px h-9 bg-slate-200 shrink-0"></div>
                <div class="w-full md:w-auto flex flex-col sm:flex-row gap-2 px-2 min-w-0">
                    <label class="sr-only" for="geo-dept">Departamento</label>
                    <select id="geo-dept" class="border-none rounded-lg bg-slate-50 md:bg-transparent px-2 py-2 text-xs font-semibold uppercase tracking-wide text-[#003874] focus:ring-0 w-full md:w-auto min-w-0">${depOpts}</select>
                    <label class="sr-only" for="geo-prov">Provincia</label>
                    <select id="geo-prov" ${state.selectedDepartmentId ? '' : 'disabled'} class="border-none rounded-lg bg-slate-50 md:bg-transparent px-2 py-2 text-xs font-semibold uppercase tracking-wide text-[#003874] focus:ring-0 disabled:opacity-40 w-full md:w-auto min-w-0">${provOpts}</select>
                    <label class="sr-only" for="geo-dist">Distrito</label>
                    <select id="geo-dist" ${state.selectedProvinceId ? '' : 'disabled'} class="border-none rounded-lg bg-slate-50 md:bg-transparent px-2 py-2 text-xs font-semibold uppercase tracking-wide text-[#003874] focus:ring-0 disabled:opacity-40 w-full md:w-auto min-w-0">${distOpts}</select>
                </div>
                <button type="button" id="btn-search" class="w-full md:w-auto shrink-0 bg-[#ff7a2b] text-[#602500] px-8 py-3 rounded-full font-bold hover:brightness-105 active:scale-[0.98] transition-all">Buscar</button>
            </div>
            ${
                state.categories.length === 0
                    ? ''
                    : `<button type="button" data-trigger-search class="mt-6 text-[#003874] font-semibold text-sm hover:underline">Mostrar todas las categorías abajo · Quitar filtro rubro</button>`
            }
        </section>
        <section class="max-w-7xl mx-auto px-4 md:px-8 mb-16">
            <div class="flex justify-between items-end mb-8 flex-wrap gap-4">
                <h2 class="text-2xl font-semibold text-[#0b1c30] tracking-tight">Categorías populares</h2>
                <button type="button" data-cat="" class="text-[#003874] font-semibold text-sm hover:underline shrink-0">Ver todas</button>
            </div>
            ${categorySection}
        </section>
        <section class="max-w-7xl mx-auto px-4 md:px-8 mb-16">
            <div class="flex justify-between items-end mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-[#0b1c30] tracking-tight">Servicios destacados</h2>
                    ${
                        state.searched && !state.loading && !state.error
                            ? `<p class="text-sm text-slate-500 mt-1">${state.results.length} resultado(s)</p>`
                            : ''
                    }
                </div>
                <div class="flex gap-2 shrink-0">
                    <button type="button" id="results-carousel-prev" class="p-2 rounded-full border border-slate-200 hover:bg-white bg-white shadow-sm" aria-label="Anterior"><span class="material-symbols-outlined text-slate-600">chevron_left</span></button>
                    <button type="button" id="results-carousel-next" class="p-2 rounded-full border border-slate-200 hover:bg-white bg-white shadow-sm" aria-label="Siguiente"><span class="material-symbols-outlined text-slate-600">chevron_right</span></button>
                </div>
            </div>
            ${resultsBlock}
        </section>
        <section id="chamba-como" class="scroll-mt-24 mx-4 md:mx-8 max-w-[calc(100%-2rem)] md:max-w-7xl md:mx-auto bg-white rounded-3xl p-8 md:p-16 border border-slate-100 relative overflow-hidden mb-16">
            <div class="relative z-10">
                <h2 class="text-2xl font-semibold text-center text-[#0b1c30] mb-12">¿Cómo funciona Chamba?</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-[#003874] text-white flex items-center justify-center font-black text-xl mb-6 shadow-lg shadow-[#003874]/25">1</div>
                        <h3 class="text-lg font-semibold text-[#0b1c30] mb-3">Busca el servicio</h3>
                        <p class="text-slate-600">Explora categorías y encuentra profesionales por zona.</p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-[#003874] text-white flex items-center justify-center font-black text-xl mb-6 shadow-lg shadow-[#003874]/25">2</div>
                        <h3 class="text-lg font-semibold text-[#0b1c30] mb-3">Compara y cotiza</h3>
                        <p class="text-slate-600">Revisa reputación y detalles antes de contactar por WhatsApp o teléfono.</p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-[#003874] text-white flex items-center justify-center font-black text-xl mb-6 shadow-lg shadow-[#003874]/25">3</div>
                        <h3 class="text-lg font-semibold text-[#0b1c30] mb-3">Contrata con confianza</h3>
                        <p class="text-slate-600">Coordina visita y pago directamente con el profesional según lo acordado.</p>
                    </div>
                </div>
                <div class="mt-12 text-center">
                    <button type="button" id="cta-como-buscar" class="inline-flex bg-[#003874] text-white px-10 py-4 rounded-full font-bold text-lg hover:shadow-xl active:scale-[0.99] transition-shadow">¡Empieza ahora!</button>
                </div>
            </div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 w-64 h-64 bg-[#1a4f95]/10 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -top-24 -left-24 w-64 h-64 bg-[#ff7a2b]/15 rounded-full blur-3xl"></div>
        </section>
        <footer class="bg-[#dce9ff]/60 border-t border-slate-200 pt-14 pb-32 md:pb-14 mt-4">
            <div class="max-w-7xl mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-4 gap-10">
                <div>
                    <span class="text-2xl font-black text-[#003874] tracking-tighter mb-4 block">Chamba</span>
                    <p class="text-slate-600 text-sm leading-relaxed">Talentos locales para tu hogar. Encuentra, compara y contacta desde un solo lugar.</p>
                </div>
                <div>
                    <h4 class="font-bold text-[#0b1c30] mb-5 uppercase text-xs tracking-widest">Plataforma</h4>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li><button type="button" data-tab="search" class="hover:text-[#003874] text-left">Buscar servicios</button></li>
                        <li><button type="button" data-scroll-como class="hover:text-[#003874] text-left">Cómo funciona</button></li>
                        <li><a href="${escapeHtml(proveedorHref)}" class="hover:text-[#003874]">Soy proveedor</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-[#0b1c30] mb-5 uppercase text-xs tracking-widest">Soporte</h4>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li><a href="${escapeHtml(portalBase)}" class="hover:text-[#003874]">Centro de ayuda</a></li>
                        <li><span class="text-slate-400">Términos y privacidad (próximamente)</span></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-[#0b1c30] mb-5 uppercase text-xs tracking-widest">Social</h4>
                    <div class="flex gap-3 text-xs font-semibold">
                        <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200">FB</span>
                        <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200">IG</span>
                    </div>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 md:px-8 mt-12 pt-8 border-t border-slate-200/80 flex flex-col md:flex-row justify-between gap-4 text-xs text-slate-500">
                <p>© ${new Date().getFullYear()} Chamba</p>
                <span>Web app</span>
            </div>
        </footer>
    </div>`;

    const mainContent = state.mainTab === 'search' ? searchBody : accountTab;

    const detailOverlay = state.detailService ? serviceDetailModalHtml(state.detailService) : '';

    const flashBanner = state.flashMessage
        ? `<div class="max-w-7xl mx-auto px-4 pt-3">
                <div class="flex gap-3 items-start justify-between rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                    <p class="flex-1 pr-2">${escapeHtml(state.flashMessage)}</p>
                    <button type="button" data-dismiss-flash class="shrink-0 -mt-0.5 rounded-lg px-2 py-1 text-lg font-bold text-emerald-800 hover:bg-emerald-100/80 leading-none border-0 bg-transparent cursor-pointer" aria-label="Cerrar">×</button>
                </div>
            </div>`
        : '';

    root.innerHTML = `
        <div class="min-h-screen flex flex-col bg-[#f8f9ff] md:pb-0 pb-[5.25rem]">
            ${detailOverlay}
            <header class="sticky top-0 z-30 bg-white border-b border-slate-200 shadow-sm">
                <nav class="max-w-7xl mx-auto px-4 h-[4rem] flex justify-between items-center gap-4">
                    <div class="flex items-center gap-6 lg:gap-10 min-w-0">
                        <a href="${escapeHtml(landing)}" class="text-2xl font-black text-[#003874] tracking-tighter shrink-0 no-underline">Chamba</a>
                        <div class="hidden md:flex items-center gap-6">
                            <button type="button" data-tab="search" class="${navSearchCls} bg-transparent cursor-pointer">Buscar servicios</button>
                            <button type="button" data-scroll-como-nav class="hidden lg:inline text-sm font-medium tracking-tight pb-1 border-b-2 border-transparent text-slate-500 hover:text-[#003874] bg-transparent cursor-pointer">Cómo funciona</button>
                            <button type="button" data-tab="account" class="${navAccountCls} bg-transparent cursor-pointer">Mi cuenta</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                        ${state.mainTab === 'search' ? `<a href="${escapeHtml(proveedorHref)}" class="hidden lg:inline text-sm font-semibold text-[#003874] hover:underline whitespace-nowrap">Cambiar a proveedor</a>` : ''}
                        ${state.mainTab === 'search' ? `<button type="button" data-scroll-hero-search class="p-2 rounded-full hover:bg-slate-50 text-slate-600 hidden sm:flex" aria-label="Ir al buscador"><span class="material-symbols-outlined text-slate-600">location_on</span></button>` : ''}
                        ${
                            !state.token
                                ? `<button type="button" data-to-gate-entry class="rounded-full border border-[#003874]/30 px-4 py-2 text-sm font-bold text-[#003874] hover:bg-[#003874]/5">Acceder</button>`
                                : `<button type="button" data-tab="account" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#003874] text-white font-bold text-sm border border-slate-200 shadow-sm" aria-label="Mi cuenta">${escapeHtml(initials)}</button>`
                        }
                    </div>
                </nav>
                <div class="md:hidden px-4 pb-2 flex gap-2 border-t border-slate-100 pt-2">
                    <button type="button" data-tab="search" class="flex-1 rounded-lg py-2 text-xs font-bold ${state.mainTab === 'search' ? 'bg-[#003874]/10 text-[#003874]' : 'bg-slate-50 text-slate-600'}">Buscar</button>
                    <button type="button" data-tab="account" class="flex-1 rounded-lg py-2 text-xs font-bold ${state.mainTab === 'account' ? 'bg-[#003874]/10 text-[#003874]' : 'bg-slate-50 text-slate-600'}">Cuenta</button>
                </div>
                ${flashBanner}
            </header>
            <main class="flex-1 w-full">${mainContent}</main>
            <nav class="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur border-t border-slate-200 flex justify-around py-2.5 safe-area-pb shadow-[0_-4px_12px_rgba(0,0,0,0.06)] rounded-t-xl">
                <button type="button" data-tab="search" class="flex flex-col items-center px-4 py-1 ${state.mainTab === 'search' ? 'text-[#003874]' : 'text-slate-400'}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                    <span class="text-[10px] font-bold uppercase mt-0.5">Explorar</span>
                </button>
                <button type="button" data-scroll-como-float class="flex flex-col items-center px-4 py-1 text-slate-400">
                    <span class="material-symbols-outlined text-[22px]">info</span>
                    <span class="text-[10px] font-bold uppercase mt-0.5">Cómo</span>
                </button>
                <button type="button" data-tab="account" class="flex flex-col items-center px-4 py-1 ${state.mainTab === 'account' ? 'text-[#003874]' : 'text-slate-400'}">
                    <span class="material-symbols-outlined text-[22px]">person</span>
                    <span class="text-[10px] font-bold uppercase mt-0.5">Perfil</span>
                </button>
            </nav>
        </div>
    `;

    root.querySelectorAll('[data-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.mainTab = btn.getAttribute('data-tab');
            state.detailService = null;
            clearDetailSolicitudState();
            render();
        });
    });

    root.querySelectorAll('[data-cat]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.detailService = null;
            clearDetailSolicitudState();
            const v = btn.getAttribute('data-cat');
            state.selectedCategoryId = v === '' || v == null ? null : Number(v);
            void runSearch();
        });
    });

    root.querySelector('#geo-dept')?.addEventListener('change', (e) => void onGeoDepartmentChange(e.target.value));
    root.querySelector('#geo-prov')?.addEventListener('change', (e) => void onGeoProvinceChange(e.target.value));
    root.querySelector('#geo-dist')?.addEventListener('change', (e) => onGeoDistrictChange(e.target.value));

    root.querySelector('[data-to-gate-entry]')?.addEventListener('click', () => openLoginGate());

    root.querySelector('[data-dismiss-flash]')?.addEventListener('click', () => {
        state.flashMessage = '';
        render();
    });

    root.querySelectorAll('[data-detail-to-login]').forEach((b) =>
        b.addEventListener('click', () => {
            state.detailService = null;
            clearDetailSolicitudState();
            openLoginGate();
        }),
    );
    root.querySelectorAll('[data-detail-to-register]').forEach((b) =>
        b.addEventListener('click', () => {
            state.detailService = null;
            clearDetailSolicitudState();
            clearError();
            state.view = 'register';
            render();
        }),
    );

    const scrollToComo = () => {
        const el = document.getElementById('chamba-como');
        const go = () => el?.scrollIntoView({ behavior: 'smooth' });
        if (state.mainTab !== 'search') {
            state.mainTab = 'search';
            state.detailService = null;
            clearDetailSolicitudState();
            render();
            requestAnimationFrame(() => requestAnimationFrame(() => go()));
        } else go();
    };
    root.querySelectorAll('[data-scroll-como],[data-scroll-como-nav],[data-scroll-como-float]').forEach((b) =>
        b.addEventListener('click', scrollToComo),
    );

    root.querySelector('[data-scroll-hero-search]')?.addEventListener('click', () =>
        document.getElementById('chamba-hero-search')?.scrollIntoView({ behavior: 'smooth' }),
    );

    root.querySelector('#cta-como-buscar')?.addEventListener('click', () =>
        document.getElementById('chamba-hero-search')?.scrollIntoView({ behavior: 'smooth' }),
    );

    root.querySelector('[data-trigger-search]')?.addEventListener('click', () => {
        state.selectedCategoryId = null;
        void runSearch();
    });

    const rs = root.querySelector('#results-scroll');
    root.querySelector('#results-carousel-prev')?.addEventListener('click', () =>
        rs?.scrollBy?.({ left: -300, behavior: 'smooth' }),
    );
    root.querySelector('#results-carousel-next')?.addEventListener('click', () =>
        rs?.scrollBy?.({ left: 300, behavior: 'smooth' }),
    );

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
            clearError();
            state.view = 'gate';
            render();
        });
    const toR = root.querySelector('[data-to-register]');
    if (toR)
        toR.addEventListener('click', () => {
            clearError();
            state.view = 'register';
            render();
        });

    root.querySelector('#svc-req-submit')?.addEventListener('click', () => void submitDetailServiceRequest(root));

    const resultsList = root.querySelector('#results-list');
    if (resultsList) {
        const openDetailFromCard = (card) => {
            const id = Number(card.getAttribute('data-service-id'));
            const svc = state.results.find((row) => Number(row.service_id) === id);
            if (svc) {
                state.detailService = svc;
                clearDetailSolicitudState();
                render();
            }
        };
        resultsList.addEventListener('click', (e) => {
            const card = e.target.closest('[data-service-id]');
            if (!card) return;
            openDetailFromCard(card);
        });
        resultsList.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const card = e.target.closest('[data-service-id]');
            if (!card) return;
            e.preventDefault();
            openDetailFromCard(card);
        });
    }

    root.querySelectorAll('[data-close-detail]').forEach((el) => {
        el.addEventListener('click', () => {
            state.detailService = null;
            clearDetailSolicitudState();
            render();
        });
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
