<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { listingDetailTo } from '@/utils/listingRef';
import { providerPublicProfileEnabled } from '@/services/features';
import FavoriteButton from '@/components/common/FavoriteButton.vue';
import OpenHoursBadge from '@/components/common/OpenHoursBadge.vue';
import { formatDistanceKm } from '@/utils/formatDistance';

const props = defineProps({
    service: { type: Object, required: true },
    featured: { type: Boolean, default: false },
    showProviderLink: { type: Boolean, default: true },
});

const id = computed(() => Number(props.service.service_id));
const providerProfileId = computed(() => {
    const pid = props.service.provider_profile_id;
    return pid != null && pid !== '' ? Number(pid) : null;
});

const showProfileLink = computed(
    () => props.showProviderLink && providerPublicProfileEnabled() && providerProfileId.value,
);

const image = computed(() => {
    return props.service.cover_image_url
        || (props.service.images && props.service.images[0])
        || `https://picsum.photos/seed/chamba_svc_${id.value}/800/480`;
});

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
    <RouterLink
        :to="listingDetailTo(service)"
        class="group block rounded-xl border border-slate-200 bg-white overflow-hidden no-underline text-inherit transition-shadow hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#003874]/30 focus-visible:ring-offset-2"
    >
        <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
            <img
                :src="image"
                alt=""
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                loading="lazy"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent pointer-events-none" />

            <span
                v-if="service.listing_type === 'promocion' || featured"
                class="absolute top-2.5 left-2.5 text-[10px] font-medium uppercase tracking-wide bg-white/95 text-slate-800 px-2 py-0.5 rounded"
            >
                Destacado
            </span>
            <span
                v-else-if="service.is_pro"
                class="absolute top-2.5 left-2.5 text-[10px] font-medium uppercase tracking-wide bg-[#003874] text-white px-2 py-0.5 rounded"
            >
                Pro
            </span>

            <div v-if="id" class="absolute top-2.5 right-2.5 z-10" @click.stop>
                <FavoriteButton :provider-service-id="id" size="sm" />
            </div>

            <div
                v-if="ratingNum"
                class="absolute bottom-2.5 left-2.5 flex items-center gap-1 text-white text-xs font-medium drop-shadow-sm"
            >
                <span class="material-symbols-outlined text-[14px] text-amber-300" style="font-variation-settings: 'FILL' 1">star</span>
                {{ ratingNum.v }} <span class="opacity-80">({{ ratingNum.n }})</span>
            </div>
            <span
                v-if="distanceLabel"
                class="absolute bottom-2.5 right-2.5 text-[10px] font-medium text-white bg-black/50 backdrop-blur-sm px-2 py-0.5 rounded"
            >
                {{ distanceLabel }}
            </span>
        </div>

        <div class="p-4">
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
                <span class="text-base font-semibold text-slate-900">{{ priceDisplay }}</span>
                <span class="text-xs font-medium text-slate-400 group-hover:text-[#003874] transition-colors">
                    Ver detalle →
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
        </div>
    </RouterLink>
</template>
