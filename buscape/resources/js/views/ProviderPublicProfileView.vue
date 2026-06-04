<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { api } from '@/services/api';
import { usePageMeta } from '@/utils/usePageMeta';
import ServiceCard from '@/components/service/ServiceCard.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';
import BusinessHoursDisplay from '@/components/common/BusinessHoursDisplay.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const auth = useAuthStore();
const { setMeta, resetMeta } = usePageMeta();
const loading = ref(true);
const error = ref('');
const profile = ref(null);
const listingsRef = ref(null);

const id = computed(() => Number(route.params.id));

const coverUrl = computed(() => {
    const p = profile.value;
    if (!p) return '';
    return p.cover_image_url || p.cover_url || '';
});

const initial = computed(() => {
    const n = profile.value?.name?.trim() || '?';
    return n.charAt(0).toUpperCase();
});

const locationLine = computed(() => {
    const p = profile.value;
    if (!p) return '';
    const geo = [p.district_name, p.province_name, p.department_name].filter(Boolean).join(' · ');
    const addr = String(p.address_text || '').trim();
    if (geo && addr) return `${geo} — ${addr}`;
    return geo || addr || '';
});

const ratingDisplay = computed(() => {
    const p = profile.value;
    if (!p) return null;
    const v = parseFloat(String(p.avg_rating ?? '').replace(',', '.'));
    const n = Number(p.total_reviews) || 0;
    if (n <= 0 || !Number.isFinite(v)) return null;
    return { value: v.toFixed(1), count: n };
});

const hasDescription = computed(() => !!String(profile.value?.description || '').trim());

