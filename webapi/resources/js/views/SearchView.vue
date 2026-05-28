<script setup>
import { nextTick, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { scrollToHash } from '@/utils/scroll';
import { useCatalogStore } from '@/stores/catalog';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';
import { categoryStyleFor } from '@/components/common/CategoryIcon';
import ServiceCard from '@/components/service/ServiceCard.vue';
import AdSlot from '@/components/ads/AdSlot.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';
import ListingSearchBar from '@/components/search/ListingSearchBar.vue';
import DiscoverMarketingSections from '@/components/discover/DiscoverMarketingSections.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const auth = useAuthStore();
const router = useRouter();
const catalog = useCatalogStore();
const geo = useGeoStore();
const search = useSearchStore();

function applyHashScroll() {
    if (route.hash) {
        nextTick(() => scrollToHash(route.hash));
    }
}

onMounted(async () => {
    await Promise.all([catalog.ensureCategories(), geo.ensureDepartments()]);
    if (typeof route.query.q === 'string' && route.query.q.trim()) {
        search.setKeyword(route.query.q.trim());
        await router.replace({ name: 'home', query: {} });
    }
    if (!search.searched) await search.run();
    applyHashScroll();
});

watch(() => route.hash, applyHashScroll);

function submitSearch() {
    void search.run();
}

function pickCategory(id) {
    search.setCategory(id);
    void search.run();
}
function clearCategory() {
    search.setCategory(null);
    void search.run();
}
</script>

<template>
    <div class="chamba-container pt-8 pb-12">
        <PageHeader
            eyebrow="Directorio · Perú"
            title="Encuentra negocios cerca de ti"
            subtitle="Filtra por rubro, zona o GPS. Contacta directo al negocio."
            class="text-center md:text-left [&_.page-title]:md:text-4xl"
        />

        <div id="buscar" class="scroll-mt-24">
            <ListingSearchBar auto-run-on-geo class="mb-6" @search="submitSearch" />
        </div>

        <GuestBrowseBanner
            v-if="!auth.isAuthenticated"
            :meta="search.guestMeta"
            class="max-w-3xl mx-auto mb-8"
        />
        <AdSlot placement="home" class="mb-8" />

        <section class="mb-10" aria-label="Filtrar por rubro">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Rubros</p>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="clearCategory"
                    class="px-4 py-2 rounded-full text-sm font-semibold border transition"
                    :class="
                        search.selectedCategoryId == null
                            ? 'bg-[#003874] text-white border-[#003874]'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/40'
                    "
                >
                    Todas
                </button>
                <button
                    v-for="c in catalog.categories"
                    :key="c.id"
                    type="button"
                    @click="pickCategory(c.id)"
                    class="px-4 py-2 rounded-full text-sm font-semibold border transition flex items-center gap-2"
                    :class="
                        search.selectedCategoryId === c.id
                            ? 'bg-[#003874] text-white border-[#003874]'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/40'
                    "
                >
                    <span class="material-symbols-outlined text-base">
                        {{ categoryStyleFor(c.name).icon }}
                    </span>
                    {{ c.name }}
                </button>
            </div>
        </section>

        <section aria-label="Resultados de búsqueda">
            <div class="flex justify-between items-baseline gap-4 mb-6">
                <h2 class="text-2xl font-semibold text-[#0b1c30] tracking-tight">Anuncios</h2>
                <p v-if="search.searched && !search.loading && !search.error" class="text-sm text-slate-500">
                    {{ search.results.length }} resultado(s)
                </p>
            </div>
            <p v-if="search.loading" class="py-16 text-center text-slate-500 font-medium">Buscando…</p>
            <div
                v-else-if="search.error"
                class="rounded-xl border border-red-100 bg-red-50 text-red-800 text-sm font-medium px-4 py-3"
            >
                {{ search.error }}
            </div>
            <div
                v-else-if="search.searched && search.results.length === 0"
                class="ui-card py-16 px-6 text-center"
            >
                <span class="material-symbols-outlined text-5xl text-slate-300 mb-3">search_off</span>
                <p class="text-slate-700 font-semibold">Sin resultados</p>
                <p class="text-sm text-slate-500 mt-1">Prueba otras palabras, rubro o zona.</p>
            </div>
            <div
                v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
            >
                <ServiceCard v-for="s in search.results" :key="s.service_id" :service="s" />
            </div>
        </section>

        <DiscoverMarketingSections v-if="!auth.isAuthenticated || auth.isCliente" />
    </div>
</template>
