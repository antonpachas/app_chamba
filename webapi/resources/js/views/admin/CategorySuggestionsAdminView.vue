<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import AdminServerTable from '@/components/admin/AdminServerTable.vue';

const route = useRoute();
const router = useRouter();

const activeTab = ref('directorio');

const items = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 });
const perPage = ref(25);
const pendingCount = ref(0);
const loading = ref(false);
const err = ref('');
const ok = ref('');
const statusFilter = ref('pending');
const actionBusyId = ref(null);

const categories = ref([]);
const categoriesMeta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 });
const categoriesLoading = ref(false);
const newCategoryName = ref('');
const createCategoryBusy = ref(false);
const categoryBusyId = ref(null);

const mainTabs = computed(() => [
    {
        id: 'directorio',
        label: 'Directorio',
        icon: 'category',
        desc: 'Categorías publicadas',
        badge: categoriesMeta.value.total || null,
    },
    {
        id: 'sugerencias',
        label: 'Sugerencias',
        icon: 'lightbulb',
        desc: 'Propuestas de usuarios',
        badge: pendingCount.value > 0 ? pendingCount.value : null,
        badgeWarm: true,
    },
]);

const suggestionTabs = [
    { value: 'pending', label: 'Pendientes', icon: 'pending' },
    { value: 'reviewed', label: 'Revisadas', icon: 'check_circle' },
    { value: 'rejected', label: 'Rechazadas', icon: 'cancel' },
    { value: 'all', label: 'Todas', icon: 'list' },
];

const categoryColumns = [
    { key: 'name', label: 'Nombre' },
    { key: 'slug', label: 'Slug' },
    { key: 'listings_count', label: 'Anuncios', align: 'center' },
    { key: 'is_active', label: 'Estado', align: 'center' },
    { key: 'actions', label: '', align: 'right' },
];

const emptyMessage = computed(() => {
    if (statusFilter.value === 'pending') {
        return 'No hay sugerencias pendientes. Cuando un usuario proponga una categoría desde Explorar, aparecerá aquí.';
    }
    return 'No hay sugerencias en este filtro.';
});

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}

function statusLabel(status) {
    if (status === 'pending') return 'Pendiente';
    if (status === 'reviewed') return 'Revisada';
    if (status === 'rejected') return 'Rechazada';
    return status;
}

function statusClass(status) {
    if (status === 'pending') return 'bg-amber-100 text-amber-900';
    if (status === 'reviewed') return 'bg-emerald-100 text-emerald-900';
    if (status === 'rejected') return 'bg-slate-100 text-slate-600';
    return 'bg-slate-100 text-slate-700';
}

const columns = [
    { key: 'name', label: 'Categoría propuesta' },
    { key: 'user', label: 'Usuario' },
    { key: 'note', label: 'Nota' },
    { key: 'status', label: 'Estado', align: 'center' },
    { key: 'created_at', label: 'Fecha' },
    { key: 'actions', label: '', align: 'right' },
];

function setTab(id) {
    activeTab.value = id;
    const hash = id === 'sugerencias' ? '#sugerencias' : '';
    router.replace({ hash });
}

function resolveTabFromHash() {
    const hash = route.hash || (typeof window !== 'undefined' ? window.location.hash : '');
    if (hash === '#sugerencias') activeTab.value = 'sugerencias';
}

async function loadCategories(page = 1) {
    categoriesLoading.value = true;
    try {
        const r = await api.get('/admin/categories', {
            auth: true,
            params: { page, per_page: 25 },
        });
        categories.value = r.data || [];
        categoriesMeta.value = r.meta || categoriesMeta.value;
    } catch (e) {
        if (!err.value) err.value = e.message;
        categories.value = [];
    } finally {
        categoriesLoading.value = false;
    }
}

async function createCategory() {
    const name = newCategoryName.value.trim();
    if (name.length < 2) {
        err.value = 'El nombre debe tener al menos 2 caracteres.';
        return;
    }
    createCategoryBusy.value = true;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post('/admin/categories', { name, is_active: true }, { auth: true });
        ok.value = r.message || 'Categoría creada.';
        newCategoryName.value = '';
        await loadCategories(categoriesMeta.value.current_page || 1);
    } catch (e) {
        err.value = e.message;
    } finally {
        createCategoryBusy.value = false;
    }
}