function scrollToListings() {
    listingsRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

watch(profile, (p) => {
    if (!p) return;
    const desc = String(p.description || '').replace(/\s+/g, ' ').trim().slice(0, 155)
        || `${p.name} en ${p.district_name || 'Perú'}. Contáctalo directo en Busca PE.`;
    setMeta({
        title: p.name,
        description: desc,
        image: p.cover_image_url || p.cover_url || null,
        url: window.location.href,
    });
});

onUnmounted(resetMeta);

onMounted(async () => {
    loading.value = true;
    error.value = '';
    try {
        const r = await api.get(`/providers/${id.value}`, { auth: true });
        profile.value = r.data || null;
    } catch (e) {
        profile.value = null;
        error.value = e.message || 'No encontramos este negocio.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="pb-20">
        <!-- Nav -->
        <div class="max-w-6xl mx-auto px-4 md:px-8 pt-6">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-[#003874] font-semibold text-sm hover:underline bg-transparent border-0 p-0 cursor-pointer"
                @click="$router.back()"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Volver
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="max-w-6xl mx-auto px-4 md:px-8 py-10 space-y-6 animate-pulse">
            <div class="h-52 md:h-64 rounded-3xl bg-slate-200" />
            <div class="h-32 rounded-2xl bg-slate-100 -mt-16 mx-4" />
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="h-48 rounded-2xl bg-slate-100 lg:col-span-1" />
                <div class="h-64 rounded-2xl bg-slate-100 lg:col-span-2" />
            </div>
        </div>

        <AppAlert v-else-if="error" type="error" class="max-w-6xl mx-auto px-4 md:px-8 mt-6">
            {{ error }}
        </AppAlert>

        <template v-else-if="profile">
            <GuestBrowseBanner v-if="!auth.isAuthenticated" compact class="max-w-6xl mx-auto px-4 md:px-8 mt-4 mb-2" />

            <!-- Cover -->
            <div class="max-w-6xl mx-auto px-4 md:px-8 mt-4">
                <div class="relative rounded-3xl overflow-hidden shadow-xl shadow-[#003874]/10">
                    <div
                        class="relative h-44 sm:h-52 md:h-60 lg:h-64 bg-grad-hero"
                        :class="coverUrl ? 'bg-cover bg-center' : ''"
                        :style="coverUrl ? { backgroundImage: `url(${coverUrl})` } : undefined"
                    >
                        <div
                            class="absolute inset-0"
                            :class="coverUrl
                                ? 'bg-gradient-to-t from-[#0b1c30]/90 via-[#003874]/35 to-[#003874]/10'
                                : 'bg-gradient-to-br from-[#003874]/90 via-[#0056a8]/70 to-[#7c3aed]/40'"
                        />
                        <div class="absolute inset-0 opacity-[0.07] bg-[radial-gradient(circle_at_20%_80%,white,transparent_50%)]" />
                    </div>
                </div>
            </div>

            <!-- Identity card (overlaps cover) -->
            <div class="max-w-6xl mx-auto px-4 md:px-8 -mt-14 md:-mt-16 relative z-10">
                <div class="rounded-2xl md:rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/60 p-5 md:p-7">
                    <div class="flex flex-col sm:flex-row gap-5 sm:gap-6">
                        <div
                            class="w-20 h-20 md:w-24 md:h-24 rounded-2xl shrink-0 flex items-center justify-center text-3xl md:text-4xl font-black shadow-lg ring-4 ring-white -mt-14 sm:-mt-0 sm:ml-0 mx-auto sm:mx-0"
                            :class="coverUrl
                                ? 'bg-[#003874] text-white'
                                : 'bg-gradient-to-br from-[#003874] to-[#0056a8] text-white'"
                        >
                            {{ initial }}
                        </div>

                        <div class="flex-1 min-w-0 text-center sm:text-left pt-0 sm:pt-1">
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-[#0b1c30] leading-tight">
                                    {{ profile.name }}
                                </h1>
                                <span
                                    v-if="profile.is_pro"
                                    class="inline-flex items-center gap-1 bg-grad-warm text-white text-[10px] font-black uppercase px-2.5 py-1 rounded-full shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1">verified</span>
                                    Pro
                                </span>
                                <span
                                    v-if="profile.is_verified"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full"
                                >
                                    <span class="material-symbols-outlined text-[13px]">verified_user</span>
                                    Verificado
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-2 text-sm">
                                <span
                                    v-if="profile.is_open_now === true"
                                    class="inline-flex items-center gap-1.5 font-bold text-emerald-700"
                                >
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Abierto ahora
                                </span>
                                <span
                                    v-else-if="profile.is_open_now === false"
                                    class="inline-flex items-center gap-1.5 font-semibold text-slate-500"
                                >
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Cerrado ahora
                                </span>
                                <span v-if="ratingDisplay" class="inline-flex items-center gap-1 text-slate-700 font-semibold">
                                    <span class="material-symbols-outlined text-amber-500 text-[18px]" style="font-variation-settings: 'FILL' 1">star</span>
                                    {{ ratingDisplay.value }}
                                    <span class="text-slate-400 font-normal">({{ ratingDisplay.count }})</span>
                                </span>
                                <span v-if="profile.listings_count" class="inline-flex items-center gap-1 text-slate-600">
                                    <span class="material-symbols-outlined text-[18px] text-[#003874]">campaign</span>
                                    {{ profile.listings_count }} anuncio{{ profile.listings_count === 1 ? '' : 's' }}
                                </span>
                            </div>

                            <p v-if="locationLine" class="mt-3 text-sm text-slate-600 flex items-start justify-center sm:justify-start gap-1.5">
                                <span class="material-symbols-outlined text-[18px] text-[#003874] shrink-0 mt-0.5">location_on</span>
                                <span class="text-left">{{ locationLine }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="max-w-6xl mx-auto px-4 md:px-8 mt-8">
                <div class="grid lg:grid-cols-3 gap-6 lg:gap-8 items-start">
                    <!-- Sidebar -->
                    <aside class="lg:col-span-1 space-y-4 lg:sticky lg:top-24">
                        <BusinessHoursDisplay
                            v-if="profile.business_hours"
                            :schedule="profile.business_hours"
                        />

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-xs font-bold uppercase tracking-widest text-[#003874] mb-3 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">contact_phone</span>
                                Contacto
                            </h2>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Teléfono y WhatsApp están en cada anuncio para que contactes al local que te interese.
                            </p>
                            <button
                                v-if="profile.listings_count"
                                type="button"
                                class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#003874] text-white text-sm font-bold px-4 py-2.5 hover:bg-[#002a55] transition cursor-pointer border-0"
                                @click="scrollToListings"
                            >
                                Ver anuncios
                                <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                            </button>
                        </div>

                        <div
                            v-if="profile.guest_preview && profile.description_truncated"
                            class="rounded-2xl border border-amber-100 bg-amber-50/80 p-4 text-sm text-amber-900"
                        >
                            <p class="font-bold flex items-center gap-2 mb-1">
                                <span class="material-symbols-outlined text-[18px]">lock</span>
                                Vista previa
                            </p>
                            <p class="text-amber-800/90 leading-relaxed">
                                Inicia sesión para leer la descripción completa y contactar sin límites.
                            </p>
                        </div>
                    </aside>

                    <!-- Main column -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- About -->
                        <section v-if="hasDescription" class="rounded-2xl border border-slate-200 bg-white p-6 md:p-7 shadow-sm">
                            <h2 class="text-xs font-bold uppercase tracking-widest text-[#7c3aed] mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">info</span>
                                Sobre el negocio
                            </h2>
                            <p class="text-slate-700 leading-relaxed whitespace-pre-wrap text-[15px]">
                                {{ profile.description }}
                            </p>
                        </section>

                        <!-- Listings -->
                        <section ref="listingsRef" class="scroll-mt-24">
                            <div class="flex flex-wrap justify-between items-end gap-4 mb-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Anuncios</p>
                                    <h2 class="text-xl md:text-2xl font-black text-[#0b1c30] mt-0.5">
                                        Publicaciones activas
                                        <span class="text-slate-400 font-bold text-lg ml-1">{{ profile.listings_count }}</span>
                                    </h2>
                                </div>
                                <RouterLink
                                    :to="{ name: 'home' }"
                                    class="text-sm font-bold text-[#003874] hover:underline no-underline inline-flex items-center gap-1"
                                >
                                    Explorar más
                                    <span class="material-symbols-outlined text-[16px]">east</span>
                                </RouterLink>
                            </div>

                            <div
                                v-if="profile.listings?.length"
                                class="grid grid-cols-1 sm:grid-cols-2 gap-5"
                            >
                                <ServiceCard
                                    v-for="listing in profile.listings"
                                    :key="listing.service_id"
                                    :service="listing"
                                    :show-provider-link="false"
                                />
                            </div>
                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-12 text-center"
                            >
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-3 block">campaign</span>
                                <p class="text-slate-600 font-medium">Sin anuncios visibles por ahora</p>
                                <p class="text-sm text-slate-400 mt-1">Vuelve más tarde o explora otros negocios.</p>
                            </div>
                        </section>

                        <!-- Reviews -->
                        <section v-if="profile.reviews?.length">
                            <div class="mb-5">
                                <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Opiniones</p>
                                <h2 class="text-xl md:text-2xl font-black text-[#0b1c30] mt-0.5">
                                    Valoraciones de clientes
                                    <span class="text-slate-400 font-bold text-lg ml-1">{{ profile.reviews.length }}</span>
                                </h2>
                            </div>
                            <ul class="grid gap-4 sm:grid-cols-2">
                                <li
                                    v-for="rev in profile.reviews"
                                    :key="rev.id"
                                    class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-start justify-between gap-3 mb-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#003874]/15 to-[#7c3aed]/10 text-[#003874] font-black flex items-center justify-center shrink-0 text-sm">
                                                {{ (rev.client_name || 'C').charAt(0).toUpperCase() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-900 truncate">{{ rev.client_name || 'Cliente' }}</p>
                                                <p v-if="rev.created_at" class="text-[10px] text-slate-400 uppercase tracking-wide">
                                                    {{ new Date(rev.created_at).toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-0.5 shrink-0 bg-amber-50 rounded-lg px-2 py-1 border border-amber-100">
                                            <span
                                                v-for="n in 5"
                                                :key="n"
                                                class="material-symbols-outlined text-[14px]"
                                                :class="n <= rev.rating ? 'text-amber-500' : 'text-slate-200'"
                                                :style="n <= rev.rating ? { fontVariationSettings: '\'FILL\' 1' } : undefined"
                                            >star</span>
                                        </div>
                                    </div>
                                    <p v-if="rev.comment" class="text-sm text-slate-700 leading-relaxed">{{ rev.comment }}</p>
                                    <p v-else class="text-xs text-slate-400 italic">Sin comentario escrito.</p>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
