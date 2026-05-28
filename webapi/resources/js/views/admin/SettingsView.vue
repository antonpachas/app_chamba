<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
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

const groupLabels = {
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

const groupOrder = ['features', 'notifications', 'providers', 'listings', 'limits', 'ads', 'payouts', 'subscriptions', 'escrow', 'general'];

const orderedGroups = computed(() => {
    const groups = store.settingsByGroup;
    const keys = Object.keys(groups);
    return groupOrder
        .filter((g) => keys.includes(g))
        .concat(keys.filter((g) => !groupOrder.includes(g)))
        .map((g) => ({ key: g, label: groupLabels[g] || g, items: groups[g] }));
});

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
    } catch (e) {
        err.value = e.message;
    }
}

onMounted(reload);

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
async function toggleSettingsLogs() {
    showSettingsLogs.value = !showSettingsLogs.value;
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
                        @click="tab = 'settings'"
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

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>

        <!-- TAB: PLANES -->
        <section v-else-if="tab === 'plans'" class="space-y-5">
            <div
                v-for="plan in store.plans"
                :key="plan.id"
                class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'inline-flex items-center justify-center w-12 h-12 rounded-2xl text-white font-bold text-lg',
                                plan.tier === 'free' ? 'bg-slate-400'
                                    : plan.tier === 'pro' ? 'bg-grad-brand'
                                    : 'bg-grad-warm',
                            ]"
                        >{{ plan.tier === 'free' ? 'F' : plan.tier === 'pro' ? 'P' : '★' }}</span>
                        <div>
                            <h3 class="text-lg font-bold text-[#0b1c30]">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">
                                {{ plan.audience }} · {{ plan.tier }} · <code class="font-mono">{{ plan.code }}</code>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-[#0b1c30]">{{ fmtMoney(plan.price, plan.currency) }}</p>
                        <p class="text-xs text-slate-500">por mes</p>
                    </div>
                </div>

                <div v-if="planDrafts[plan.id]" class="grid md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Nombre del plan</span>
                        <input v-model="planDrafts[plan.id].name" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Precio mensual ({{ plan.currency }})</span>
                        <input v-model.number="planDrafts[plan.id].price" type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                    </label>

                    <label v-if="plan.tier === 'free' && plan.audience === 'proveedor'" class="block">
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Contactos / mes (Free)</span>
                        <input v-model.number="planDrafts[plan.id].features.contacts_per_month" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                    </label>

                    <label v-if="plan.audience === 'proveedor'" class="block">
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Servicios máx.</span>
                        <input
                            v-model.number="planDrafts[plan.id].features.max_services"
                            type="number"
                            min="0"
                            placeholder="Vacío = ilimitado"
                            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                        />
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Soporte</span>
                        <input v-model="planDrafts[plan.id].features.support" placeholder="standard / prioritario / vip" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none" />
                    </label>

                    <label class="flex items-center gap-2 mt-2">
                        <input v-model="planDrafts[plan.id].is_active" type="checkbox" class="w-4 h-4" />
                        <span class="text-sm text-slate-700">Plan activo (visible para usuarios)</span>
                    </label>
                </div>

                <div class="mt-5 flex flex-wrap gap-2 items-center">
                    <AppButton @click="savePlan(plan)" :loading="store.saving" variant="primary">
                        Guardar cambios
                    </AppButton>
                    <button type="button" @click="togglePlanLogs(plan)" class="text-sm text-chamba-700 hover:text-chamba-800 font-semibold">
                        {{ planLogsOpen === plan.id ? 'Ocultar' : 'Ver' }} historial de cambios
                    </button>
                </div>

                <div v-if="planLogsOpen === plan.id" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p v-if="!store.planLogs[plan.id]" class="text-sm text-slate-500">Cargando historial…</p>
                    <p v-else-if="!store.planLogs[plan.id].length" class="text-sm text-slate-500">Sin cambios registrados.</p>
                    <ul v-else class="space-y-2 text-sm">
                        <li v-for="log in store.planLogs[plan.id]" :key="log.id" class="flex flex-wrap gap-2 items-baseline">
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-chamba-100 text-chamba-800">{{ log.field }}</span>
                            <span class="text-slate-500 line-through">{{ log.old_value || 'vacío' }}</span>
                            <span class="text-slate-400">→</span>
                            <span class="font-semibold text-[#0b1c30]">{{ log.new_value || 'vacío' }}</span>
                            <span class="ml-auto text-xs text-slate-500">{{ fmtDate(log.created_at) }} · {{ log.changed_by || 'sistema' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- TAB: SETTINGS -->
        <section v-else-if="tab === 'settings'" class="space-y-5">
            <div v-for="g in orderedGroups" :key="g.key" class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
                <h3 class="text-lg font-bold text-[#0b1c30] mb-4">{{ g.label }}</h3>
                <div class="space-y-4">
                    <div v-for="s in g.items" :key="s.key" class="grid md:grid-cols-[1fr_auto] gap-3 items-center border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                        <div>
                            <p class="text-sm font-semibold text-[#0b1c30]">{{ s.label }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ s.description }}</p>
                            <code class="text-[10px] text-slate-400 font-mono">{{ s.key }}</code>
                        </div>
                        <div class="flex gap-2 items-center">
                            <select
                                v-if="s.type === 'boolean'"
                                v-model="settingsDraft[s.key]"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-40"
                            >
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                            <input
                                v-else
                                v-model="settingsDraft[s.key]"
                                :type="['integer', 'decimal'].includes(s.type) ? 'number' : 'text'"
                                :step="s.type === 'decimal' ? '0.01' : '1'"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-56 font-mono focus:border-chamba-500 focus:ring-2 focus:ring-chamba-200/40 outline-none"
                            />
                            <AppButton size="sm" :loading="store.saving" @click="saveSetting(s)">Guardar</AppButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-bold text-[#0b1c30]">Historial de cambios</h3>
                    <button type="button" @click="toggleSettingsLogs" class="text-sm text-chamba-700 hover:text-chamba-800 font-semibold">
                        {{ showSettingsLogs ? 'Ocultar' : 'Mostrar' }}
                    </button>
                </div>
                <div v-if="showSettingsLogs">
                    <p v-if="!store.settingsLogs.length" class="text-sm text-slate-500">Aún no hay cambios registrados.</p>
                    <ul v-else class="space-y-2 text-sm">
                        <li v-for="log in store.settingsLogs" :key="log.id" class="flex flex-wrap gap-2 items-baseline">
                            <code class="font-mono text-xs px-2 py-0.5 rounded bg-chamba-100 text-chamba-800">{{ log.setting_key }}</code>
                            <span class="text-slate-500 line-through">{{ log.old_value || 'vacío' }}</span>
                            <span class="text-slate-400">→</span>
                            <span class="font-semibold text-[#0b1c30]">{{ log.new_value || 'vacío' }}</span>
                            <span class="ml-auto text-xs text-slate-500">{{ fmtDate(log.created_at) }} · {{ log.changed_by || 'sistema' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</template>
