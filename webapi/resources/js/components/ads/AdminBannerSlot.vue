<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    placement: { type: String, default: 'home' },
});

const auth = useAuthStore();
const banners = ref([]);
const enabled = ref(true);
const loaded = ref(false);

const PLACEMENT_LABELS = {
    home: 'Inicio',
    search: 'Búsqueda',
    detail: 'Detalle',
    all: 'Global',
};

const placementLabel = computed(() => PLACEMENT_LABELS[props.placement] || props.placement);

async function load() {
    if (auth.isAuthenticated && auth.entitlements && auth.entitlements.shows_ads === false) {
        loaded.value = true;
        banners.value = [];
        return;
    }
    try {
        const [cfg, ban] = await Promise.all([
            api.get('/ads/config'),
            api.get('/ads/banners', { params: { placement: props.placement } }),
        ]);
        enabled.value = cfg?.custom?.enabled !== false;
        banners.value = ban.data || [];
    } catch {
        enabled.value = true;
        banners.value = [];
    } finally {
        loaded.value = true;
    }
}

const visible = computed(() => loaded.value && enabled.value && banners.value.length > 0);

async function onBannerClick(id, url) {
    try {
        await api.post(`/ads/banners/${id}/click`, {});
    } catch { /* noop */ }
    if (url) window.open(url, '_blank', 'noopener');
}

onMounted(load);
watch(() => props.placement, load);
watch(() => auth.isAuthenticated, load);
</script>

<template>
    <div v-if="visible" class="admin-banner-slot my-3" :data-admin-ad-placement="placement">
        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-700/80 mb-2 flex items-center gap-1">
            <span class="material-symbols-outlined text-[12px]">campaign</span>
            Patrocinado · {{ placementLabel }}
        </p>
        <a
            v-for="b in banners"
            :key="b.id"
            href="#"
            class="block rounded-xl overflow-hidden border border-amber-200/80 bg-white shadow-sm mb-3 last:mb-0 hover:shadow-md transition-shadow"
            @click.prevent="onBannerClick(b.id, b.link_url)"
        >
            <img :src="b.image_url" :alt="b.title" class="w-full max-h-40 object-cover" loading="lazy" />
        </a>
    </div>
</template>
