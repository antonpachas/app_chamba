<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { api } from '@/services/api';
import ServiceCard from '@/components/service/ServiceCard.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';
import OpenHoursBadge from '@/components/common/OpenHoursBadge.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const auth = useAuthStore();
const loading = ref(true);
const error = ref('');
const profile = ref(null);

const id = computed(() => Number(route.params.id));

const locationLine = computed(() => {
    const p = profile.value;
    if (!p) return '';
    return [p.district_name, p.province_name, p.department_name].filter(Boolean).join(' · ');
});

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
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <div class="mb-6">
            <button
                type="button"
                class="text-[#003874] font-semibold text-sm hover:underline bg-transparent border-0 p-0 cursor-pointer"
                @click="$router.back()"
            >
                ← Volver
            </button>
        </div>

        <p v-if="loading" class="text-slate-500 py-16 text-center">Cargando perfil…</p>
        <AppAlert v-else-if="error" type="error">{{ error }}</AppAlert>

        <template v-else-if="profile">
            <GuestBrowseBanner v-if="!auth.isAuthenticated" compact class="mb-6" />
            <header class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm mb-10">
                <div class="bg-grad-hero px-6 md:px-10 py-8 md:py-10 text-white">
                    <div class="flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                        <img
                            v-if="profile.avatar_url"
                            :src="profile.avatar_url"
                            alt=""
                            class="w-20 h-20 rounded-2xl object-cover ring-2 ring-white/40 shadow-lg"
                        />
                        <div
                            v-else
                            class="w-20 h-20 rounded-2xl bg-white/15 flex items-center justify-center text-3xl font-black ring-2 ring-white/30"
                        >
                            {{ (profile.name || '?').charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h1 class="text-2xl md:text-3xl font-black tracking-tight">{{ profile.name }}</h1>
                                <span
                                    v-if="profile.is_pro"
                                    class="inline-flex items-center gap-1 bg-grad-warm text-white text-[10px] font-black uppercase px-2 py-0.5 rounded-full"
                                >
                                    <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1">verified</span>
                                    Pro
                                </span>
                                <span
                                    v-if="profile.is_verified"
                                    class="text-[10px] font-bold uppercase bg-white/20 px-2 py-0.5 rounded-full"
                                >Verificado</span>
                            </div>
                            <p v-if="locationLine" class="text-white/85 text-sm flex items-center gap-1 mt-1">
                                <span class="material-symbols-outlined text-base">location_on</span>
                                {{ locationLine }}
                            </p>
                            <p v-if="profile.avg_rating && profile.total_reviews > 0" class="text-white/90 text-sm mt-2 flex items-center gap-1">
                                <span class="material-symbols-outlined text-amber-300 text-base" style="font-variation-settings: 'FILL' 1">star</span>
                                {{ profile.avg_rating }} · {{ profile.total_reviews }} valoración(es)
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 md:px-10 py-6 space-y-4">
                    <p v-if="profile.description" class="text-slate-700 leading-relaxed whitespace-pre-wrap">
                        {{ profile.description }}
                    </p>
                    <p v-if="profile.description_truncated" class="text-sm text-slate-500 mt-2">
                        Descripción abreviada para visitantes.
                    </p>
                    <p v-if="profile.address_text" class="text-sm text-slate-600">
                        <strong>Dirección:</strong> {{ profile.address_text }}
                    </p>
                    <OpenHoursBadge
                        v-if="profile.is_open_now !== null && profile.is_open_now !== undefined"
                        :is-open="profile.is_open_now"
                        :summary="profile.hours_summary"
                        class="pt-2"
                    />
                    <p class="text-sm text-slate-600 pt-2 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3">
                        <span class="material-symbols-outlined text-[#003874] align-middle text-base mr-1">info</span>
                        Abre un anuncio abajo para ver teléfono, WhatsApp y contactar al negocio.
                    </p>
                </div>
            </header>

            <section>
                <div class="flex justify-between items-end gap-4 mb-6 flex-wrap">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Anuncios</p>
                        <h2 class="text-2xl font-bold text-[#0b1c30]">
                            Publicaciones activas
                            <span class="text-slate-500 font-semibold text-lg">({{ profile.listings_count }})</span>
                        </h2>
                    </div>
                    <RouterLink :to="{ name: 'home' }" class="text-sm font-bold text-[#003874] hover:underline no-underline">
                        Buscar más negocios
                    </RouterLink>
                </div>

                <div
                    v-if="profile.listings?.length"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                >
                    <ServiceCard
                        v-for="listing in profile.listings"
                        :key="listing.service_id"
                        :service="listing"
                        :show-provider-link="false"
                    />
                </div>
                <p v-else class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
                    Este negocio no tiene anuncios visibles en este momento.
                </p>
            </section>

            <section v-if="profile.reviews?.length" class="mt-12">
                <h2 class="text-xl font-bold text-[#0b1c30] mb-4">Valoraciones</h2>
                <ul class="space-y-3">
                    <li
                        v-for="rev in profile.reviews"
                        :key="rev.id"
                        class="rounded-xl border border-slate-200 bg-white p-4"
                    >
                        <div class="flex justify-between gap-2 text-sm">
                            <strong>{{ rev.client_name || 'Cliente' }}</strong>
                            <span class="text-amber-600 font-bold">{{ rev.rating }} ★</span>
                        </div>
                        <p v-if="rev.comment" class="text-sm text-slate-600 mt-2">{{ rev.comment }}</p>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>
