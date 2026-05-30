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
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis favoritos</h1>
            <p class="text-slate-600 mt-1">Anuncios que guardaste para encontrarlos rápido.</p>
        </header>

        <AppAlert v-if="favs.error" type="error" class="mb-6">{{ favs.error }}</AppAlert>
        <AppAlert v-else-if="removeErr" type="error" class="mb-6">{{ removeErr }}</AppAlert>

        <p v-if="favs.loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!favs.items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no guardaste favoritos.
            <RouterLink :to="{ name: 'home' }" class="text-[#003874] font-bold hover:underline ml-1">Explorar anuncios</RouterLink>
        </div>
        <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article
                v-for="f in favs.items"
                :key="f.favorite_id || f.provider_service_id"
                class="rounded-2xl border border-slate-200 bg-white overflow-hidden flex flex-col"
            >
                <div
                    v-if="f.cover_image_url"
                    class="aspect-[16/10] bg-slate-100 bg-cover bg-center"
                    :style="{ backgroundImage: `url(${f.cover_image_url})` }"
                />
                <div class="p-5 flex flex-col flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#003874]">{{ f.provider_name || '—' }}</p>
                    <h3 class="text-lg font-bold text-slate-900 mt-1">{{ f.title || 'Anuncio' }}</h3>
                    <p class="text-sm text-slate-600 mt-1">
                        {{ [f.district_name, f.province_name].filter(Boolean).join(', ') || '—' }}
                    </p>
                    <div class="mt-4 flex justify-between items-center gap-2">
                        <span class="text-sm text-slate-700">
                            ★ {{ f.avg_rating ?? '—' }}
                            <span class="text-slate-500 text-xs">({{ f.total_reviews ?? 0 }})</span>
                        </span>
                        <FavoriteButton
                            v-if="f.provider_service_id"
                            :provider-service-id="f.provider_service_id"
                            size="sm"
                        />
                    </div>
                    <div class="mt-4 flex flex-col gap-2">
                        <RouterLink
                            v-if="f.provider_service_id"
                            :to="listingDetailTo({ service_id: f.provider_service_id, listing_ref: f.listing_ref })"
                            class="text-center rounded-full border-2 border-[#003874]/30 text-[#003874] font-bold text-sm py-2 no-underline hover:bg-[#003874]/5"
                        >
                            Ver anuncio
                        </RouterLink>
                        <AppButton
                            variant="ghost"
                            size="sm"
                            :disabled="removingId === f.provider_service_id"
                            @click="removeFavorite(f.provider_service_id)"
                        >
                            {{ removingId === f.provider_service_id ? 'Quitando…' : 'Quitar de favoritos' }}
                        </AppButton>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
