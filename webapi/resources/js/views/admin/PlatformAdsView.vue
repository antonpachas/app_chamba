<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';

const ads = ref([]);
const form = ref({ title: '', link_url: '', placement: 'home', is_active: true });
const file = ref(null);
const msg = ref('');

async function load() {
    const r = await api.get('/admin/platform-ads', { auth: true });
    ads.value = r.data || [];
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
        <div class="grid sm:grid-cols-2 gap-4">
            <article v-for="a in ads" :key="a.id" class="rounded-xl border border-slate-200 overflow-hidden">
                <img :src="a.image_url" :alt="a.title" class="w-full h-32 object-cover" />
                <div class="p-3 text-sm">
                    <p class="font-bold">{{ a.title }}</p>
                    <p class="text-slate-500">{{ a.placement }} · {{ a.impressions }} vistas</p>
                </div>
            </article>
        </div>
    </div>
</template>
