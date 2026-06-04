<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useAdminSettingsStore } from '@/stores/adminSettings';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const store = useAdminSettingsStore();

const tab = ref('plans');
const err = ref('');
const ok = ref('');

const settingsDraft = reactive({});
const planDrafts = reactive({});
const planLogsOpen = ref(null);

const settingsQuery = ref('');
const activeGroupKey = ref('features');
const settingsPanel = ref('group'); // group | history

const plansQuery = ref('');
const activePlanId = ref(null);

const audienceLabels = {
    proveedor: 'Negocios / proveedores',
    cliente: 'Clientes',
};

const audienceIcons = {
    proveedor: 'storefront',
    cliente: 'groups',
};

const tierIcons = {
    free: 'money_off',
    pro: 'workspace_premium',
    premium: 'star',
};

const tierLabels = {
    free: 'Gratis',
    pro: 'Pro',
    premium: 'Premium',
};

const groupLabels = {
    ui: 'Interfaz y listados',
    providers: 'Perfiles públicos de negocios',
    listings: 'Anuncios (publicación)',
    limits: 'Límites free / premium',
    ads: 'Google AdSense y banners',
    payouts: 'Pagos de la plataforma',
    subscriptions: 'Suscripciones',
    escrow: 'Comisión (modo custodia)',
    features: 'Modos del sistema',
    notifications: 'Correos de notificación',
    general: 'General',
};

const groupIcons = {
    features: 'tune',
    ui: 'web',
    notifications: 'mail',
    providers: 'storefront',
    listings: 'campaign',
    limits: 'speed',
    ads: 'ads_click',
    payouts: 'payments',
    subscriptions: 'card_membership',
    escrow: 'account_balance',
    general: 'settings',
};

const groupOrder = ['features', 'ui', 'notifications', 'providers', 'listings', 'limits', 'ads', 'payouts', 'subscriptions', 'escrow', 'general'];

const orderedGroups = computed(() => {
    const groups = store.settingsByGroup;
    const keys = Object.keys(groups);
    return groupOrder
        .filter((g) => keys.includes(g))
        .concat(keys.filter((g) => !groupOrder.includes(g)))
        .map((g) => ({ key: g, label: groupLabels[g] || g, icon: groupIcons[g] || 'folder', items: groups[g] }));
});

const activeGroup = computed(() => orderedGroups.value.find((g) => g.key === activeGroupKey.value) || orderedGroups.value[0] || null);

const searchActive = computed(() => settingsQuery.value.trim().length >= 2);

const filteredSettings = computed(() => {
    const q = settingsQuery.value.trim().toLowerCase();
    if (q.length < 2) return [];
    return store.settings.filter((s) => {
        const hay = `${s.label || ''} ${s.key || ''} ${s.description || ''}`.toLowerCase();
        return hay.includes(q);
    });
});

const filteredByGroup = computed(() => {
    if (!searchActive.value) return [];
    const map = new Map();
    for (const s of filteredSettings.value) {
        const g = s.group || 'general';
        if (!map.has(g)) {
            map.set(g, {
                key: g,
                label: groupLabels[g] || g,
                icon: groupIcons[g] || 'folder',
                items: [],
            });
        }
        map.get(g).items.push(s);
    }
    return groupOrder
        .filter((k) => map.has(k))
        .concat([...map.keys()].filter((k) => !groupOrder.includes(k)))
        .map((k) => map.get(k));
});

function selectGroup(key) {
    settingsPanel.value = 'group';
    activeGroupKey.value = key;
    settingsQuery.value = '';
}

function openHistory() {
    settingsPanel.value = 'history';
    settingsQuery.value = '';
    toggleSettingsLogs(true);
}

const planGroups = computed(() => {
    const audiences = ['proveedor', 'cliente'];
    return audiences
        .map((a) => ({
            key: a,
            label: audienceLabels[a] || a,
            icon: audienceIcons[a] || 'folder',
            plans: store.plans.filter((p) => p.audience === a),
        }))
        .filter((g) => g.plans.length);
});

