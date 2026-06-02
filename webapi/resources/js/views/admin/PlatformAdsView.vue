<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import AdminServerTable from '@/components/admin/AdminServerTable.vue';

const ads = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 });
const perPage = ref(25);
const loading = ref(false);
const saving = ref(false);
const err = ref('');
const msg = ref('');
const createModalOpen = ref(false);
const imagePreview = ref('');
const fileInput = ref(null);

const placementLabels = {
    home: 'Inicio',
    search: 'Búsqueda',
    detail: 'Detalle de anuncio',
    all: 'Todas las páginas',
};

const columns = [
    { key: 'preview', label: 'Banner' },
    { key: 'title', label: 'Título' },
    { key: 'placement', label: 'Ubicación' },
    { key: 'stats', label: 'Métricas', align: 'right' },
    { key: 'is_active', label: 'Estado', align: 'center' },
];

const defaultForm = () => ({
    title: '',
    link_url: '',
    placement: 'home',
    is_active: true,
    starts_at: '',
    ends_at: '',
});

const form = reactive(defaultForm());
let selectedFile = null;

async function load(page = 1) {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/platform-ads', { auth: true, params: { page, per_page: perPage.value } });
        ads.value = r.data || [];
        meta.value = r.meta || meta.value;
    } catch (e) {
        err.value = e.message;
        ads.value = [];
    } finally {
        loading.value = false;
    }
}

function onPage(p) {
    load(p);
}

function onPerPage(n) {
    perPage.value = n;
    load(1);
}

function resetImagePreview() {
    if (imagePreview.value) URL.revokeObjectURL(imagePreview.value);
    imagePreview.value = '';
    selectedFile = null;
    if (fileInput.value) fileInput.value.value = '';
}

function openCreateModal() {
    Object.assign(form, defaultForm());
    resetImagePreview();
    err.value = '';
    createModalOpen.value = true;
}

function closeCreateModal() {
    createModalOpen.value = false;
    resetImagePreview();
}

function onFileChange(event) {
    const f = event.target.files?.[0];
    resetImagePreview();
    if (!f) return;
    selectedFile = f;
    imagePreview.value = URL.createObjectURL(f);
}

async function submit() {
    if (!selectedFile) {
        err.value = 'Selecciona una imagen para el banner.';
        return;
    }

    msg.value = '';
    err.value = '';
    saving.value = true;

    try {
        const fd = new FormData();
        fd.append('title', form.title.trim());
        if (form.link_url.trim()) fd.append('link_url', form.link_url.trim());
        fd.append('placement', form.placement);
        fd.append('is_active', form.is_active ? '1' : '0');
        if (form.starts_at) fd.append('starts_at', form.starts_at);
        if (form.ends_at) fd.append('ends_at', form.ends_at);
        fd.append('image', selectedFile);

        await api.post('/admin/platform-ads', fd, { auth: true });
        msg.value = 'Banner publicitario creado correctamente.';
        closeCreateModal();
        await load(meta.value.current_page || 1);
    } catch (e) {
        err.value = e.message;
    } finally {
        saving.value = false;
    }
}

