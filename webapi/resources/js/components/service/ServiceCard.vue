<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { listingDetailTo } from '@/utils/listingRef';
import { providerPublicProfileEnabled } from '@/services/features';
import FavoriteButton from '@/components/common/FavoriteButton.vue';
import OpenHoursBadge from '@/components/common/OpenHoursBadge.vue';
import ListingImageCarousel from '@/components/listing/ListingImageCarousel.vue';
import { formatDistanceKm } from '@/utils/formatDistance';

const props = defineProps({
    service: { type: Object, required: true },
    featured: { type: Boolean, default: false },
    showProviderLink: { type: Boolean, default: true },
});

const id = computed(() => {
    const raw = props.service?.service_id ?? props.service?.id;
    const n = Number(raw);
    return Number.isFinite(n) && n > 0 ? n : null;
});
const providerProfileId = computed(() => {
    const pid = props.service.provider_profile_id;
    return pid != null && pid !== '' ? Number(pid) : null;
});

const showProfileLink = computed(
    () => props.showProviderLink && providerPublicProfileEnabled() && providerProfileId.value,
);

const galleryImages = computed(() => {
    const urls = [];
    const cover = props.service.cover_image_url;
    const list = props.service.images || [];
    if (cover) urls.push(cover);
    if (Array.isArray(list)) {
        for (const u of list) {
            if (u && !urls.includes(u)) urls.push(u);
        }
    }
    if (!urls.length) {
        urls.push(`https://picsum.photos/seed/chamba_svc_${id.value}/800/480`);
    }
    return urls;
});

const hasMultipleImages = computed(() => galleryImages.value.length > 1);

const ratingNum = computed(() => {
    const v = parseFloat(String(props.service.avg_rating ?? '').replace(',', '.'));
    const reviews = Number(props.service.total_reviews) || 0;
    if (reviews <= 0 || !Number.isFinite(v)) return null;
    return { v: v.toFixed(1), n: reviews };
});

const distanceLabel = computed(() => formatDistanceKm(props.service.distance_km));

const locationLine = computed(() => {
    const geo = [props.service.district_name, props.service.province_name, props.service.department_name].filter(Boolean);
    const geoStr = geo.join(' · ');
    const addr = String(props.service.address_text || '').trim();
    if (geoStr && addr) return `${geoStr} — ${addr}`;
    return geoStr || addr || '—';
});

const localName = computed(() => {
    const label = String(props.service.location_label || '').trim();
    if (label) return label;
    return props.service.provider_name || '—';
});

const priceDisplay = computed(() => {
    const has = props.service.base_price != null && String(props.service.base_price).trim() !== '';
    if (has) return `S/ ${String(props.service.base_price).trim()}`;
    return 'Consultar';
});
</script>

<template>
    <article
        class="group block rounded-xl border border-slate-200 bg-white overflow-hidden text-inherit transition-all duration-200 hover:shadow-[0_4px_20px_-4px_rgba(0,56,116,0.18)] hover:-translate-y-0.5 hover:border-slate-300 focus-within:ring-2 focus-within:ring-[#003874]/30 focus-within:ring-offset-2"
    >
        <div
            class="relative aspect-[4/3] bg-slate-100 overflow-hidden"
            @click.stop
        >
            <ListingImageCarousel
                :images="galleryImages"
                :alt="service.title || 'Anuncio'"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent pointer-events-none" />

            <span
                v-if="service.listing_type === 'promocion' || featured"
                class="absolute top-2.5 left-2.5 z-10 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider bg-gradient-to-r from-amber-400 to-amber-500 text-white px-2 py-0.5 rounded-full pointer-events-none shadow-sm"
            >
                <span class="material-symbols-outlined text-[10px] leading-none" style="font-variation-settings:'FILL' 1">star</span>
                Destacado
            </span>
            <span
                v-else-if="service.is_pro"
                class="absolute top-2.5 left-2.5 z-10 text-[10px] font-bold uppercase tracking-wider bg-[#003874] text-white px-2 py-0.5 rounded-full pointer-events-none shadow-sm"
            >
                Pro
            </span>

            <div v-if="id" class="absolute top-2.5 right-2.5 z-20">
                <FavoriteButton :provider-service-id="id" size="sm" />
            </div>

            <div
                v-if="ratingNum"
                class="absolute left-2.5 z-10 flex items-center gap-1 text-white text-xs font-medium drop-shadow-sm pointer-events-none"
                :class="hasMultipleImages ? 'bottom-8' : 'bottom-2.5'"
            >
                <span class="material-symbols-outlined text-[14px] text-amber-300" style="font-variation-settings: 'FILL' 1">star</span>
                {{ ratingNum.v }} <span class="opacity-80">({{ ratingNum.n }})</span>
            </div>
            <span
                v-if="distanceLabel"
                class="absolute right-2.5 z-10 text-[10px] font-medium text-white bg-black/50 backdrop-blur-sm px-2 py-0.5 rounded pointer-events-none"
                :class="hasMultipleImages ? 'bottom-8' : 'bottom-2.5'"
            >
                {{ distanceLabel }}
            </span>
        </div>

        <RouterLink
            :to="listingDetailTo(service)"
            class="block p-4 no-underline text-inherit focus-visible:outline-none"
        >
            <p v-if="service.category_name" class="text-xs font-medium text-slate-500 mb-1 truncate">
                {{ service.category_name }}
            </p>
            <h3 class="font-semibold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#003874] transition-colors">
                {{ service.title }}
            </h3>
            <p class="text-sm text-slate-600 mt-1 truncate">{{ localName }}</p>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                <span class="inline-flex items-center gap-0.5 min-w-0 truncate">
                    <span class="material-symbols-outlined text-[16px] shrink-0">location_on</span>
                    <span class="truncate">{{ locationLine }}</span>
                </span>
                <OpenHoursBadge
                    v-if="service.is_open_now !== null && service.is_open_now !== undefined"
                    :is-open="service.is_open_now"
                    compact
                />
            </div>

            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                <span class="text-[0.9375rem] font-bold text-slate-900">{{ priceDisplay }}</span>
                <span class="inline-flex items-center gap-0.5 text-xs font-semibold text-slate-400 group-hover:text-[#003874] transition-colors">
                    Ver detalle
                    <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </span>
            </div>

            <RouterLink
                v-if="showProfileLink"
                :to="{ name: 'provider-public', params: { id: providerProfileId } }"
                class="mt-2 inline-block text-xs text-slate-500 hover:text-[#003874] relative z-10 no-underline"
                @click.stop
            >
                Ver todos los anuncios del negocio
            </RouterLink>
        </RouterLink>
    </article>
</template>