const activePlan = computed(
    () => store.plans.find((p) => p.id === activePlanId.value) || store.plans[0] || null,
);

const planSearchActive = computed(() => plansQuery.value.trim().length >= 2);

const filteredPlans = computed(() => {
    const q = plansQuery.value.trim().toLowerCase();
    if (q.length < 2) return [];
    return store.plans.filter((p) => {
        const hay = `${p.name || ''} ${p.code || ''} ${p.audience || ''} ${p.tier || ''}`.toLowerCase();
        return hay.includes(q);
    });
});

function selectPlan(plan) {
    if (!plan) return;
    activePlanId.value = plan.id;
    plansQuery.value = '';
    planLogsOpen.value = null;
}

function planTierClass(plan) {
    if (plan.tier === 'free') return 'bg-slate-400';
    if (plan.tier === 'pro') return 'bg-grad-brand';
    return 'bg-grad-warm';
}

function planTierLetter(plan) {
    if (plan.tier === 'free') return 'F';
    if (plan.tier === 'pro') return 'P';
    return '★';
}

function fmtMoney(v, currency = 'PEN') {
    if (v == null) return '—';
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(Number(v));
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'medium', timeStyle: 'short' });
}

async function reload() {
    err.value = ''; ok.value = '';
    try {
        await store.loadAll();
        for (const s of store.settings) settingsDraft[s.key] = s.value ?? '';
        for (const p of store.plans) {
            planDrafts[p.id] = {
                name: p.name,
                price: Number(p.price),
                features: { ...(p.features || {}) },
                is_active: !!p.is_active,
            };
        }
        if (orderedGroups.value.length && !orderedGroups.value.some((g) => g.key === activeGroupKey.value)) {
            activeGroupKey.value = orderedGroups.value[0].key;
        }
        if (store.plans.length && !store.plans.some((p) => p.id === activePlanId.value)) {
            activePlanId.value = store.plans[0].id;
        }
    } catch (e) {
        err.value = e.message;
    }
}

onMounted(reload);