onMounted(() => load(1));
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Admin · Marketing"
            title="Publicidad en la web"
            subtitle="Banners de negocios que pagan publicidad. Google AdSense se configura en Configuración → Ads."
        >
            <template #actions>
                <AppButton variant="primary" @click="openCreateModal">
                    <span class="material-symbols-outlined text-lg align-middle mr-1">add_photo_alternate</span>
                    Nuevo banner
                </AppButton>
                <RouterLink
                    :to="{ name: 'admin-settings' }"
                    class="text-sm font-bold text-[#003874] hover:underline no-underline py-2"
                >
                    Configuración Ads →
                </RouterLink>
            </template>
        </PageHeader>

        <AppAlert v-if="err && !createModalOpen" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="msg" type="success" class="mb-4">{{ msg }}</AppAlert>

        <AdminServerTable
            :columns="columns"
            :rows="ads"
            :meta="meta"
            :loading="loading"
            empty-message="Aún no hay banners publicitarios. Crea el primero con «Nuevo banner»."
            @page="onPage"
            @per-page="onPerPage"
        >
            <template #cell-preview="{ row }">
                <img
                    :src="row.image_url"
                    :alt="row.title"
                    class="h-14 w-24 object-cover rounded-lg ring-1 ring-slate-200"
                />
            </template>
            <template #cell-placement="{ row }">
                <span class="text-sm text-slate-700">{{ placementLabels[row.placement] || row.placement }}</span>
            </template>
            <template #cell-stats="{ row }">
                <span class="text-xs text-slate-600">{{ row.impressions }} vistas · {{ row.clicks }} clics</span>
            </template>
            <template #cell-is_active="{ row }">
                <span
                    class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full"
                    :class="row.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                >
                    {{ row.is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </template>
        </AdminServerTable>

        <p class="text-xs text-slate-500 mt-3 text-center">
            Los banners activos se muestran según la ubicación elegida. Puedes definir fechas de inicio y fin al crearlos.
        </p>

        <!-- Modal crear banner -->
        <Teleport to="body">
            <div
                v-if="createModalOpen"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="platform-ad-modal-title"
                @click.self="closeCreateModal"
            >
                <div
                    class="bg-white w-full sm:max-w-lg sm:rounded-2xl shadow-xl flex flex-col max-h-[92vh] rounded-t-2xl"
                    @click.stop
                >
                    <header class="flex items-start justify-between gap-3 px-5 py-4 border-b border-slate-200 shrink-0">
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-500">Publicidad</p>
                            <h2 id="platform-ad-modal-title" class="text-lg font-black text-slate-900">
                                Nuevo banner
                            </h2>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50"
                            aria-label="Cerrar"
                            @click="closeCreateModal"
                        >
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </header>

                    <form class="flex-1 overflow-y-auto px-5 py-4 space-y-4" @submit.prevent="submit">
                        <AppAlert v-if="err && createModalOpen" type="error">{{ err }}</AppAlert>

                        <p class="text-sm text-slate-600">
                            Sube la imagen del anuncio y elige dónde aparecerá. Si indicas URL, el banner será clicable.
                        </p>

                        <label class="block text-sm">
                            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Título</span>
                            <input
                                v-model="form.title"
                                required
                                maxlength="150"
                                placeholder="Ej. Promoción verano — Ferretería López"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                            />
                        </label>

                        <label class="block text-sm">
                            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">URL al hacer clic</span>
                            <input
                                v-model="form.link_url"
                                type="url"
                                maxlength="500"
                                placeholder="https://… (opcional)"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                            />
                        </label>

                        <label class="block text-sm">
                            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Ubicación</span>
                            <select v-model="form.placement" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5">
                                <option v-for="(label, key) in placementLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </label>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <label class="block text-sm">
                                <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Inicio (opcional)</span>
                                <input
                                    v-model="form.starts_at"
                                    type="date"
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                                />
                            </label>
                            <label class="block text-sm">
                                <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Fin (opcional)</span>
                                <input
                                    v-model="form.ends_at"
                                    type="date"
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                                />
                            </label>
                        </div>

                        <div class="block text-sm">
                            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Imagen del banner</span>
                            <div
                                class="mt-2 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/80 p-4 text-center"
                                :class="imagePreview ? 'border-[#003874]/30 bg-sky-50/40' : ''"
                            >
                                <img
                                    v-if="imagePreview"
                                    :src="imagePreview"
                                    alt="Vista previa"
                                    class="mx-auto max-h-36 rounded-lg object-contain ring-1 ring-slate-200 mb-3"
                                />
                                <span
                                    v-else
                                    class="material-symbols-outlined text-4xl text-slate-300 block mb-2"
                                >image</span>
                                <p v-if="!imagePreview" class="text-xs text-slate-500 mb-3">
                                    JPG, PNG o WebP · máx. 5 MB
                                </p>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                    class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#003874] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-[#002a57]"
                                    @change="onFileChange"
                                />
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300" />
                            <span class="text-slate-700">Publicar activo (visible de inmediato)</span>
                        </label>

                        <div class="flex flex-wrap justify-end gap-2 pt-2 border-t border-slate-100">
                            <AppButton variant="ghost" type="button" :disabled="saving" @click="closeCreateModal">
                                Cancelar
                            </AppButton>
                            <AppButton variant="primary" type="submit" :loading="saving">
                                Crear banner
                            </AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
