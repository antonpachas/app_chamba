<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppPasswordInput from '@/components/ui/AppPasswordInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import AvatarUploader from '@/components/common/AvatarUploader.vue';
import ProviderBusinessProfileSection from '@/components/provider/ProviderBusinessProfileSection.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const escrow = escrowEnabled();

const activeTab = ref('personal');

const form = ref({
    full_name: '',
    email: '',
    phone: '',
    current_password: '',
    password: '',
    password_confirmation: '',
});
const saving = ref(false);
const errMsg = ref('');
const okMsg = ref('');
const showPasswordFields = ref(false);

const initials = computed(() => {
    const n = auth.user?.full_name?.trim() || '';
    if (!n) return '?';
    const p = n.split(/\s+/).filter(Boolean);
    return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?';
});

const supportRoute = computed(() =>
    auth.user?.role === 'admin' ? { name: 'admin-support' } : { name: 'support' },
);

const tabs = computed(() => {
    const list = [
        { id: 'personal', label: 'Mi perfil', icon: 'person', desc: 'Datos personales y acceso' },
    ];
    if (auth.isProveedor) {
        list.push({ id: 'negocio', label: 'Mi negocio', icon: 'storefront', desc: 'Ficha pública y contacto' });
    }
    list.push({ id: 'accesos', label: 'Accesos rápidos', icon: 'apps', desc: 'Atajos de la plataforma' });
    return list;
});

const quickLinks = computed(() => {
    const links = [];
    if (auth.isCliente) {
        links.push(
            { to: { name: 'home' }, icon: 'explore', label: 'Explorar anuncios', desc: 'Encuentra negocios cerca de ti', accent: 'primary' },
            { to: { name: 'client-requests' }, icon: 'inbox', label: 'Mis solicitudes', desc: 'Contactos y cotizaciones' },
            { to: { name: 'client-favorites' }, icon: 'favorite', label: 'Mis favoritos', desc: 'Anuncios que guardaste' },
            { to: { name: 'client-subscription' }, icon: 'workspace_premium', label: auth.isPro ? 'Mi membresía Premium' : 'Hazte Premium', desc: auth.isPro ? 'Gestiona tu plan' : 'Desde S/ 9 al mes', accent: 'warm' },
        );
        if (escrow) {
            links.push({ to: { name: 'client-wallet' }, icon: 'account_balance_wallet', label: 'Mis pagos', desc: 'Historial y comprobantes' });
        }
    }
    if (auth.isProveedor) {
        links.push(
            { to: { name: 'provider-dashboard' }, icon: 'dashboard', label: 'Panel del negocio', desc: 'Resumen de actividad', accent: 'primary' },
            { to: { name: 'provider-listings' }, icon: 'campaign', label: 'Mis anuncios', desc: 'Crea y edita fichas' },
            { to: { name: 'provider-requests' }, icon: 'inbox', label: 'Contactos recibidos', desc: 'Clientes interesados' },
            { to: { name: 'provider-subscription' }, icon: 'verified', label: auth.isPro ? 'Mi plan Pro' : 'Pasarme a Pro', desc: auth.isPro ? 'Beneficios activos' : 'S/ 29 al mes', accent: 'warm' },
        );
        if (escrow) {
            links.push({ to: { name: 'provider-wallet' }, icon: 'payments', label: 'Mis ingresos', desc: 'Saldo y retiros' });
        }
    }
    if (auth.user?.role === 'admin') {
        links.push(
            { to: { name: 'admin-dashboard' }, icon: 'admin_panel_settings', label: 'Panel admin', desc: 'Vista general', accent: 'primary' },
            { to: { name: 'admin-subscriptions' }, icon: 'card_membership', label: 'Membresías', desc: 'Pagos y planes' },
            { to: { name: 'admin-moderation' }, icon: 'gavel', label: 'Moderación', desc: 'Anuncios y reportes' },
            { to: { name: 'admin-category-suggestions' }, icon: 'category', label: 'Categorías', desc: 'Directorio y sugerencias' },
            { to: { name: 'admin-support' }, icon: 'support_agent', label: 'Soporte', desc: 'Casos abiertos' },
            { to: { name: 'admin-ledger' }, icon: 'receipt_long', label: 'Kardex', desc: 'Ingresos y egresos' },
        );
        if (escrow) {
            links.push(
                { to: { name: 'admin-payments' }, icon: 'lock', label: 'Pagos en custodia', desc: 'Revisar depósitos' },
                { to: { name: 'admin-withdrawals' }, icon: 'account_balance', label: 'Retiros', desc: 'Procesar solicitudes' },
            );
        }
    }
    links.push({ to: { name: 'support' }, icon: 'help', label: 'Soporte / ayuda', desc: 'Habla con el equipo' });
    return links;
});