watch(tab, (t) => {
    const hash = window.location.hash.replace(/^#/, '');
    if (t === 'plans' && hash.startsWith('plan-')) {
        const code = hash.replace(/^plan-/, '');
        const plan = store.plans.find((p) => p.code === code);
        if (plan) activePlanId.value = plan.id;
    } else if (t === 'settings' && orderedGroups.value.length && settingsPanel.value === 'group') {
        if (hash && orderedGroups.value.some((g) => g.key === hash)) {
            activeGroupKey.value = hash;
        }
    }
});

watch(activePlanId, (id) => {
    if (tab.value === 'plans' && id) {
        const plan = store.plans.find((p) => p.id === id);
        if (plan) window.history.replaceState(null, '', `#plan-${plan.code}`);
    }
});

watch(activeGroupKey, (key) => {
    if (tab.value === 'settings' && settingsPanel.value === 'group' && key) {
        window.history.replaceState(null, '', `#${key}`);
    }
});

async function saveSetting(s) {
    err.value = ''; ok.value = '';
    try {
        const value = settingsDraft[s.key];
        await store.saveSetting(s.key, value);
        ok.value = `Guardado: ${s.label}`;
    } catch (e) {
        err.value = e.message;
    }
}

async function savePlan(plan) {
    err.value = ''; ok.value = '';
    const draft = planDrafts[plan.id];
    try {
        await store.savePlan(plan, {
            name: draft.name,
            price: Number(draft.price),
            features: draft.features,
            is_active: !!draft.is_active,
        });
        ok.value = `Plan actualizado: ${plan.name}`;
    } catch (e) {
        err.value = e.message;
    }
}

async function togglePlanLogs(plan) {
    if (planLogsOpen.value === plan.id) {
        planLogsOpen.value = null;
        return;
    }
    planLogsOpen.value = plan.id;
    if (!store.planLogs[plan.id]) {
        try { await store.loadPlanLogs(plan.id); }
        catch (e) { err.value = e.message; }
    }
}

const showSettingsLogs = ref(false);
async function toggleSettingsLogs(forceOpen = null) {
    const next = forceOpen ?? !showSettingsLogs.value;
    showSettingsLogs.value = next;
    if (showSettingsLogs.value && !store.settingsLogs.length) {
        try { await store.loadSettingsLogs(); }
        catch (e) { err.value = e.message; }
    }
}
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 rounded-3xl bg-grad-hero px-6 md:px-8 py-7 text-white shadow-xl shadow-chamba-700/30">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-white/70">Admin</p>
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Configuración del sistema</h1>
                    <p class="text-white/85 mt-1 max-w-xl">
                        Edita precios de planes, datos de pago y configuración general. Cada cambio queda registrado con fecha y autor.
                    </p>
                </div>
                <div class="flex gap-2 rounded-2xl bg-white/15 backdrop-blur p-1">
                    <button
                        type="button"
                        @click="tab = 'plans'"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-semibold transition',
                            tab === 'plans' ? 'bg-white text-chamba-700 shadow' : 'text-white/85 hover:text-white',
                        ]"
                    >Planes y precios</button>
                    <button
                        type="button"
                        @click="tab = 'settings'; settingsPanel = 'group'"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-semibold transition',
                            tab === 'settings' ? 'bg-white text-chamba-700 shadow' : 'text-white/85 hover:text-white',
                        ]"
                    >Configuración general</button>
                </div>
            </div>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="mb-6 rounded-2xl border border-[#003874]/15 bg-sky-50/80 p-4 md:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-[#0b1c30]">Categorías del directorio</p>
                <p class="text-xs text-slate-600 mt-0.5">
                    Revisa lo que proponen desde Explorar y crea la categoría con un clic.
                </p>
            </div>
            <RouterLink
                :to="{ name: 'admin-category-suggestions' }"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-[#003874] text-white font-bold text-sm px-5 py-2.5 no-underline hover:bg-[#002a57] transition shrink-0"
            >
                <span class="material-symbols-outlined text-[18px]">category</span>
                Ir a sugerencias
            </RouterLink>
        </div>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>

        <!-- TAB: PLANES -->
        <section v-else-if="tab === 'plans'" class="admin-settings-layout">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start">
                <!-- Navegación lateral -->
                <aside class="w-full lg:w-72 shrink-0 lg:sticky lg:top-24 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <label class="block">
                            <span class="sr-only">Buscar plan</span>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                                <input
                                    v-model="plansQuery"
                                    type="search"
                                    placeholder="Buscar plan…"
                                    class="w-full rounded-xl border border-slate-200 pl-10 pr-3 py-2.5 text-sm focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                                />
                            </div>
                        </label>
                        <p v-if="planSearchActive" class="text-xs text-slate-500 mt-2">
                            {{ filteredPlans.length }} resultado{{ filteredPlans.length === 1 ? '' : 's' }}
                        </p>
                    </div>

                    <nav
                        v-if="!planSearchActive"
                        class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm hidden lg:block"
                        aria-label="Planes de suscripción"
                    >
                        <div v-for="(group, gi) in planGroups" :key="group.key" :class="gi > 0 ? 'mt-3 pt-3 border-t border-slate-100' : ''">
                            <p class="px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">{{ group.icon }}</span>
                                {{ group.label }}
                            </p>
                            <button
                                v-for="plan in group.plans"
                                :key="plan.id"
                                type="button"
                                @click="selectPlan(plan)"
                                :class="[
                                    'w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition mt-0.5',
                                    activePlan?.id === plan.id
                                        ? 'bg-chamba-50 text-chamba-800 font-semibold'
                                        : 'text-slate-700 hover:bg-slate-50',
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-flex items-center justify-center w-8 h-8 rounded-xl text-white text-xs font-bold shrink-0',
                                        planTierClass(plan),
                                    ]"
                                >{{ planTierLetter(plan) }}</span>
                                <span class="flex-1 min-w-0">
                                    <span class="block leading-snug truncate">{{ plan.name }}</span>
                                    <span class="block text-[10px] font-normal text-slate-500 mt-0.5">
                                        {{ tierLabels[plan.tier] || plan.tier }} · {{ fmtMoney(plan.price, plan.currency) }}
                                    </span>
                                </span>
                                <span
                                    v-if="!plan.is_active"
                                    class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-500"
                                >Off</span>
                            </button>
                        </div>
                    </nav>

                    <!-- Chips en móvil -->
                    <div v-if="!planSearchActive" class="lg:hidden space-y-3">
                        <div v-for="group in planGroups" :key="`m-${group.key}`">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1.5 px-1">{{ group.label }}</p>
                            <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                                <button
                                    v-for="plan in group.plans"
                                    :key="`m-plan-${plan.id}`"
                                    type="button"
                                    @click="selectPlan(plan)"
                                    :class="[
                                        'shrink-0 inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold border transition',
                                        activePlan?.id === plan.id
                                            ? 'bg-chamba-700 text-white border-chamba-700'
                                            : 'bg-white text-slate-700 border-slate-200',
                                    ]"
                                >
                                    <span class="material-symbols-outlined text-[14px]">{{ tierIcons[plan.tier] || 'sell' }}</span>
                                    {{ plan.name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Panel principal -->
                <div class="flex-1 min-w-0 w-full">
                    <!-- Resultados de búsqueda -->
                    <template v-if="planSearchActive">
                        <div v-if="!filteredPlans.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                            <p class="text-sm font-semibold text-slate-700 mt-2">Sin resultados</p>
                            <p class="text-xs text-slate-500 mt-1">Prueba con el nombre, código o tipo (ej. <code class="font-mono">provider_pro</code>).</p>
                        </div>
                        <div v-else class="space-y-3">
                            <button
                                v-for="plan in filteredPlans"
                                :key="`sr-plan-${plan.id}`"
                                type="button"
                                @click="selectPlan(plan)"
                                class="w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-left hover:border-chamba-200 hover:bg-chamba-50/30 transition flex items-center gap-4"
                            >
                                <span
                                    :class="[
                                        'inline-flex items-center justify-center w-11 h-11 rounded-2xl text-white font-bold',
                                        planTierClass(plan),
                                    ]"
                                >{{ planTierLetter(plan) }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-[#0b1c30]">{{ plan.name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ audienceLabels[plan.audience] || plan.audience }} · {{ tierLabels[plan.tier] || plan.tier }} · <code class="font-mono">{{ plan.code }}</code>
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-bold text-[#0b1c30]">{{ fmtMoney(plan.price, plan.currency) }}</p>
                                    <p class="text-xs text-chamba-600 font-semibold">Editar →</p>
                                </div>
                            </button>
                        </div>
                    </template>

                    <!-- Plan activo -->
                    <template v-else-if="activePlan">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
                            <header class="flex flex-wrap items-start justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
                                <div class="flex items-start gap-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center justify-center w-12 h-12 rounded-2xl text-white font-bold text-lg shrink-0',
                                            planTierClass(activePlan),
                                        ]"
                                    >{{ planTierLetter(activePlan) }}</span>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-0.5">
                                            {{ audienceLabels[activePlan.audience] || activePlan.audience }}
                                        </p>
                                        <h3 class="text-xl font-bold text-[#0b1c30]">{{ activePlan.name }}</h3>
                                        <p class="text-xs text-slate-500 mt-0.5 uppercase tracking-wider">
                                            {{ tierLabels[activePlan.tier] || activePlan.tier }} · <code class="font-mono">{{ activePlan.code }}</code>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-[#0b1c30]">{{ fmtMoney(activePlan.price, activePlan.currency) }}</p>
                                    <p class="text-xs text-slate-500">por mes</p>
                                    <span
                                        v-if="!activePlan.is_active"
                                        class="inline-block mt-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"
                                    >Inactivo</span>
                                </div>
                            </header>

                            <div v-if="planDrafts[activePlan.id]" class="grid md:grid-cols-2 gap-4">
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Nombre del plan</span>
                                    <input v-model="planDrafts[activePlan.id].name" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                                </label>
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Precio mensual ({{ activePlan.currency }})</span>
                                    <input v-model.number="planDrafts[activePlan.id].price" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                                </label>

                                <label v-if="activePlan.tier === 'free' && activePlan.audience === 'proveedor'" class="block">
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Contactos / mes (Free)</span>
                                    <input v-model.number="planDrafts[activePlan.id].features.contacts_per_month" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                                </label>

                                <label v-if="activePlan.audience === 'proveedor'" class="block">
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Servicios máx.</span>
                                    <input
                                        v-model.number="planDrafts[activePlan.id].features.max_services"
                                        type="number"
                                        min="0"
                                        placeholder="Vacío = ilimitado"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                                    />
                                </label>

                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Soporte</span>
                                    <input v-model="planDrafts[activePlan.id].features.support" placeholder="standard / prioritario / vip" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                                </label>

                                <label class="flex items-center gap-2 mt-2 md:col-span-2">
                                    <input v-model="planDrafts[activePlan.id].is_active" type="checkbox" class="w-4 h-4" />
                                    <span class="text-sm text-slate-700">Plan activo (visible para usuarios)</span>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2 items-center">
                                <AppButton @click="savePlan(activePlan)" :loading="store.saving" variant="primary">
                                    Guardar cambios
                                </AppButton>
                                <button type="button" @click="togglePlanLogs(activePlan)" class="text-sm text-chamba-700 hover:text-chamba-800 font-semibold">
                                    {{ planLogsOpen === activePlan.id ? 'Ocultar' : 'Ver' }} historial de cambios
                                </button>
                            </div>

                            <div v-if="planLogsOpen === activePlan.id" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p v-if="!store.planLogs[activePlan.id]" class="text-sm text-slate-500">Cargando historial…</p>
                                <p v-else-if="!store.planLogs[activePlan.id].length" class="text-sm text-slate-500">Sin cambios registrados.</p>
                                <ul v-else class="space-y-2 text-sm max-h-[min(50vh,24rem)] overflow-y-auto pr-1">
                                    <li v-for="log in store.planLogs[activePlan.id]" :key="log.id" class="flex flex-wrap gap-2 items-baseline rounded-lg border border-slate-100 bg-white/80 px-3 py-2">
                                        <span class="font-mono text-xs px-2 py-0.5 rounded bg-chamba-100 text-chamba-800">{{ log.field }}</span>
                                        <span class="text-slate-500 line-through">{{ log.old_value || 'vacío' }}</span>
                                        <span class="text-slate-400">→</span>
                                        <span class="font-semibold text-[#0b1c30]">{{ log.new_value || 'vacío' }}</span>
                                        <span class="ml-auto text-xs text-slate-500">{{ fmtDate(log.created_at) }} · {{ log.changed_by || 'sistema' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- TAB: SETTINGS -->
        <section v-else-if="tab === 'settings'" class="admin-settings-layout">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start">
                <!-- Navegación lateral -->
                <aside class="w-full lg:w-72 shrink-0 lg:sticky lg:top-24 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <label class="block">
                            <span class="sr-only">Buscar configuración</span>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                                <input
                                    v-model="settingsQuery"
                                    type="search"
                                    placeholder="Buscar opción…"
                                    class="w-full rounded-xl border border-slate-200 pl-10 pr-3 py-2.5 text-sm focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                                />
                            </div>
                        </label>
                        <p v-if="searchActive" class="text-xs text-slate-500 mt-2">
                            {{ filteredSettings.length }} resultado{{ filteredSettings.length === 1 ? '' : 's' }}
                        </p>
                    </div>

                    <nav
                        v-if="!searchActive"
                        class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm hidden lg:block"
                        aria-label="Secciones de configuración"
                    >
                        <button
                            v-for="g in orderedGroups"
                            :key="g.key"
                            type="button"
                            @click="selectGroup(g.key)"
                            :class="[
                                'w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition',
                                settingsPanel === 'group' && activeGroupKey === g.key
                                    ? 'bg-chamba-50 text-chamba-800 font-semibold'
                                    : 'text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <span
                                class="material-symbols-outlined text-[20px] shrink-0"
                                :class="settingsPanel === 'group' && activeGroupKey === g.key ? 'text-chamba-600' : 'text-slate-400'"
                            >{{ g.icon }}</span>
                            <span class="flex-1 min-w-0 leading-snug">{{ g.label }}</span>
                            <span class="text-[10px] font-bold tabular-nums px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                {{ g.items.length }}
                            </span>
                        </button>
                        <div class="border-t border-slate-100 my-2" />
                        <button
                            type="button"
                            @click="openHistory"
                            :class="[
                                'w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition',
                                settingsPanel === 'history'
                                    ? 'bg-chamba-50 text-chamba-800 font-semibold'
                                    : 'text-slate-700 hover:bg-slate-50',
                            ]"
                        >
                            <span class="material-symbols-outlined text-[20px] shrink-0 text-slate-400">history</span>
                            Historial de cambios
                        </button>
                    </nav>

                    <!-- Chips en móvil -->
                    <div v-if="!searchActive" class="lg:hidden space-y-2">
                        <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                            <button
                                v-for="g in orderedGroups"
                                :key="`m-${g.key}`"
                                type="button"
                                @click="selectGroup(g.key)"
                                :class="[
                                    'shrink-0 inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold border transition',
                                    settingsPanel === 'group' && activeGroupKey === g.key
                                        ? 'bg-chamba-700 text-white border-chamba-700'
                                        : 'bg-white text-slate-700 border-slate-200',
                                ]"
                            >
                                <span class="material-symbols-outlined text-[14px]">{{ g.icon }}</span>
                                {{ g.label }}
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="openHistory"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold border transition',
                                settingsPanel === 'history'
                                    ? 'bg-chamba-700 text-white border-chamba-700'
                                    : 'bg-white text-slate-700 border-slate-200',
                            ]"
                        >
                            <span class="material-symbols-outlined text-[14px]">history</span>
                            Historial
                        </button>
                    </div>
                </aside>

                <!-- Panel principal -->
                <div class="flex-1 min-w-0 w-full">
                    <!-- Resultados de búsqueda -->
                    <template v-if="searchActive">
                        <div v-if="!filteredSettings.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                            <p class="text-sm font-semibold text-slate-700 mt-2">Sin resultados</p>
                            <p class="text-xs text-slate-500 mt-1">Prueba con otra palabra clave o el código técnico (ej. <code class="font-mono">ads.</code>).</p>
                        </div>
                        <div v-else class="space-y-5">
                            <div
                                v-for="g in filteredByGroup"
                                :key="`sr-${g.key}`"
                                class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-2 mb-4 text-left w-full group"
                                    @click="selectGroup(g.key)"
                                >
                                    <span class="material-symbols-outlined text-chamba-600 text-[22px]">{{ g.icon }}</span>
                                    <h3 class="text-lg font-bold text-[#0b1c30] group-hover:text-chamba-700">{{ g.label }}</h3>
                                    <span class="text-xs text-chamba-600 font-semibold ml-auto">Ir a sección →</span>
                                </button>
                                <div class="space-y-4">
                                    <div
                                        v-for="s in g.items"
                                        :key="s.key"
                                        class="grid md:grid-cols-[1fr_auto] gap-3 items-start border-b border-slate-100 pb-4 last:border-b-0 last:pb-0"
                                    >
                                        <div>
                                            <p class="text-sm font-semibold text-[#0b1c30]">{{ s.label }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ s.description }}</p>
                                            <code class="text-[10px] text-slate-400 font-mono">{{ s.key }}</code>
                                        </div>
                                        <div class="flex gap-2 items-center flex-wrap md:justify-end">
                                            <select
                                                v-if="s.type === 'boolean'"
                                                v-model="settingsDraft[s.key]"
                                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full md:w-40"
                                            >
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                            <input
                                                v-else
                                                v-model="settingsDraft[s.key]"
                                                :type="['integer', 'decimal'].includes(s.type) ? 'number' : 'text'"
                                                :step="s.type === 'decimal' ? '0.01' : '1'"
                                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-full md:w-56 font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                                            />
                                            <AppButton size="sm" :loading="store.saving" @click="saveSetting(s)">Guardar</AppButton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Sección activa -->
                    <template v-else-if="settingsPanel === 'group' && activeGroup">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
                            <header class="flex flex-wrap items-start justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-chamba-50 text-chamba-700">
                                        <span class="material-symbols-outlined text-[24px]">{{ activeGroup.icon }}</span>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-[#0b1c30]">{{ activeGroup.label }}</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ activeGroup.items.length }} opciones en esta sección</p>
                                    </div>
                                </div>
                            </header>
                            <div class="space-y-4">
                                <div
                                    v-for="s in activeGroup.items"
                                    :key="s.key"
                                    class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 grid md:grid-cols-[1fr_auto] gap-3 items-start"
                                >
                                    <div>
                                        <p class="text-sm font-semibold text-[#0b1c30]">{{ s.label }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ s.description }}</p>
                                        <code class="text-[10px] text-slate-400 font-mono mt-1 inline-block">{{ s.key }}</code>
                                    </div>
                                    <div class="flex gap-2 items-center flex-wrap md:justify-end">
                                        <select
                                            v-if="s.type === 'boolean'"
                                            v-model="settingsDraft[s.key]"
                                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm w-full md:w-40"
                                        >
                                            <option value="1">Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                        <input
                                            v-else
                                            v-model="settingsDraft[s.key]"
                                            :type="['integer', 'decimal'].includes(s.type) ? 'number' : 'text'"
                                            :step="s.type === 'decimal' ? '0.01' : '1'"
                                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm w-full md:w-56 font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                                        />
                                        <AppButton size="sm" :loading="store.saving" @click="saveSetting(s)">Guardar</AppButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Historial -->
                    <template v-else-if="settingsPanel === 'history'">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
                            <header class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-slate-100 text-slate-600">
                                        <span class="material-symbols-outlined text-[24px]">history</span>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-[#0b1c30]">Historial de cambios</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">Registro de ajustes en configuración general</p>
                                    </div>
                                </div>
                                <button type="button" @click="toggleSettingsLogs()" class="text-sm text-chamba-700 hover:text-chamba-800 font-semibold">
                                    {{ showSettingsLogs ? 'Actualizar' : 'Cargar' }}
                                </button>
                            </header>
                            <div v-if="showSettingsLogs">
                                <p v-if="!store.settingsLogs.length" class="text-sm text-slate-500">Aún no hay cambios registrados.</p>
                                <ul v-else class="space-y-2 text-sm max-h-[min(70vh,32rem)] overflow-y-auto pr-1">
                                    <li v-for="log in store.settingsLogs" :key="log.id" class="flex flex-wrap gap-2 items-baseline rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2">
                                        <code class="font-mono text-xs px-2 py-0.5 rounded bg-chamba-100 text-chamba-800">{{ log.setting_key }}</code>
                                        <span class="text-slate-500 line-through">{{ log.old_value || 'vacío' }}</span>
                                        <span class="text-slate-400">→</span>
                                        <span class="font-semibold text-[#0b1c30]">{{ log.new_value || 'vacío' }}</span>
                                        <span class="ml-auto text-xs text-slate-500">{{ fmtDate(log.created_at) }} · {{ log.changed_by || 'sistema' }}</span>
                                    </li>
                                </ul>
                            </div>
                            <p v-else class="text-sm text-slate-500">Pulsa «Cargar» para ver el historial.</p>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </div>
</template>
