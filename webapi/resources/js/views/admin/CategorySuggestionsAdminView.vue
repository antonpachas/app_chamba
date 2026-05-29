<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import AdminServerTable from '@/components/admin/AdminServerTable.vue';

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

const categoryColumns = [
    { key: 'name', label: 'Nombre' },
    { key: 'slug', label: 'Slug' },
    { key: 'listings_count', label: 'Anuncios', align: 'center' },
    { key: 'is_active', label: 'Estado', align: 'center' },
    { key: 'actions', label: '', align: 'right' },
];

const tabs = [
    { value: 'pending', label: 'Pendientes' },
    { value: 'reviewed', label: 'Revisadas' },
    { value: 'rejected', label: 'Rechazadas' },
    { value: 'all', label: 'Todas' },
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
        await load(meta.value.current_page || 1);
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
    loadCategories(1);
    load(1);
});
watch(statusFilter, () => load(1));
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Admin · Directorio"
            title="Categorías del directorio"
            :subtitle="`Crea categorías directamente o revisa sugerencias de usuarios.${pendingCount ? ` · ${pendingCount} sugerencia(s) pendiente(s)` : ''}`"
            class="mb-6"
        />

        <div class="mb-6 flex flex-wrap gap-2">
            <RouterLink
                :to="{ name: 'admin-dashboard' }"
                class="text-sm font-bold text-[#003874] hover:underline no-underline"
            >
                ← Panel admin
            </RouterLink>
            <span class="text-slate-300">·</span>
            <RouterLink
                :to="{ name: 'admin-settings' }"
                class="text-sm font-bold text-slate-600 hover:text-[#003874] hover:underline no-underline"
            >
                Configuración
            </RouterLink>
            <RouterLink
                :to="{ name: 'home' }"
                class="text-sm font-bold text-slate-600 hover:text-[#003874] hover:underline no-underline"
            >
                Ver Explorar (público)
            </RouterLink>
        </div>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <section class="mb-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0b1c30] mb-1">Crear categoría</h2>
            <p class="text-sm text-slate-600 mb-4">
                Las categorías activas aparecen de inmediato en el buscador de Explorar.
            </p>
            <form class="flex flex-wrap gap-3 items-end" @submit.prevent="createCategory">
                <label class="block flex-1 min-w-[200px]">
                    <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Nombre</span>
                    <input
                        v-model="newCategoryName"
                        type="text"
                        maxlength="120"
                        placeholder="Ej. Plomería, Belleza…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                    />
                </label>
                <AppButton variant="primary" type="submit" :loading="createCategoryBusy">
                    Crear categoría
                </AppButton>
            </form>
        </section>

        <section class="mb-10">
            <h2 class="text-lg font-bold text-[#0b1c30] mb-4">Categorías publicadas</h2>
            <AdminServerTable
                :columns="categoryColumns"
                :rows="categories"
                :meta="categoriesMeta"
                :loading="categoriesLoading"
                empty-message="Aún no hay categorías. Crea la primera arriba."
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
        </section>

        <h2 class="text-lg font-bold text-[#0b1c30] mb-4">Sugerencias de usuarios</h2>

        <div class="flex flex-wrap gap-2 mb-6">
            <button
                v-for="t in tabs"
                :key="t.value"
                type="button"
                class="px-4 py-2 rounded-full text-sm font-bold border transition"
                :class="
                    statusFilter === t.value
                        ? 'bg-[#003874] text-white border-[#003874]'
                        : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/30'
                "
                @click="statusFilter = t.value"
            >
                {{ t.label }}
                <span
                    v-if="t.value === 'pending' && pendingCount > 0"
                    class="ml-1.5 inline-flex min-w-[1.25rem] justify-center rounded-full bg-amber-400 text-amber-950 text-[10px] px-1.5 py-0.5"
                >
                    {{ pendingCount }}
                </span>
            </button>
        </div>

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

        <div class="mt-8 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
            <span class="material-symbols-outlined text-[#003874] align-middle text-base mr-1">info</span>
            <strong>Crear categoría</strong> la publica en el buscador de inmediato.
            Los usuarios la envían desde el banner «Sugerir categoría» en la página Explorar.
        </div>
    </div>
</template>