function syncFormFromUser() {
    form.value.full_name = auth.user?.full_name || '';
    form.value.email = auth.user?.email || '';
    form.value.phone = auth.user?.phone || '';
    form.value.current_password = '';
    form.value.password = '';
    form.value.password_confirmation = '';
}

function resolveTabFromHash() {
    const hash = route.hash || (typeof window !== 'undefined' ? window.location.hash : '');
    if (auth.isProveedor && hash === '#perfil-negocio') {
        activeTab.value = 'negocio';
        return;
    }
    if (hash === '#accesos') {
        activeTab.value = 'accesos';
    }
}

function setTab(id) {
    activeTab.value = id;
    errMsg.value = '';
    okMsg.value = '';
    let hash = '';
    if (id === 'negocio') hash = '#perfil-negocio';
    else if (id === 'accesos') hash = '#accesos';
    router.replace({ hash });
}

onMounted(() => {
    syncFormFromUser();
    resolveTabFromHash();
});

watch(() => route.hash, resolveTabFromHash);

async function saveProfile() {
    errMsg.value = '';
    okMsg.value = '';
    saving.value = true;
    try {
        const payload = {
            full_name: form.value.full_name.trim(),
            phone: form.value.phone.trim() || null,
        };
        if (showPasswordFields.value && form.value.password) {
            payload.current_password = form.value.current_password;
            payload.password = form.value.password;
            payload.password_confirmation = form.value.password_confirmation;
        }
        await auth.updateProfile(payload);
        okMsg.value = 'Datos guardados correctamente.';
        showPasswordFields.value = false;
        syncFormFromUser();
    } catch (e) {
        errMsg.value = e.message || 'No se pudo guardar el perfil.';
    } finally {
        saving.value = false;
    }
}

async function doLogout() {
    await auth.logout();
    router.replace({ name: 'home' });
}

function linkCardClass(accent) {
    if (accent === 'primary') {
        return 'border-[#003874]/20 bg-gradient-to-br from-[#003874]/5 to-sky-50/80 hover:border-[#003874]/40 hover:shadow-md hover:shadow-[#003874]/10';
    }
    if (accent === 'warm') {
        return 'border-orange-200/80 bg-gradient-to-br from-orange-50/90 to-amber-50/60 hover:border-orange-300 hover:shadow-md hover:shadow-orange-500/10';
    }
    return 'border-slate-200/90 bg-white hover:border-[#003874]/25 hover:shadow-md hover:shadow-slate-200/80';
}

