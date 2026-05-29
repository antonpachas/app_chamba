<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import AdminServerTable from '@/components/admin/AdminServerTable.vue';

const tabs = [
    { id: 'departments', label: 'Departamentos' },
    { id: 'provinces', label: 'Provincias' },
    { id: 'districts', label: 'Distritos' },
];

const activeTab = ref('departments');
const err = ref('');
const ok = ref('');
const loading = ref(false);
const rows = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 });
const perPage = ref(25);

const filters = reactive({
    q: '',
    is_active: '',
    department_id: '',
    province_id: '',
});

const deptOptions = ref([]);
const provOptions = ref([]);

const modal = ref({ open: false, mode: 'create', tab: 'departments', row: null });
const form = reactive({
    name: '',
    ubigeo_code: '',
    ubigeo: '',
    latitude: '',
    longitude: '',
    is_active: true,
    department_id: '',
    province_id: '',
});
const saving = ref(false);

const columns = computed(() => {
    if (activeTab.value === 'departments') {
        return [
            { key: 'name', label: 'Nombre' },
            { key: 'ubigeo_code', label: 'UBIGEO (2)' },
            { key: 'coords', label: 'Coordenadas', align: 'right' },
            { key: 'is_active', label: 'Estado', align: 'center' },
            { key: 'actions', label: '', align: 'right' },
        ];
    }
    if (activeTab.value === 'provinces') {
        return [
            { key: 'name', label: 'Nombre' },
            { key: 'department_name', label: 'Departamento' },
            { key: 'ubigeo_code', label: 'UBIGEO (4)' },
            { key: 'coords', label: 'Coordenadas', align: 'right' },
            { key: 'is_active', label: 'Estado', align: 'center' },
            { key: 'actions', label: '', align: 'right' },
        ];
    }
    return [
        { key: 'name', label: 'Distrito' },
        { key: 'department_name', label: 'Departamento' },
        { key: 'province_name', label: 'Provincia' },
        { key: 'ubigeo', label: 'UBIGEO (6)' },
        { key: 'coords', label: 'Coordenadas', align: 'right' },
        { key: 'is_active', label: 'Estado', align: 'center' },
        { key: 'actions', label: '', align: 'right' },
    ];
});

const endpoint = computed(() => `/admin/geo/${activeTab.value}`);

async function loadDeptOptions() {
    try {
        const r = await api.get('/admin/geo/departments', { auth: true, params: { per_page: 100, is_active: '' } });
        deptOptions.value = r.data || [];
    } catch {
        deptOptions.value = [];
    }
}

async function loadProvOptions() {
    if (!filters.department_id && activeTab.value === 'districts' && !form.department_id) {
        provOptions.value = [];
        return;
    }
    const deptId = filters.department_id || form.department_id;
    if (!deptId) {
        provOptions.value = [];
        return;
    }
    try {
        const r = await api.get('/admin/geo/provinces', {
            auth: true,
            params: { department_id: deptId, per_page: 200, is_active: '' },
        });
        provOptions.value = r.data || [];
    } catch {
        provOptions.value = [];
    }
}

