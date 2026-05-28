<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { providerPublicProfileEnabled } from '@/services/features';
import FavoriteButton from '@/components/common/FavoriteButton.vue';
import ListingContactActions from '@/components/listing/ListingContactActions.vue';
import { hasListingContact } from '@/utils/whatsapp';

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
    return v.toFixed(1);
});

const locationLine = computed(() => {
    const a = props.service.district_name;
    const b = props.service.province_name;
    return [a, b].filter(Boolean).join(', ');
});

const priceFooter = computed(() => {
    const pt = String(props.service.price_type || '');
    const has = props.service.base_price != null && String(props.service.base_price).trim() !== '';
    const pNum = has ? String(props.service.base_price).trim() : '';
    const ptLabels = { cotizar: 'COTIZAR', desde: 'DESDE', fijo: 'PRECIO FIJO' };
    const label = ptLabels[pt] || 'PRECIO';
    const value = has ? `S/\u00A0${pNum}` : 'Consultar';
    const cta = pt === 'cotizar' ? 'Cotizar' : pt === 'fijo' ? 'Ver más' : 'Consultar';
    return { label, value, cta };
});

const showContact = computed(
    () => hasListingContact(props.service) || !!props.service?.contact_requires_login,
);
</script>

<template>
    <RouterLink
        :to="{ name: 'listing-detail', params: { id } }"
        class="group block ui-card ui-card-hover overflow-hidden no-underline text-inherit focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-chamba-700/40 focus-visible:ring-offset-2"
    >
        <div class="relative h-48 bg-slate-200 overflow-hidden">
            <img
                :src="image"
                alt=""
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
            />
            <div
                v-if="service.is_pro"
                class="absolute top-3 left-3 bg-grad-warm text-white px-2.5 py-1 rounded-full flex items-center gap-1 shadow-lg shadow-orange-500/30"
            >
                <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1">verified</span>
                <span class="text-[10px] font-black uppercase tracking-wider">Pro</span>
            </div>
            <div
                v-if="id"
                class="absolute bottom-3 right-3 z-10"
                @click.stop
            >
                <FavoriteButton :provider-service-id="id" size="sm" />
            </div>
            <div
                v-if="ratingNum"
                class="absolute top-3 right-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm"
            >
                <span class="material-symbols-outlined text-amber-500 text-sm" style="font-variation-settings: 'FILL' 1">
                    star
                </span>
                <span class="text-xs font-bold text-slate-900">{{ ratingNum }}</span>
            </div>
        </div>
        <div class="p-4">
            <div class="flex justify-between items-start gap-2 mb-2">
                <h3 class="font-bold text-[#0b1c30] leading-snug">{{ service.title }}</h3>
                <span
                    v-if="featured"
                    class="bg-blue-100 text-[#003874] text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shrink-0"
                >
                    Destacado
                </span>
            </div>
            <p class="text-sm font-semibold text-slate-800 mb-0.5">{{ service.provider_name }}</p>
            <RouterLink
                v-if="showProfileLink"
                :to="{ name: 'provider-public', params: { id: providerProfileId } }"
                class="inline-flex items-center gap-1 text-xs font-bold text-[#003874] hover:underline mb-2 relative z-10"
                @click.stop
            >
                <span class="material-symbols-outlined text-[14px]">storefront</span>
                Ver perfil del negocio
            </RouterLink>
            <div class="flex items-center gap-1 text-slate-500 text-sm mb-4">
                <span class="material-symbols-outlined text-sm">location_on</span>
                <span>{{ locationLine || '—' }}</span>
            </div>
            <div class="flex justify-between items-center border-t border-slate-100 pt-4 mb-3">
                <div class="flex flex-col min-w-0">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide">
                        {{ priceFooter.label }}
                    </span>
                    <span class="text-lg font-black text-[#003874]" v-html="priceFooter.value"></span>
                </div>
                <span class="shrink-0 text-[#9f4200] font-bold text-sm border border-[#9f4200] px-4 py-2 rounded-lg">
                    {{ priceFooter.cta }}
                </span>
            </div>
            <div v-if="showContact" class="border-t border-slate-100 pt-3" @click.stop>
                <ListingContactActions :service="service" compact />
            </div>
        </div>
    </RouterLink>
</template>