function linkIconClass(accent) {
    if (accent === 'primary') return 'bg-[#003874] text-white';
    if (accent === 'warm') return 'bg-grad-warm text-white';
    return 'bg-slate-100 text-[#003874]';
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8 pb-32 md:pb-16">
        <!-- Hero -->
        <header class="relative mb-0 rounded-3xl bg-grad-hero text-white overflow-hidden shadow-xl shadow-[#003874]/25">
            <div class="pointer-events-none absolute -bottom-24 -right-16 w-80 h-80 bg-[#0ea5e9]/25 rounded-full blur-3xl" />
            <div class="pointer-events-none absolute -top-16 -left-16 w-64 h-64 bg-[#ff7a2b]/20 rounded-full blur-3xl" />

            <div class="relative z-10 px-6 md:px-10 pt-8 pb-28 md:pb-32">
                <div class="flex items-center justify-between gap-3 mb-5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/65">Mi cuenta</p>
                    <RouterLink
                        :to="supportRoute"
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur border border-white/20 text-white text-xs font-bold px-3.5 py-2 no-underline transition-colors shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[16px]">support_agent</span>
                        Soporte
                    </RouterLink>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                    <div class="relative shrink-0">
                        <img
                            v-if="auth.avatarUrl"
                            :src="auth.avatarUrl"
                            alt=""
                            class="w-24 h-24 md:w-28 md:h-28 rounded-2xl object-cover ring-4 ring-white/30 shadow-2xl"
                        />
                        <div
                            v-else
                            class="w-24 h-24 md:w-28 md:h-28 rounded-2xl bg-white/15 backdrop-blur text-white text-3xl md:text-4xl font-black flex items-center justify-center ring-4 ring-white/30 shadow-2xl"
                        >
                            {{ initials }}
                        </div>
                        <span
                            v-if="auth.isPro"
                            class="absolute -top-2 -right-2 text-[9px] font-black uppercase tracking-wide rounded-full px-2 py-0.5 bg-grad-warm text-white shadow-lg ring-2 ring-white"
                        >
                            PRO
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl md:text-3xl font-black tracking-tight truncate capitalize">
                            {{ auth.user?.full_name || 'Usuario' }}
                        </h1>
                        <p class="text-white/75 text-sm mt-1 truncate">{{ form.email }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider bg-white/15 backdrop-blur px-2.5 py-1 rounded-full">
                                <span class="material-symbols-outlined text-[14px]">{{ auth.isProveedor ? 'storefront' : auth.isAdmin ? 'shield_person' : 'person' }}</span>
                                {{ auth.roleLabel }}
                            </span>
                            <span v-if="auth.inTrial" class="text-[11px] font-bold uppercase tracking-wide bg-amber-400/90 text-amber-950 px-2.5 py-1 rounded-full">
                                Trial · {{ auth.trialDaysLeft }}d
                            </span>
                            <span v-if="auth.planName" class="text-[11px] font-semibold text-white/80 bg-white/10 px-2.5 py-1 rounded-full">
                                {{ auth.planName }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Panel principal -->
        <div class="relative z-20 -mt-20 md:-mt-24">
            <!-- Tabs -->
            <div
                class="mx-2 md:mx-4 mb-4 flex gap-1.5 p-1.5 rounded-2xl bg-white/95 backdrop-blur-md border border-white/80 shadow-lg shadow-slate-900/5 overflow-x-auto scrollbar-none"
                role="tablist"
            >
                <button
                    v-for="t in tabs"
                    :key="t.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === t.id"
                    class="group flex-1 min-w-[120px] flex items-center gap-2.5 px-4 py-3 rounded-xl text-left transition-all duration-200"
                    :class="
                        activeTab === t.id
                            ? 'bg-[#003874] text-white shadow-md shadow-[#003874]/25'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-[#003874]'
                    "
                    @click="setTab(t.id)"
                >
                    <span
                        class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                        :class="activeTab === t.id ? 'bg-white/15' : 'bg-slate-100 group-hover:bg-[#003874]/10'"
                    >
                        <span class="material-symbols-outlined text-[20px]">{{ t.icon }}</span>
                    </span>
                    <span class="min-w-0 hidden sm:block">
                        <span class="block text-sm font-bold leading-tight">{{ t.label }}</span>
                        <span
                            class="block text-[11px] truncate mt-0.5"
                            :class="activeTab === t.id ? 'text-white/70' : 'text-slate-400'"
                        >
                            {{ t.desc }}
                        </span>
                    </span>
                    <span class="sm:hidden text-sm font-bold">{{ t.label.split(' ').slice(-1)[0] }}</span>
                </button>
            </div>

            <AppAlert v-if="errMsg && activeTab === 'personal'" type="error" class="mb-4 mx-1">{{ errMsg }}</AppAlert>
            <AppAlert v-if="okMsg && activeTab === 'personal'" type="success" class="mb-4 mx-1">{{ okMsg }}</AppAlert>

            <!-- Tab: Personal -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="activeTab === 'personal'"
                    key="personal"
                    class="rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden"
                    role="tabpanel"
                >
                    <div class="px-6 md:px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 to-white">
                        <h2 class="text-lg font-bold text-[#0b1c30]">Datos personales</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Tu identidad en Busca PE y credenciales de acceso.</p>
                    </div>

                    <div class="p-6 md:p-8 space-y-8">
                        <section class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 md:p-6">
                            <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">photo_camera</span>
                                Foto de perfil
                            </h3>
                            <AvatarUploader />
                        </section>

                        <form class="space-y-6" @submit.prevent="saveProfile">
                            <section class="space-y-5">
                                <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">badge</span>
                                    Información básica
                                </h3>
                                <AppInput v-model="form.full_name" label="Nombre completo" placeholder="Juan Pérez" autocomplete="name" />
                                <div>
                                    <span class="mb-2 block text-sm font-bold text-slate-700">Correo electrónico</span>
                                    <p class="ui-input bg-slate-50 text-slate-600 cursor-not-allowed">{{ form.email || '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">El correo no se puede cambiar desde aquí. Contacta soporte si necesitas actualizarlo.</p>
                                </div>
                                <AppInput v-model="form.phone" label="Teléfono (opcional)" placeholder="999 999 999" autocomplete="tel" />
                            </section>

                            <section class="rounded-2xl border border-slate-100 p-5 md:p-6 space-y-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">lock</span>
                                        Seguridad
                                    </h3>
                                    <button
                                        type="button"
                                        class="text-sm font-bold text-[#003874] hover:underline"
                                        @click="showPasswordFields = !showPasswordFields"
                                    >
                                        {{ showPasswordFields ? 'Ocultar' : 'Cambiar contraseña' }}
                                    </button>
                                </div>
                                <Transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                >
                                    <div v-if="showPasswordFields" class="space-y-4 pt-1">
                                        <AppPasswordInput v-model="form.current_password" label="Contraseña actual" autocomplete="current-password" />
                                        <AppPasswordInput v-model="form.password" label="Nueva contraseña" autocomplete="new-password" />
                                        <AppPasswordInput v-model="form.password_confirmation" label="Confirmar nueva contraseña" autocomplete="new-password" />
                                    </div>
                                </Transition>
                                <p v-if="!showPasswordFields" class="text-xs text-slate-500">
                                    Protege tu cuenta con una contraseña segura y única.
                                </p>
                            </section>

                            <section class="grid sm:grid-cols-2 gap-4 rounded-2xl border border-slate-100 bg-slate-50/40 p-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Estado de cuenta</p>
                                    <p class="text-sm font-bold text-[#0b1c30] mt-1 capitalize">{{ auth.user?.status || '—' }}</p>
                                </div>
                                <div v-if="auth.planName">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Plan activo</p>
                                    <p class="text-sm font-bold text-[#0b1c30] mt-1">{{ auth.planName }}</p>
                                </div>
                            </section>

                            <div class="flex flex-wrap gap-3 pt-2">
                                <AppButton variant="primary" type="submit" :loading="saving">Guardar cambios</AppButton>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>

            <!-- Tab: Negocio -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="activeTab === 'negocio' && auth.isProveedor" key="negocio" role="tabpanel">
                    <ProviderBusinessProfileSection embedded />
                </div>
            </Transition>

            <!-- Tab: Accesos -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="activeTab === 'accesos'"
                    key="accesos"
                    class="rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden"
                    role="tabpanel"
                >
                    <div class="px-6 md:px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 to-white">
                        <h2 class="text-lg font-bold text-[#0b1c30]">Accesos rápidos</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Salta directo a las secciones que más usas.</p>
                    </div>
                    <div class="p-6 md:p-8">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <RouterLink
                                v-for="(link, i) in quickLinks"
                                :key="i"
                                :to="link.to"
                                class="group flex items-start gap-4 p-4 rounded-2xl border no-underline text-inherit transition-all duration-200"
                                :class="linkCardClass(link.accent)"
                            >
                                <span
                                    class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center shadow-sm transition-transform group-hover:scale-105"
                                    :class="linkIconClass(link.accent)"
                                >
                                    <span class="material-symbols-outlined text-[22px]">{{ link.icon }}</span>
                                </span>
                                <span class="min-w-0 pt-0.5">
                                    <span class="block text-sm font-bold text-[#0b1c30] group-hover:text-[#003874] transition-colors">{{ link.label }}</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">{{ link.desc }}</span>
                                </span>
                                <span class="material-symbols-outlined text-slate-300 group-hover:text-[#003874] ml-auto shrink-0 transition-colors">chevron_right</span>
                            </RouterLink>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-700">¿Quieres salir de tu sesión?</p>
                                <p class="text-xs text-slate-500 mt-0.5">Cierra sesión de forma segura en este dispositivo.</p>
                            </div>
                            <AppButton variant="outline" class="sm:shrink-0" @click="doLogout">Cerrar sesión</AppButton>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-none {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
</style>