async function toggleCategoryActive(row) {
    categoryBusyId.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.put(
            `/admin/categories/${row.id}`,
            { name: row.name, is_active: !row.is_active },
            { auth: true },
        );
        ok.value = r.message || 'Categoría actualizada.';
        await loadCategories(categoriesMeta.value.current_page || 1);
    } catch (e) {
        err.value = e.message;
    } finally {
        categoryBusyId.value = null;
    }
}

function onCategoriesPage(p) {
    loadCategories(p);
}

async function load(page = 1) {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/category-suggestions', {
            auth: true,
            params: {
                status: statusFilter.value,
                page,
                per_page: perPage.value,
            },
        });
        items.value = r.data || [];
        meta.value = r.meta || meta.value;
        pendingCount.value = r.meta?.pending_count ?? 0;
    } catch (e) {
        err.value = e.message;
        items.value = [];
    } finally {
        loading.value = false;
    }
}

async function setStatus(id, status) {
    actionBusyId.value = id;
    err.value = '';
    ok.value = '';
    try {
        await api.patch(`/admin/category-suggestions/${id}`, { status }, { auth: true });
        ok.value = 'Estado actualizado.';
        await load(meta.value.current_page || 1);
    } catch (e) {
        err.value = e.message;
    } finally {
        actionBusyId.value = null;
    }
}

