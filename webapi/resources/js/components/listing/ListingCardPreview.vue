<script setup>
import { computed } from 'vue';
import ListingImageCarousel from '@/components/listing/ListingImageCarousel.vue';

/**
 * Vista previa estática de cómo se verá el anuncio en búsqueda (sin enlace).
 */
const props = defineProps({
    service: { type: Object, required: true },
    featured: { type: Boolean, default: false },
});

const images = computed(() => {
    const fromList = Array.isArray(props.service.images) ? props.service.images.filter(Boolean) : [];
    if (fromList.length) return fromList.slice(0, 3);
    if (props.service.cover_image_url) return [props.service.cover_image_url];
    return [];
});

const hasImages = computed(() => images.value.length > 0);

const ratingNum = computed(() => {
    const v = parseFloat(String(props.service.avg_rating ?? '').replace(',', '.'));
    const reviews = Number(props.service.total_reviews) || 0;
    if (reviews <= 0 || !Number.isFinite(v)) return null;
    return v.toFixed(1);
});

const localName = computed(() => {
    const label = String(props.service.location_label || '').trim();
    if (label) return label;
    return props.service.provider_name || 'Tu negocio';
});

const geoLine = computed(() => {
    const parts = [
        props.service.district_name,
        props.service.province_name,
        props.service.department_name,
    ].filter(Boolean);
    return parts.length ? parts.join(' · ') : null;
});

const addressLine = computed(() => {
    const addr = String(props.service.address_text || '').trim();
    return addr || null;
});

const locationLine = computed(() => {
    if (geoLine.value && addressLine.value) {
        return `${geoLine.value} — ${addressLine.value}`;
    }
    return geoLine.value || addressLine.value || '—';
});

const priceFooter = computed(() => {
    const pt = String(props.service.price_type || 'cotizar');
    const has = props.service.base_price != null && String(props.service.base_price).trim() !== '';
    const pNum = has ? String(props.service.base_price).trim() : '';
    const ptLabels = { cotizar: 'COTIZAR', desde: 'DESDE', fijo: 'PRECIO FIJO' };
    const label = ptLabels[pt] || 'PRECIO';
    const value = has ? `S/\u00A0${pNum}` : 'Consultar';
    const cta = pt === 'cotizar' ? 'Cotizar' : pt === 'fijo' ? 'Ver más' : 'Consultar';
    return { label, value, cta };
});
</script>

<template>
    <div class="relative">
        <span class="absolute -top-2 left-3 z-10 text-[10px] font-bold uppercase tracking-widest bg-[#003874] text-white px-2 py-0.5 rounded-full shadow">
            Vista previa
        </span>
        <div
            class="block bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden pointer-events-none select-none"
            aria-hidden="true"
        >
            <div class="relative h-48 bg-slate-200 overflow-hidden">
                <ListingImageCarousel
                    v-if="hasImages"
                    :images="images"
                    :alt="service.title || 'Anuncio'"
                />
                <div
                    v-else
                    class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-1"
                >
                    <span class="material-symbols-outlined text-4xl">add_photo_alternate</span>
                    <span class="text-xs font-semibold">Agrega hasta 3 fotos</span>
                </div>
                <div
                    v-if="service.is_pro"
                    class="absolute top-3 left-3 z-10 bg-grad-warm text-white px-2.5 py-1 rounded-full flex items-center gap-1 shadow-lg shadow-orange-500/30"
                >
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1">verified</span>
                    <span class="text-[10px] font-black uppercase tracking-wider">Pro</span>
                </div>
                <div
                    v-if="ratingNum"
                    class="absolute top-3 right-3 z-10 bg-white/90 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm"
                >
                    <span class="material-symbols-outlined text-amber-500 text-sm" style="font-variation-settings: 'FILL' 1">star</span>
                    <span class="text-xs font-bold text-slate-900">{{ ratingNum }}</span>
                </div>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-start gap-2 mb-2">
                    <h3 class="font-bold text-[#0b1c30] leading-snug line-clamp-2">
                        {{ service.title || 'Título del anuncio' }}
                    </h3>
                    <span
                        v-if="featured"
                        class="bg-blue-100 text-[#003874] text-[10px] font-bold uppercase px-2 py-0.5 rounded-full shrink-0"
                    >
                        Destacado
                    </span>
                </div>
                <p class="text-sm font-semibold text-slate-800 mb-0.5 truncate">
                    {{ localName }}
                </p>
                <div class="flex items-start gap-1 text-slate-500 text-sm mb-4">
                    <span class="material-symbols-outlined text-sm shrink-0 mt-0.5">location_on</span>
                    <span class="line-clamp-2">{{ locationLine }}</span>
                </div>
                <div class="flex justify-between items-center border-t border-slate-100 pt-4">
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
            </div>
        </div>
    </div>
</template>
