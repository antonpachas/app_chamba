<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';
import AdminServerTable from '@/components/admin/AdminServerTable.vue';

const ads = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 });
const perPage = ref(25);
const loading = ref(false);
const form = ref({ title: '', link_url: '', placement: 'home', is_active: true });
const file = ref(null);
const msg = ref('');

const columns = [
    { key: 'preview', label: 'Banner' },
    { key: 'title', label: 'Título' },
    { key: 'placement', label: 'Ubicación' },
    { key: 'stats', label: 'Métricas', align: 'right' },
    { key: 'is_active', label: 'Estado', align: 'center' },
];

async function load(page = 1) {
    loading.value = true;
    try {
        const r = await api.get('/admin/platform-ads', { auth: true, params: { page, per_page: perPage.value } });
        ads.value = r.data || [];
        meta.value = r.meta || meta.value;
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

async function submit() {
    const fd = new FormData();
    fd.append('title', form.value.title);
    if (form.value.link_url) fd.append('link_url', form.value.link_url);
    fd.append('placement', form.value.placement);
    fd.append('is_active', form.value.is_active ? '1' : '0');
    if (file.value) fd.append('image', file.value);
    await api.post('/admin/platform-ads', fd, { auth: true });
    msg.value = 'Banner creado.';
    form.value.title = '';
    file.value = null;
    await load();
}

onMounted(load);
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-[#0b1c30] mb-2">Publicidad en la web</h1>
        <p class="text-slate-600 mb-6">Banners de negocios que pagan publicidad. AdSense se configura en Configuración → Ads.</p>
        <form class="rounded-2xl border border-slate-200 bg-white p-6 mb-8 space-y-3" @submit.prevent="submit">
            <input v-model="form.title" placeholder="Título" class="w-full rounded-lg border border-slate-200 px-3 py-2" required />
            <input v-model="form.link_url" placeholder="URL al hacer clic" class="w-full rounded-lg border border-slate-200 px-3 py-2" />
            <select v-model="form.placement" class="w-full rounded-lg border border-slate-200 px-3 py-2">
                <option value="home">Inicio</option>
                <option value="search">Búsqueda</option>
                <option value="detail">Detalle anuncio</option>
                <option value="all">Todas</option>
            </select>
            <input type="file" accept="image/*" @change="file = $event.target.files?.[0]" required />
            <button type="submit" class="rounded-lg bg-[#003874] text-white px-4 py-2 font-bold">Subir banner</button>
            <p v-if="msg" class="text-sm text-emerald-700">{{ msg }}</p>
        </form>
        <AdminServerTable
            :columns="columns"
            :rows="ads"
            :meta="meta"
            :loading="loading"
            empty-message="Aún no hay banners publicitarios."
            @page="onPage"
            @per-page="onPerPage"
        >
            <template #cell-preview="{ row }">
                <img :src="row.image_url" :alt="row.title" class="h-14 w-24 object-cover rounded-lg ring-1 ring-slate-200" />
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
    </div>
</template>
