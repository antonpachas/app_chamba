<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import { providerPublicProfileEnabled } from '@/services/features';
import FavoriteButton from '@/components/common/FavoriteButton.vue';
import ListingImageCarousel from '@/components/listing/ListingImageCarousel.vue';
import { LISTING_PLACEHOLDER } from '@/utils/placeholderImage';
import { listingDetailTo } from '@/utils/listingRef';

const props = defineProps({
    open: { type: Boolean, default: false },
    listingId: { type: [Number, String], default: null },
});
const emit = defineEmits(['close']);

const auth = useAuthStore();
const loading = ref(false);
const error = ref('');
const listing = ref(null);

watch(
    () => [props.open, props.listingId],
    async ([isOpen, id]) => {
        if (!isOpen || !id) {
            listing.value = null;
            error.value = '';
            return;
        }
        loading.value = true;
        error.value = '';
        try {
            const r = await api.get(`/listings/${id}`, { auth: true });
            listing.value = r.data || null;
            if (!listing.value) error.value = 'No se pudo cargar el anuncio.';
        } catch (e) {
            listing.value = null;
            error.value = e.message || 'Anuncio no disponible.';
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

function close() {
    emit('close');
}

const galleryImages = computed(() => {
    const l = listing.value;
    if (!l) return [];
    const urls = [];
    if (l.cover_image_url) urls.push(l.cover_image_url);
    if (Array.isArray(l.images)) {
        for (const u of l.images) {
            if (u && !urls.includes(u)) urls.push(u);
        }
    }
    if (!urls.length && l.service_id) {
        urls.push(LISTING_PLACEHOLDER);
    }
    return urls;
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
            role="dialog"
            aria-modal="true"
            @click.self="close"
        >
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="close"></div>
            <div class="relative w-full sm:max-w-lg max-h-[90vh] overflow-y-auto bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl border border-slate-200">
                <div class="sticky top-0 flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-white/95 backdrop-blur z-10">
                    <h2 class="text-base font-bold text-[#0b1c30]">Anuncio</h2>
                    <button
                        type="button"
                        class="w-9 h-9 rounded-full hover:bg-slate-100 text-slate-600 font-bold"
                        aria-label="Cerrar"
                        @click="close"
                    >×</button>
                </div>

                <div class="p-4">
                    <p v-if="loading" class="text-slate-500 text-sm py-8 text-center">Cargando…</p>
                    <p v-else-if="error" class="text-red-600 text-sm py-8 text-center">{{ error }}</p>
                    <template v-else-if="listing">
                        <div v-if="!listing.is_visible" class="mb-3 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-900">
                            Este anuncio ya no está visible en la búsqueda pública, pero puedes verlo porque tienes una solicitud asociada.
                        </div>
                        <div class="relative mb-4 h-44 rounded-xl overflow-hidden bg-slate-100">
                            <ListingImageCarousel
                                :images="galleryImages"
                                :alt="listing.title || 'Anuncio'"
                            />
                            <div
                                v-if="listing.service_id"
                                class="absolute bottom-2 right-2 z-20"
                                @click.stop
                            >
                                <FavoriteButton :provider-service-id="listing.service_id" size="sm" />
                            </div>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">{{ listing.category_name }}</p>
                        <h3 class="text-xl font-black text-slate-900 mt-1">{{ listing.title }}</h3>
                        <p class="text-sm font-semibold text-slate-700 mt-1">{{ listing.location_label || listing.provider_name }}</p>
                        <p v-if="listing.district_name || listing.address_text" class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            {{
                                [
                                    [listing.district_name, listing.province_name, listing.department_name].filter(Boolean).join(' · '),
                                    listing.address_text,
                                ].filter(Boolean).join(' — ')
                            }}
                        </p>
                        <p v-if="listing.description" class="text-sm text-slate-700 mt-3 line-clamp-4 whitespace-pre-wrap">{{ listing.description }}</p>
                        <p class="text-lg font-black text-[#003874] mt-3">
                            <Money :amount="listing.base_price" />
                            <span v-if="listing.price_type" class="text-xs text-slate-500 font-medium ml-1">({{ listing.price_type }})</span>
                        </p>
                        <RouterLink
                            v-if="providerPublicProfileEnabled() && listing.provider_profile_id"
                            :to="{ name: 'provider-public', params: { id: listing.provider_profile_id } }"
                            class="inline-flex items-center gap-1 text-xs font-bold text-[#003874] hover:underline mb-3"
                            @click.stop
                        >
                            <span class="material-symbols-outlined text-[14px]">storefront</span>
                            Ver perfil del negocio
                        </RouterLink>
                        <p class="text-xs text-slate-500 mt-3">
                            Teléfono y WhatsApp están en la página completa del anuncio.
                        </p>
                        <div class="mt-5 flex flex-col sm:flex-row gap-2">
                            <RouterLink
                                :to="listingDetailTo(listing)"
                                class="flex-1 inline-flex justify-center rounded-full bg-[#003874] text-white font-bold px-4 py-2.5 text-sm no-underline hover:bg-[#08458b]"
                                @click="close"
                            >
                                Ver anuncio y contactar
                            </RouterLink>
                            <AppButton variant="ghost" class="flex-1" @click="close">Cerrar</AppButton>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>