async function load(page = 1) {
    loading.value = true;
    err.value = '';
    try {
        const params = { page, per_page: perPage.value };
        if (filters.q) params.q = filters.q;
        if (filters.is_active !== '') params.is_active = filters.is_active;
        if (activeTab.value === 'provinces' && filters.department_id) {
            params.department_id = filters.department_id;
        }
        if (activeTab.value === 'districts') {
            if (filters.province_id) params.province_id = filters.province_id;
            else if (filters.department_id) params.department_id = filters.department_id;
        }
        const r = await api.get(endpoint.value, { auth: true, params });
        rows.value = r.data || [];
        meta.value = r.meta || meta.value;
    } catch (e) {
        err.value = e.message;
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.name = '';
    form.ubigeo_code = '';
    form.ubigeo = '';
    form.latitude = '';
    form.longitude = '';
    form.is_active = true;
    form.department_id = filters.department_id || '';
    form.province_id = filters.province_id || '';
}

function openCreate() {
    resetForm();
    modal.value = { open: true, mode: 'create', tab: activeTab.value, row: null };
    if (activeTab.value !== 'departments') loadDeptOptions();
    if (activeTab.value === 'districts' && form.department_id) loadProvOptions();
}

function openEdit(row) {
    modal.value = { open: true, mode: 'edit', tab: activeTab.value, row };
    form.name = row.name || '';
    form.ubigeo_code = row.ubigeo_code || '';
    form.ubigeo = row.ubigeo || '';
    form.latitude = String(row.latitude ?? '');
    form.longitude = String(row.longitude ?? '');
    form.is_active = row.is_active !== false;
    form.department_id = String(row.department_id || filters.department_id || '');
    form.province_id = String(row.province_id || filters.province_id || '');
    if (activeTab.value !== 'departments') loadDeptOptions();
    if (activeTab.value === 'districts') loadProvOptions();
}

function closeModal() {
    modal.value.open = false;
}

async function save() {
    saving.value = true;
    err.value = '';
    ok.value = '';
    const tab = modal.value.tab;
    const payload = {
        name: form.name.trim(),
        latitude: parseFloat(form.latitude),
        longitude: parseFloat(form.longitude),
        is_active: form.is_active,
    };
    if (tab === 'departments') {
        payload.ubigeo_code = form.ubigeo_code || null;
    } else if (tab === 'provinces') {
        payload.department_id = parseInt(form.department_id, 10);
        payload.ubigeo_code = form.ubigeo_code || null;
    } else {
        payload.province_id = parseInt(form.province_id, 10);
        payload.ubigeo = form.ubigeo || null;
    }
    try {
        if (modal.value.mode === 'create') {
            const r = await api.post(`/admin/geo/${tab}`, payload, { auth: true });
            ok.value = r.message || 'Registro creado.';
        } else {
            const id = modal.value.row.id;
            const r = await api.put(`/admin/geo/${tab}/${id}`, payload, { auth: true });
            ok.value = r.message || 'Registro actualizado.';
        }
        closeModal();
        await load(meta.value.current_page || 1);
    } catch (e) {
        err.value = e.message;
    } finally {
        saving.value = false;
    }
}

async function toggleActive(row) {
    const tab = activeTab.value;
    const payload = {
        name: row.name,
        latitude: row.latitude,
        longitude: row.longitude,
        is_active: !row.is_active,
    };
    if (tab === 'provinces') payload.department_id = row.department_id;
    if (tab === 'districts') payload.province_id = row.province_id;
    if (tab === 'departments') payload.ubigeo_code = row.ubigeo_code;
    if (tab === 'provinces') payload.ubigeo_code = row.ubigeo_code;
    if (tab === 'districts') payload.ubigeo = row.ubigeo;
    try {
        await api.put(`/admin/geo/${tab}/${row.id}`, payload, { auth: true });
        ok.value = row.is_active ? 'Ubicación deshabilitada.' : 'Ubicación habilitada.';
        await load(meta.value.current_page || 1);
    } catch (e) {
        err.value = e.message;
    }
}

function onPage(p) {
    load(p);
}

function onPerPage(n) {
    perPage.value = n;
    load(1);
}

watch(activeTab, () => {
    filters.q = '';
    if (activeTab.value === 'departments') {
        filters.department_id = '';
        filters.province_id = '';
    }
    load(1);
});

watch(
    () => filters.department_id,
    () => {
        filters.province_id = '';
        if (activeTab.value === 'provinces' || activeTab.value === 'districts') {
            loadProvOptions();
            load(1);
        }
    },
);

watch(
    () => filters.province_id,
    () => {
        if (activeTab.value === 'districts') load(1);
    },
);

watch(
    () => form.department_id,
    () => {
        if (modal.value.open) {
            form.province_id = '';
            loadProvOptions();
        }
    },
);

onMounted(async () => {
    await loadDeptOptions();
    await load(1);
});
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Admin · Directorio"
            title="Ubicación (Perú)"
            subtitle="Departamentos, provincias y distritos con UBIGEO y coordenadas. Deshabilita zonas que no quieras mostrar en el buscador."
            class="mb-6"
        />

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="flex flex-wrap gap-2 mb-6">
            <button
                v-for="t in tabs"
                :key="t.id"
                type="button"
                class="px-4 py-2 rounded-full text-sm font-bold border transition"
                :class="
                    activeTab === t.id
                        ? 'bg-[#003874] text-white border-[#003874]'
                        : 'bg-white text-slate-700 border-slate-200'
                "
                @click="activeTab = t.id"
            >
                {{ t.label }}
            </button>
        </div>

        <AdminServerTable
            :columns="columns"
            :rows="rows"
            :meta="meta"
            :loading="loading"
            empty-message="No hay registros con estos filtros."
            @page="onPage"
            @per-page="onPerPage"
        >
            <template #toolbar>
                <div class="flex flex-wrap items-end gap-3 w-full">
                    <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                        Buscar
                        <input
                            v-model="filters.q"
                            type="search"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-sm min-w-[10rem]"
                            placeholder="Nombre o ubigeo…"
                            @keydown.enter="load(1)"
                        />
                    </label>
                    <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                        Estado
                        <select v-model="filters.is_active" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" @change="load(1)">
                            <option value="">Todos</option>
                            <option value="true">Activos</option>
                            <option value="false">Inactivos</option>
                        </select>
                    </label>
                    <label
                        v-if="activeTab === 'provinces' || activeTab === 'districts'"
                        class="flex flex-col gap-1 text-xs font-semibold text-slate-600"
                    >
                        Departamento
                        <select
                            v-model="filters.department_id"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-sm max-w-[12rem]"
                            @change="load(1)"
                        >
                            <option value="">Todos</option>
                            <option v-for="d in deptOptions" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                        </select>
                    </label>
                    <label v-if="activeTab === 'districts'" class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                        Provincia
                        <select
                            v-model="filters.province_id"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-sm max-w-[12rem]"
                            :disabled="!filters.department_id"
                            @change="load(1)"
                        >
                            <option value="">Todas</option>
                            <option v-for="p in provOptions" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                        </select>
                    </label>
                    <AppButton variant="outline" class="!py-2" @click="load(1)">Filtrar</AppButton>
                    <AppButton variant="primary" class="!py-2 ml-auto" @click="openCreate">Agregar</AppButton>
                </div>
            </template>

            <template #cell-coords="{ row }">
                <span class="font-mono text-xs text-slate-600">{{ row.latitude }}, {{ row.longitude }}</span>
            </template>
            <template #cell-is_active="{ row }">
                <span
                    class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full"
                    :class="row.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                >
                    {{ row.is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button type="button" class="text-xs font-bold text-[#003874] hover:underline" @click="openEdit(row)">
                        Editar
                    </button>
                    <button
                        type="button"
                        class="text-xs font-bold hover:underline"
                        :class="row.is_active ? 'text-amber-700' : 'text-emerald-700'"
                        @click="toggleActive(row)"
                    >
                        {{ row.is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>
            </template>
        </AdminServerTable>

        <div class="mt-6 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
            <span class="material-symbols-outlined text-[#003874] align-middle text-base mr-1">info</span>
            Para cargar todo el Perú (~1 893 distritos), ejecuta en el servidor:
            <code class="text-xs bg-white px-1 rounded">php artisan chamba:import-peru-ubigeo</code>
            (o desde migración local tras desplegar).
        </div>

        <!-- Modal crear/editar -->
        <div
            v-if="modal.open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50"
            @click.self="closeModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl p-6 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">
                    {{ modal.mode === 'create' ? 'Nuevo' : 'Editar' }}
                    {{ modal.tab === 'departments' ? 'departamento' : modal.tab === 'provinces' ? 'provincia' : 'distrito' }}
                </h2>
                <label v-if="modal.tab !== 'departments'" class="block text-sm">
                    <span class="font-semibold text-slate-700">Departamento</span>
                    <select v-model="form.department_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
                        <option value="">Selecciona…</option>
                        <option v-for="d in deptOptions" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                    </select>
                </label>
                <label v-if="modal.tab === 'districts'" class="block text-sm">
                    <span class="font-semibold text-slate-700">Provincia</span>
                    <select
                        v-model="form.province_id"
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2"
                        required
                        :disabled="!form.department_id"
                    >
                        <option value="">Selecciona…</option>
                        <option v-for="p in provOptions" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-slate-700">Nombre</span>
                    <input v-model="form.name" type="text" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required />
                </label>
                <label v-if="modal.tab === 'departments'" class="block text-sm">
                    <span class="font-semibold text-slate-700">Código UBIGEO (2 dígitos)</span>
                    <input v-model="form.ubigeo_code" type="text" maxlength="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 font-mono" />
                </label>
                <label v-if="modal.tab === 'provinces'" class="block text-sm">
                    <span class="font-semibold text-slate-700">Código UBIGEO (4 dígitos)</span>
                    <input v-model="form.ubigeo_code" type="text" maxlength="4" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 font-mono" />
                </label>
                <label v-if="modal.tab === 'districts'" class="block text-sm">
                    <span class="font-semibold text-slate-700">UBIGEO distrito (6 dígitos)</span>
                    <input v-model="form.ubigeo" type="text" maxlength="6" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 font-mono" />
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-700">Latitud</span>
                        <input v-model="form.latitude" type="number" step="any" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required />
                    </label>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-700">Longitud</span>
                        <input v-model="form.longitude" type="number" step="any" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required />
                    </label>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded" />
                    Activo en el buscador
                </label>
                <div class="flex gap-2 justify-end pt-2">
                    <AppButton variant="outline" @click="closeModal">Cancelar</AppButton>
                    <AppButton variant="primary" :loading="saving" @click="save">Guardar</AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
