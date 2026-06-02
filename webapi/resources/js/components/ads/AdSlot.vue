<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    placement: { type: String, default: 'home' },
    /** Siempre muestra el marco de ubicación (útil para ver dónde irá AdSense). */
    showPlaceholder: { type: Boolean, default: true },
});

const auth = useAuthStore();
const config = ref(null);
const banners = ref([]);
const loaded = ref(false);

const PLACEMENT_STYLES = {
    home: {
        label: 'Inicio',
        box: 'bg-amber-50 border-amber-300 text-amber-900',
        badge: 'bg-amber-200/80 text-amber-900',
        icon: 'home',
    },
    search: {
        label: 'Búsqueda',
        box: 'bg-sky-50 border-sky-300 text-sky-900',
        badge: 'bg-sky-200/80 text-sky-900',
        icon: 'travel_explore',
    },
    detail: {
        label: 'Detalle',
        box: 'bg-violet-50 border-violet-300 text-violet-900',
        badge: 'bg-violet-200/80 text-violet-900',
        icon: 'article',
    },
    all: {
        label: 'Global',
        box: 'bg-slate-50 border-slate-300 text-slate-800',
        badge: 'bg-slate-200/80 text-slate-800',
        icon: 'campaign',
    },
};

const placementStyle = computed(
    () => PLACEMENT_STYLES[props.placement] || PLACEMENT_STYLES.all,
);

async function load() {
    if (auth.isAuthenticated && auth.entitlements && auth.entitlements.shows_ads === false) {
        loaded.value = true;
        config.value = null;
        banners.value = [];
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

const showCustom = computed(
    () => banners.value.length > 0 && config.value?.custom?.enabled !== false,
);

const showAdsense = computed(
    () =>
        !showCustom.value
        && config.value?.adsense?.enabled
        && config.value?.adsense?.client_id,
);

const showEmptyPlaceholder = computed(
    () => props.showPlaceholder && loaded.value && !showCustom.value,
);

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
    <div v-if="loaded && (showCustom || showEmptyPlaceholder)" class="ad-slot my-4" :data-ad-placement="placement">
        <!-- Banners personalizados del admin -->
        <template v-if="showCustom">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
                Publicidad · {{ placementStyle.label }}
            </p>
            <a
                v-for="b in banners"
                :key="b.id"
                href="#"
                class="block rounded-xl overflow-hidden border border-slate-200 shadow-sm mb-3 last:mb-0 hover:shadow-md transition-shadow"
                @click.prevent="onBannerClick(b.id, b.link_url)"
            >
                <img :src="b.image_url" :alt="b.title" class="w-full max-h-36 object-cover" loading="lazy" />
            </a>
        </template>

        <!-- Marcador de ubicación AdSense / espacio reservado -->
        <div
            v-else-if="showEmptyPlaceholder"
            class="ad-slot-placeholder rounded-xl border-2 border-dashed p-4 min-h-[96px] flex flex-col items-center justify-center gap-2 text-center"
            :class="placementStyle.box"
            role="note"
            :aria-label="`Espacio publicitario ${placementStyle.label}`"
        >
            <span
                class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full"
                :class="placementStyle.badge"
            >
                <span class="material-symbols-outlined text-[14px]">{{ placementStyle.icon }}</span>
                Espacio publicitario · {{ placementStyle.label }}
            </span>
            <p class="text-xs font-medium opacity-80 m-0">
                <template v-if="showAdsense">AdSense activo — anuncio aquí</template>
                <template v-else>AdSense / banner patrocinado</template>
            </p>
        </div>
    </div>
</template>
