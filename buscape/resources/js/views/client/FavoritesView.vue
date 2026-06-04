<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { listingDetailTo } from '@/utils/listingRef';
import { useFavoritesStore } from '@/stores/favorites';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import FavoriteButton from '@/components/common/FavoriteButton.vue';

const favs = useFavoritesStore();
const removingId = ref(null);
const removeErr = ref('');

onMounted(async () => {
    try {
        await favs.load();
    } catch {
        /* error en store */
    }
});

async function removeFavorite(serviceId) {
    const id = Number(serviceId);
    if (!Number.isFinite(id) || id <= 0) return;
    removeErr.value = '';
    removingId.value = id;
    try {
        await favs.toggle(id);
    } catch (e) {
        removeErr.value = e.message || 'No se pudo quitar el favorito.';
    } finally {
        removingId.value = null;
    }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-[#0b1c30] tracking-tight">Mis favoritos</h1>
                <p class="text-slate-500 mt-0.5 text-sm">Anuncios que guardaste para encontrarlos rápido.</p>
            </div>
            <RouterLink
                :to="{ name: 'home' }"
                class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold text-[#003874] hover:underline no-underline"
            >
                <span class="material-symbols-outlined text-[16px]">search</span>
                Explorar más
            </RouterLink>
        </header>

        <AppAlert v-if="favs.error" type="error" class="mb-5">{{ favs.error }}</AppAlert>
        <AppAlert v-else-if="removeErr" type="error" class="mb-5">{{ removeErr }}</AppAlert>

        <div v-if="favs.loading" class="flex items-center justify-center py-20">
            <span class="material-symbols-outlined text-[2rem] text-slate-300 animate-spin">progress_activity</span>
        </div>

        <div v-else-if="!favs.items.length" class="flex flex-col items-center justify-center py-20 text-center">
            <span class="material-symbols-outlined text-[3.5rem] text-slate-200 mb-3">favorite</span>
            <p class="text-base font-semibold text-slate-600 mb-1">Aún no guardaste favoritos</p>
            <p class="text-sm text-slate-400 mb-5">Cuando encuentres un anuncio que te interese, toca el corazón para guardarlo.</p>
            <RouterLink
                :to="{ name: 'home' }"
                class="inline-flex items-center gap-2 rounded-xl bg-[#003874] text-white font-bold px-5 py-2.5 text-sm no-underline hover:bg-[#08458b] transition-colors"
            >
                <span class="material-symbols-outlined text-[18px]">search</span>
                Explorar anuncios
            </RouterLink>
        </div>

        <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article
                v-for="f in favs.items"
                :key="f.favorite_id || f.provider_service_id"
                class="group rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col shadow-sm hover:shadow-md transition-shadow duration-200"
            >
                <!-- Imagen de portada -->
                <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
                    <img
                        v-if="f.cover_image_url"
                        :src="f.cover_image_url"
                        :alt="f.title || 'Anuncio'"
                        class="w-full h-full object-cover object-center group-hover:scale-[1.03] transition-transform duration-300"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-[3rem] text-slate-300">storefront</span>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent pointer-events-none" />

                    <!-- Rating -->
                    <div
                        v-if="f.avg_rating && Number(f.total_reviews) > 0"
                        class="absolute bottom-2.5 left-2.5 flex items-center gap-1 text-white text-xs font-semibold drop-shadow pointer-events-none"
                    >
                        <span class="material-symbols-outlined text-[14px] text-amber-300" style="font-variation-settings:'FILL' 1">star</span>
                        {{ parseFloat(f.avg_rating).toFixed(1) }}
                        <span class="opacity-75">({{ f.total_reviews }})</span>
                    </div>

                    <!-- Botón favorito -->
                    <div class="absolute top-2.5 right-2.5 z-10">
                        <FavoriteButton
                            v-if="f.provider_service_id"
                            :provider-service-id="f.provider_service_id"
                            size="sm"
                        />
                    </div>
                </div>

                <!-- Contenido -->
                <RouterLink
                    v-if="f.provider_service_id"
                    :to="listingDetailTo({ service_id: f.provider_service_id, listing_ref: f.listing_ref })"
                    class="p-4 flex flex-col flex-1 no-underline text-inherit focus-visible:outline-none"
                >
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1 flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[13px]">storefront</span>
                        {{ f.provider_name || '—' }}
                    </p>
                    <h3 class="font-semibold text-[0.95rem] leading-snug line-clamp-2 text-slate-900 group-hover:text-[#003874] transition-colors">
                        {{ f.title || 'Anuncio' }}
                    </h3>
                    <p class="mt-1.5 text-xs text-slate-500 flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[13px] shrink-0">location_on</span>
                        {{ [f.district_name, f.province_name].filter(Boolean).join(', ') || '—' }}
                    </p>
                    <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="text-xs font-medium text-slate-400">Ver detalle</span>
                        <span class="material-symbols-outlined text-[16px] text-[#003874] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                    </div>
                </RouterLink>
            </article>
        </div>
    </div>
</template>
