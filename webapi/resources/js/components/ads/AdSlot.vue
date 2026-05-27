<script setup>
import { onMounted, ref, watch } from 'vue';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    placement: { type: String, default: 'home' },
});

const auth = useAuthStore();
const config = ref(null);
const banners = ref([]);
const loaded = ref(false);

async function load() {
    if (auth.isAuthenticated && auth.entitlements && auth.entitlements.shows_ads === false) {
        loaded.value = true;
        return;
    }
    try {
        const [cfg, ban] = await Promise.all([
            api.get('/ads/config'),
            api.get('/ads/banners', { params: { placement: props.placement } }),
        ]);
        config.value = cfg;
        banners.value = ban.data || [];
    } catch {
        config.value = null;
        banners.value = [];
    } finally {
        loaded.value = true;
    }
}

async function onBannerClick(id, url) {
    try {
        await api.post(`/ads/banners/${id}/click`, {});
    } catch { /* noop */ }
    if (url) window.open(url, '_blank', 'noopener');
}

onMounted(load);
watch(() => props.placement, load);
</script>

<template>
    <div v-if="loaded" class="ad-slot my-4">
        <template v-if="banners.length && (config?.custom?.enabled !== false)">
            <a
                v-for="b in banners"
                :key="b.id"
                href="#"
                class="block rounded-xl overflow-hidden border border-slate-200 shadow-sm mb-3"
                @click.prevent="onBannerClick(b.id, b.link_url)"
            >
                <img :src="b.image_url" :alt="b.title" class="w-full max-h-32 object-cover" />
            </a>
        </template>
        <div
            v-else-if="config?.adsense?.enabled && config?.adsense?.client_id"
            class="rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-4 text-center text-xs text-slate-500 min-h-[90px] flex items-center justify-center"
        >
            Espacio publicitario (AdSense)
        </div>
    </div>
</template>
