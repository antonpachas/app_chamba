<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { scrollToHash } from '@/utils/scroll';
import { loadRecentSearches, pushRecentSearch } from '@/utils/searchHistory';
import { useCatalogStore } from '@/stores/catalog';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';
import { platform } from '@/services/features';
import { categoryStyleFor } from '@/components/common/CategoryIcon';
import ServiceCard from '@/components/service/ServiceCard.vue';
import AdSlot from '@/components/ads/AdSlot.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';
import ListingSearchBar from '@/components/search/ListingSearchBar.vue';
import SearchResultsToolbar from '@/components/search/SearchResultsToolbar.vue';
import ListingResultsMap from '@/components/search/ListingResultsMap.vue';
import CategorySuggestModal from '@/components/search/CategorySuggestModal.vue';
import CategorySuggestCallout from '@/components/search/CategorySuggestCallout.vue';
import DiscoverMarketingSections from '@/components/discover/DiscoverMarketingSections.vue';
import { useAuthStore } from '@/stores/auth';
import NotificationsBanner from '@/components/notifications/NotificationsBanner.vue';

const route = useRoute();
const auth = useAuthStore();
const router = useRouter();
const catalog = useCatalogStore();
const geo = useGeoStore();
const search = useSearchStore();

const showSuggestCategory = ref(false);
const showGeoFilters = ref(false);
const recentSearches = ref(loadRecentSearches());

const gridClass = computed(() => {
    const sm = Math.max(1, Math.min(2, Number(platform.search_grid_columns_sm) || 1));
    const md = Math.max(1, Math.min(4, Number(platform.search_grid_columns_md) || 2));
    if (sm >= 2) {
        return 'grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-3 xl:grid-cols-4';
    }
    if (md >= 3) {
        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6';
    }
    if (md >= 2) {
        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6';
    }
    return 'grid grid-cols-1 gap-4 md:gap-6 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3';
});

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

watch(
    () => search.viewMode,
    (mode) => {
        if (mode === 'map') void geo.ensureMapLocation();
    },
    { immediate: true },
);

function recordSearch() {
    const kw = search.keyword.trim();
    const cat = catalog.categories.find((c) => Number(c.id) === Number(search.selectedCategoryId));
    const label = kw || cat?.name || (geo.selectedDistrictId ? 'Búsqueda por zona' : '');
    if (!label) return;
    recentSearches.value = pushRecentSearch({
        label,
        keyword: kw,
        category_id: search.selectedCategoryId,
        district_id: geo.selectedDistrictId,
    });
}

async function submitSearch() {
    await search.run();
    recordSearch();
}

function pickCategory(id) {
    search.setCategory(id);
    void submitSearch();
}
function clearCategory() {
    search.setCategory(null);
    void submitSearch();
}

function applyRecent(item) {
    search.setKeyword(item.keyword || '');
    if (item.category_id) search.setCategory(item.category_id);
    void submitSearch();
}
</script>

<template>
    <div class="chamba-container pt-6 pb-12">
        <PageHeader
            eyebrow="Directorio · Perú"
            title="Encuentra negocios cerca de ti"
            subtitle="Busca por categoría, zona o GPS. Abre un anuncio para ver contacto."
            class="text-center md:text-left [&_.page-title]:md:text-4xl mb-6"
        />

        <div id="buscar" class="scroll-mt-24 max-w-4xl mx-auto">
            <ListingSearchBar auto-run-on-geo :compact-geo="!showGeoFilters" class="mb-3" @search="submitSearch" />
            <button
                type="button"
                class="mx-auto block text-xs font-bold text-[#003874] hover:underline mb-4"
                @click="showGeoFilters = !showGeoFilters"
            >
                {{ showGeoFilters ? 'Ocultar filtros de zona' : 'Filtrar por departamento / provincia / distrito' }}
            </button>
        </div>

        <NotificationsBanner v-if="auth.isCliente" class="max-w-3xl mx-auto mb-6" />
        <GuestBrowseBanner
            v-if="!auth.isAuthenticated"
            :meta="search.guestMeta"
            class="max-w-3xl mx-auto mb-6"
        />
        <AdSlot placement="home" class="mb-8" />

        <section v-if="recentSearches.length" class="mb-8 max-w-4xl mx-auto" aria-label="Tus últimas búsquedas">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Tus últimas búsquedas</p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="item in recentSearches"
                    :key="item.ts"
                    type="button"
                    class="px-3 py-1.5 rounded-full text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:border-[#003874]/40 hover:bg-slate-50 transition inline-flex items-center gap-1"
                    @click="applyRecent(item)"
                >
                    <span class="material-symbols-outlined text-base text-slate-400">history</span>
                    {{ item.label }}
                </button>
            </div>
        </section>

        <section class="mb-8" aria-label="Categorías">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Categorías</p>
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-1 px-1 snap-x snap-mandatory scrollbar-thin">
                <button
                    type="button"
                    class="snap-start shrink-0 px-4 py-2.5 rounded-2xl text-sm font-bold border transition"
                    :class="
                        search.selectedCategoryId == null
                            ? 'bg-[#003874] text-white border-[#003874] shadow-md'
                            : 'bg-white text-slate-700 border-slate-200'
                    "
                    @click="clearCategory"
                >
                    Todas
                </button>
                <button
                    v-for="c in catalog.categories"
                    :key="c.id"
                    type="button"
                    class="snap-start shrink-0 px-4 py-2.5 rounded-2xl text-sm font-bold border transition flex items-center gap-2 max-w-[11rem]"
                    :class="
                        search.selectedCategoryId === c.id
                            ? 'bg-[#003874] text-white border-[#003874] shadow-md'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/30'
                    "
                    @click="pickCategory(c.id)"
                >
                    <span
                        class="material-symbols-outlined text-lg shrink-0"
                        :class="search.selectedCategoryId === c.id ? 'text-white' : 'text-[#003874]'"
                    >
                        {{ categoryStyleFor(c.name).icon }}
                    </span>
                    <span class="truncate">{{ c.name }}</span>
                </button>
            </div>
            <CategorySuggestCallout @open="showSuggestCategory = true" />
        </section>

        <section aria-label="Resultados de búsqueda">
            <div class="flex justify-between items-baseline gap-4 mb-3">
                <h2 class="text-xl md:text-2xl font-semibold text-[#0b1c30] tracking-tight">Anuncios</h2>
                <p v-if="search.searched && !search.loading && !search.error" class="text-sm text-slate-500">
                    {{ search.results.length }} resultado(s)
                </p>
            </div>
            <SearchResultsToolbar v-if="search.searched && !search.loading && !search.error && search.results.length" />
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
                <p class="text-sm text-slate-500 mt-1">Prueba otras palabras, otra categoría o zona.</p>
            </div>
            <ListingResultsMap
                v-else-if="search.viewMode === 'map'"
                :results="search.results"
                :user-lat="geo.mapDisplayLat"
                :user-lng="geo.mapDisplayLng"
                :location-loading="geo.mapLocationLoading"
            />
            <div v-else :class="gridClass">
                <ServiceCard v-for="s in search.results" :key="s.service_id" :service="s" />
            </div>
        </section>

        <DiscoverMarketingSections v-if="!auth.isAuthenticated || auth.isCliente" />
        <CategorySuggestModal v-model:open="showSuggestCategory" />
    </div>
</template>
