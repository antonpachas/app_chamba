<script setup>

import { computed, nextTick, onMounted, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import { scrollToHash } from '@/utils/scroll';

import { pushRecentSearch } from '@/utils/searchHistory';

import { useCatalogStore } from '@/stores/catalog';

import { useGeoStore } from '@/stores/geo';

import { useSearchStore } from '@/stores/search';

import { platform } from '@/services/features';

import ServiceCard from '@/components/service/ServiceCard.vue';

import AdSlot from '@/components/ads/AdSlot.vue';

import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';

import ListingSearchBar from '@/components/search/ListingSearchBar.vue';

import SearchResultsSidebar from '@/components/search/SearchResultsSidebar.vue';

import SearchResultsToolbar from '@/components/search/SearchResultsToolbar.vue';

import SearchResultsPagination from '@/components/search/SearchResultsPagination.vue';

import ListingResultsMap from '@/components/search/ListingResultsMap.vue';

import CategorySuggestModal from '@/components/search/CategorySuggestModal.vue';

import CategorySuggestCallout from '@/components/search/CategorySuggestCallout.vue';

import HomeFeaturedSlider from '@/components/home/HomeFeaturedSlider.vue';

import HomeCategoriesSection from '@/components/home/HomeCategoriesSection.vue';

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

const searchBarRef = ref(null);



const gridClass = computed(() => {

    const sm = Math.max(1, Math.min(2, Number(platform.search_grid_columns_sm) || 1));

    const md = Math.max(1, Math.min(4, Number(platform.search_grid_columns_md) || 2));

    if (sm >= 2) {

        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5';

    }

    if (md >= 3) {

        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5';

    }

    if (md >= 2) {

        return 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5';

    }

    return 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5';

});



const selectedCategoryName = computed(() => {

    if (search.selectedCategoryId == null) return null;

    return catalog.categories.find((c) => Number(c.id) === Number(search.selectedCategoryId))?.name || null;

});

/** Banners admin: `home` al explorar, `search` tras buscar/filtrar. */
const adPlacement = computed(() => (search.searched ? 'search' : 'home'));

/** Vista tipo marketplace (sidebar + grid) al buscar o filtrar. */
const showSearchLayout = computed(() => {
    if (!search.searched) return false;
    return !!(
        search.keyword.trim()
        || search.selectedCategoryId != null
        || geo.selectedDistrictId != null
        || geo.selectedProvinceId != null
        || geo.selectedDepartmentId != null
        || geo.useGps
    );
});

const resultsTitle = computed(() => {
    const kw = search.keyword.trim();
    if (kw) return kw;
    if (selectedCategoryName.value) return selectedCategoryName.value;
    if (geo.useGps) return 'Cerca de ti';
    return 'Anuncios cerca de ti';
});

const searchGridClass = computed(() =>
    'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5',
);

const resultsCount = computed(() => {
    if (!search.searched || search.loading || search.error) return null;
    return search.searchMeta?.pagination?.total ?? search.results.length;
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

    pushRecentSearch({
        label,
        keyword: kw,
        category_id: search.selectedCategoryId,
        district_id: geo.selectedDistrictId,
    });
    searchBarRef.value?.refreshRecents?.();
}



async function submitSearch() {

    await search.run(1);

    recordSearch();

    await nextTick();

    if (search.keyword.trim() || search.selectedCategoryId != null || geo.selectedDistrictId) {

        scrollToHash('#resultados');

    }

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
    search.setKeyword(item.keyword || item.label || '');
    if (item.category_id) search.setCategory(item.category_id);
    void submitSearch();
}

function resetSearch() {
    search.$patch({
        keyword: '',
        selectedCategoryId: null,
        searched: false,
        results: [],
        error: null,
        loading: false,
    });
}

</script>



<template>

    <div class="home-page home-page--market">

        <!-- 1. Banda superior: búsqueda prominente -->
        <section class="home-hero-band" aria-label="Buscar negocios">
            <div class="home-hero-band__inner chamba-container max-w-5xl mx-auto px-4 md:px-6">

                <!-- Tagline (solo modo exploración) -->
                <div v-if="!showSearchLayout" class="home-hero-band__headline text-center mb-5 md:mb-6">
                    <h1 class="text-[1.75rem] md:text-[2.25rem] font-extrabold text-white tracking-tight leading-tight">
                        Encuentra el negocio ideal
                        <span class="block text-[#93c5fd]">en tu zona</span>
                    </h1>
                    <p class="text-white/65 text-sm md:text-base mt-2.5 max-w-md mx-auto leading-relaxed">
                        Servicios, tiendas y negocios locales verificados en todo el Perú
                    </p>
                </div>

                <div id="buscar" class="scroll-mt-24">
                    <ListingSearchBar
                        ref="searchBarRef"
                        variant="home"
                        auto-run-on-geo
                        :compact-geo="showSearchLayout || !showGeoFilters"
                        :show-filter-link="!showSearchLayout"
                        :filter-expanded="showGeoFilters"
                        @search="submitSearch"
                        @apply-recent="applyRecent"
                        @toggle-geo-filters="showGeoFilters = !showGeoFilters"
                    />
                </div>

                <GuestBrowseBanner :meta="search.guestMeta" compact class="mt-2" />
            </div>
        </section>



        <!-- 2. Banner hero (solo exploración inicial) -->
        <section v-if="!showSearchLayout" class="home-banner-zone" aria-label="Destacados">
            <HomeFeaturedSlider />
            <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6">
                <AdSlot placement="home" />
            </div>
        </section>



        <!-- 3. Categorías (solo exploración inicial) -->
        <template v-if="!showSearchLayout">
            <HomeCategoriesSection
                layout="shelf"
                :categories="catalog.categories"
                :selected-id="search.selectedCategoryId"
                @select="pickCategory"
                @clear="clearCategory"
            />

            <div class="home-shelf__footer chamba-container max-w-6xl mx-auto px-4 md:px-6 pb-3">
                <CategorySuggestCallout inline @open="showSuggestCategory = true" />
            </div>
        </template>



        <!-- 4. Resultados -->
        <section class="home-results-zone" :class="showSearchLayout ? 'home-results-zone--search' : ''">
            <div
                class="chamba-container mx-auto px-4 md:px-6 py-6 md:py-8"
                :class="showSearchLayout ? 'max-w-7xl' : 'max-w-6xl home-results-panel'"
            >
                <NotificationsBanner v-if="auth.isCliente" class="mb-5" />

                <section id="resultados" class="scroll-mt-24" aria-label="Resultados de búsqueda">
                    <!-- Cabecera resultados (estilo ML) -->
                    <div
                        v-if="showSearchLayout"
                        class="search-results-header mb-5"
                    >
                        <p v-if="selectedCategoryName && search.keyword.trim()" class="search-results-header__crumb text-sm text-slate-500 mb-1">
                            Categorías · {{ selectedCategoryName }}
                        </p>
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 class="search-results-header__title">{{ resultsTitle }}</h2>
                                <p v-if="resultsCount != null" class="search-results-header__count">
                                    {{ resultsCount }} {{ resultsCount === 1 ? 'resultado' : 'resultados' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#003874] hover:bg-blue-50 border border-[#003874]/20 rounded-lg px-3 py-1.5 transition shrink-0"
                                @click="resetSearch"
                            >
                                <span class="material-symbols-outlined text-[16px]">home</span>
                                Volver a explorar
                            </button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-wrap items-end justify-between gap-3 mb-4 pb-4 border-b border-slate-100"
                    >
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-slate-900">Anuncios cerca de ti</h2>
                            <p v-if="selectedCategoryName" class="text-sm text-slate-500 mt-0.5">
                                Categoría: {{ selectedCategoryName }}
                            </p>
                        </div>
                        <p v-if="resultsCount != null" class="text-sm text-slate-500 tabular-nums font-medium">
                            {{ resultsCount }} {{ resultsCount === 1 ? 'anuncio' : 'anuncios' }}
                        </p>
                    </div>

                    <div
                        class="search-results-layout"
                        :class="showSearchLayout ? 'search-results-layout--with-sidebar' : ''"
                    >
                        <SearchResultsSidebar
                            v-if="showSearchLayout"
                            class="search-results-layout__sidebar"
                            @apply="submitSearch"
                        />

                        <div class="search-results-layout__main min-w-0">
                            <SearchResultsToolbar
                                v-if="!showSearchLayout && search.searched && !search.loading && !search.error && search.results.length"
                                class="mb-5"
                            />

                            <p v-if="search.loading" class="py-20 text-center text-slate-500">Buscando…</p>

                            <div
                                v-else-if="search.error"
                                class="rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3"
                            >
                                {{ search.error }}
                            </div>

                            <div
                                v-else-if="search.searched && search.results.length === 0"
                                class="rounded-xl border border-slate-200 bg-slate-50 py-16 px-6 text-center"
                            >
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-3 block">search_off</span>
                                <p class="text-slate-800 font-medium">No encontramos anuncios</p>
                                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                                    Prueba con otras palabras, otra categoría o amplía la zona de búsqueda.
                                </p>
                            </div>

                            <ListingResultsMap
                                v-else-if="search.viewMode === 'map'"
                                :results="search.results"
                                :user-lat="geo.mapDisplayLat"
                                :user-lng="geo.mapDisplayLng"
                                :location-loading="geo.mapLocationLoading"
                            />

                            <div v-else :class="showSearchLayout ? searchGridClass : gridClass">
                                <ServiceCard v-for="s in search.results" :key="s.service_id" :service="s" />
                            </div>

                            <SearchResultsPagination v-if="search.viewMode === 'list' && search.results.length" />

                            <AdSlot :placement="adPlacement" class="mt-8" />
                        </div>
                    </div>
                </section>
            </div>
        </section>



        <!-- 5. Marketing al pie (solo exploración) -->
        <div v-if="!showSearchLayout" class="home-marketing chamba-container max-w-6xl mx-auto px-4 md:px-6 pb-12 md:pb-16">
            <DiscoverMarketingSections />
        </div>



        <CategorySuggestModal v-model:open="showSuggestCategory" />

    </div>

</template>