async function approve(id, name) {
    if (!confirm(`¿Crear la categoría «${name}» y marcar esta sugerencia como revisada?`)) {
        return;
    }
    actionBusyId.value = id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/category-suggestions/${id}/approve`, {}, { auth: true });
        ok.value = r.message || 'Categoría creada.';
        await Promise.all([
            load(meta.value.current_page || 1),
            loadCategories(categoriesMeta.value.current_page || 1),
        ]);
    } catch (e) {
        err.value = e.message;
    } finally {
        actionBusyId.value = null;
    }
}

function onPage(p) {
    load(p);
}

function onPerPage(n) {
    perPage.value = n;
    load(1);
}

onMounted(() => {
    resolveTabFromHash();
    loadCategories(1);
    load(1);
});

watch(() => route.hash, resolveTabFromHash);
watch(statusFilter, () => load(1));
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 pb-24">
        <!-- Hero -->
        <header class="relative mb-0 rounded-3xl bg-grad-hero text-white overflow-hidden shadow-xl shadow-[#003874]/25">
            <div class="pointer-events-none absolute -bottom-20 -right-16 w-72 h-72 bg-[#0ea5e9]/25 rounded-full blur-3xl" />
            <div class="pointer-events-none absolute -top-16 -left-12 w-56 h-56 bg-[#ff7a2b]/20 rounded-full blur-3xl" />

            <div class="relative z-10 px-6 md:px-10 pt-8 pb-24 md:pb-28">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/65">Admin · Directorio</p>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight mt-2">Categorías del directorio</h1>
                <p class="text-white/80 text-sm mt-2 max-w-xl">
                    Publica categorías en Explorar o revisa lo que proponen los usuarios.
                </p>

                <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    <RouterLink :to="{ name: 'admin-dashboard' }" class="font-bold text-white/90 hover:text-white no-underline">
                        ← Panel admin
                    </RouterLink>
                    <RouterLink :to="{ name: 'admin-settings' }" class="font-bold text-white/70 hover:text-white no-underline">
                        Configuración
                    </RouterLink>
                    <RouterLink :to="{ name: 'home' }" class="font-bold text-white/70 hover:text-white no-underline">
                        Ver Explorar
                    </RouterLink>
                </div>

                <!-- Stats rápidos -->
                <div class="mt-6 flex flex-wrap gap-3">
                    <div class="rounded-xl bg-white/10 backdrop-blur border border-white/15 px-4 py-2.5 min-w-[120px]">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/60">Publicadas</p>
                        <p class="text-xl font-black">{{ categoriesMeta.total ?? '—' }}</p>
                    </div>
                    <div
                        class="rounded-xl backdrop-blur border px-4 py-2.5 min-w-[120px]"
                        :class="pendingCount > 0 ? 'bg-amber-400/20 border-amber-300/30' : 'bg-white/10 border-white/15'"
                    >
                        <p class="text-[10px] font-bold uppercase tracking-wider text-white/60">Pendientes</p>
                        <p class="text-xl font-black">{{ pendingCount }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Panel -->
        <div class="relative z-20 -mt-16 md:-mt-20">
            <!-- Tabs principales -->
            <div
                class="mx-2 md:mx-4 mb-4 flex gap-1.5 p-1.5 rounded-2xl bg-white/95 backdrop-blur-md border border-white/80 shadow-lg shadow-slate-900/5"
                role="tablist"
            >
                <button
                    v-for="t in mainTabs"
                    :key="t.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === t.id"
                    class="group flex-1 flex items-center gap-2.5 px-4 py-3 rounded-xl text-left transition-all duration-200"
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
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="text-sm font-bold leading-tight">{{ t.label }}</span>
                            <span
                                v-if="t.badge != null"
                                class="text-[10px] font-black min-w-[1.25rem] text-center rounded-full px-1.5 py-0.5"
                                :class="
                                    activeTab === t.id
                                        ? t.badgeWarm
                                            ? 'bg-amber-400 text-amber-950'
                                            : 'bg-white/25 text-white'
                                        : t.badgeWarm
                                          ? 'bg-amber-100 text-amber-900'
                                          : 'bg-slate-200 text-slate-700'
                                "
                            >
                                {{ t.badge }}
                            </span>
                        </span>
                        <span
                            class="block text-[11px] truncate mt-0.5"
                            :class="activeTab === t.id ? 'text-white/70' : 'text-slate-400'"
                        >
                            {{ t.desc }}
                        </span>
                    </span>
                </button>
            </div>

            <AppAlert v-if="err" type="error" class="mb-4 mx-1">{{ err }}</AppAlert>
            <AppAlert v-if="ok" type="success" class="mb-4 mx-1">{{ ok }}</AppAlert>

            <!-- Tab: Directorio -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="activeTab === 'directorio'"
                    key="directorio"
                    class="rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden"
                    role="tabpanel"
                >
                    <div class="px-6 md:px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 to-white">
                        <h2 class="text-lg font-bold text-[#0b1c30] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#003874]">add_circle</span>
                            Crear categoría
                        </h2>
                        <p class="text-sm text-slate-500 mt-0.5">Las categorías activas aparecen de inmediato en el buscador de Explorar.</p>
                    </div>

                    <div class="px-6 md:px-8 py-6 border-b border-slate-100">
                        <form class="flex flex-wrap gap-3 items-end" @submit.prevent="createCategory">
                            <label class="block flex-1 min-w-[200px]">
                                <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Nombre</span>
                                <input
                                    v-model="newCategoryName"
                                    type="text"
                                    maxlength="120"
                                    placeholder="Ej. Plomería, Belleza…"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                                />
                            </label>
                            <AppButton variant="primary" type="submit" :loading="createCategoryBusy">
                                Crear categoría
                            </AppButton>
                        </form>
                    </div>

                    <div class="px-6 md:px-8 py-5 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">folder_open</span>
                            Categorías publicadas
                        </h3>
                    </div>

                    <div class="px-2 md:px-4 pb-4">
                        <AdminServerTable
                            :columns="categoryColumns"
                            :rows="categories"
                            :meta="categoriesMeta"
                            :loading="categoriesLoading"
                            empty-message="Aún no hay categorías. Crea la primera arriba."
                            :show-per-page="false"
                            @page="onCategoriesPage"
                        >
                            <template #cell-listings_count="{ row }">
                                <span class="text-sm font-semibold text-slate-700">{{ row.listings_count ?? 0 }}</span>
                            </template>
                            <template #cell-is_active="{ row }">
                                <span
                                    class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full"
                                    :class="row.is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ row.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </template>
                            <template #cell-actions="{ row }">
                                <AppButton
                                    variant="outline"
                                    size="sm"
                                    :loading="categoryBusyId === row.id"
                                    :disabled="categoryBusyId != null && categoryBusyId !== row.id"
                                    @click="toggleCategoryActive(row)"
                                >
                                    {{ row.is_active ? 'Desactivar' : 'Activar' }}
                                </AppButton>
                            </template>
                        </AdminServerTable>
                    </div>
                </div>
            </Transition>

            <!-- Tab: Sugerencias -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="activeTab === 'sugerencias'"
                    key="sugerencias"
                    class="rounded-3xl border border-slate-200/90 bg-white shadow-sm overflow-hidden"
                    role="tabpanel"
                >
                    <div class="px-6 md:px-8 py-6 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 to-white">
                        <h2 class="text-lg font-bold text-[#0b1c30] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#003874]">lightbulb</span>
                            Sugerencias de usuarios
                        </h2>
                        <p class="text-sm text-slate-500 mt-0.5">
                            Propuestas enviadas desde el banner «Sugerir categoría» en Explorar.
                        </p>
                    </div>

                    <!-- Sub-tabs de estado -->
                    <div class="px-4 md:px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex flex-wrap gap-1.5 p-1 rounded-xl bg-white border border-slate-200/80 shadow-sm">
                            <button
                                v-for="t in suggestionTabs"
                                :key="t.value"
                                type="button"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-bold transition-all"
                                :class="
                                    statusFilter === t.value
                                        ? 'bg-[#003874] text-white shadow-sm'
                                        : 'text-slate-600 hover:bg-slate-50 hover:text-[#003874]'
                                "
                                @click="statusFilter = t.value"
                            >
                                <span class="material-symbols-outlined text-[16px]">{{ t.icon }}</span>
                                {{ t.label }}
                                <span
                                    v-if="t.value === 'pending' && pendingCount > 0"
                                    class="min-w-[1.15rem] text-center rounded-full text-[10px] font-black px-1.5 py-0.5"
                                    :class="statusFilter === t.value ? 'bg-amber-400 text-amber-950' : 'bg-amber-100 text-amber-900'"
                                >
                                    {{ pendingCount }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="px-2 md:px-4 pb-4 pt-2">
                        <AdminServerTable
                            :columns="columns"
                            :rows="items"
                            :meta="meta"
                            :loading="loading"
                            :empty-message="emptyMessage"
                            @page="onPage"
                            @per-page="onPerPage"
                        >
                            <template #cell-user="{ row }">
                                <span class="text-sm">{{ row.user?.full_name || '—' }}</span>
                                <span v-if="row.user?.email" class="block text-xs text-slate-500">{{ row.user.email }}</span>
                            </template>
                            <template #cell-note="{ row }">
                                <span class="text-sm text-slate-600 line-clamp-2 max-w-xs">{{ row.note || '—' }}</span>
                            </template>
                            <template #cell-status="{ row }">
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full" :class="statusClass(row.status)">
                                    {{ statusLabel(row.status) }}
                                </span>
                            </template>
                            <template #cell-created_at="{ row }">
                                <span class="text-xs text-slate-500">{{ fmtDate(row.created_at) }}</span>
                            </template>
                            <template #cell-actions="{ row }">
                                <div v-if="row.status === 'pending'" class="flex flex-col gap-1 items-end">
                                    <AppButton
                                        variant="primary"
                                        size="sm"
                                        :loading="actionBusyId === row.id"
                                        :disabled="actionBusyId != null && actionBusyId !== row.id"
                                        @click="approve(row.id, row.name)"
                                    >
                                        Crear
                                    </AppButton>
                                    <AppButton variant="outline" size="sm" :disabled="actionBusyId != null" @click="setStatus(row.id, 'rejected')">
                                        Rechazar
                                    </AppButton>
                                </div>
                                <AppButton
                                    v-else-if="row.status === 'rejected'"
                                    variant="outline"
                                    size="sm"
                                    :disabled="actionBusyId != null"
                                    @click="setStatus(row.id, 'pending')"
                                >
                                    Pendiente
                                </AppButton>
                            </template>
                        </AdminServerTable>
                    </div>
                </div>
            </Transition>

            <div class="mt-6 mx-1 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
                <span class="material-symbols-outlined text-[#003874] align-middle text-base mr-1">info</span>
                <strong>Crear categoría</strong> la publica en el buscador de inmediato.
                Al aprobar una sugerencia también se crea la categoría y queda visible en Explorar.
            </div>
        </div>
    </div>
</template>
