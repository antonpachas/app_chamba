<script setup>

import { computed, nextTick, onMounted, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import { scrollToHash } from '@/utils/scroll';

import { loadRecentSearches, pushRecentSearch } from '@/utils/searchHistory';

import { useCatalogStore } from '@/stores/catalog';

import { useGeoStore } from '@/stores/geo';

import { useSearchStore } from '@/stores/search';

import { platform } from '@/services/features';

import ServiceCard from '@/components/service/ServiceCard.vue';

import AdSlot from '@/components/ads/AdSlot.vue';

import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';

import ListingSearchBar from '@/components/search/ListingSearchBar.vue';

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

const recentSearches = ref(loadRecentSearches());



const gridClass = computed(() => {

    const sm = Math.max(1, Math.min(2, Number(platform.search_grid_columns_sm) || 1));

    const md = Math.max(1, Math.min(4, Number(platform.search_grid_columns_md) || 2));

    if (sm >= 2) {

        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5';

    }

    if (md >= 3) {

        return 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5';

    }

    if (md >= 2) {

        return 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5';

    }

    return 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5';

});



const selectedCategoryName = computed(() => {

    if (search.selectedCategoryId == null) return null;

    return catalog.categories.find((c) => Number(c.id) === Number(search.selectedCategoryId))?.name || null;

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

    await search.run(1);

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

    <div class="home-page">

        <section class="home-search-strip border-b border-slate-200/80">

            <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6 py-4 md:py-5">

                <div id="buscar" class="scroll-mt-24">

                    <ListingSearchBar

                        variant="home"

                        auto-run-on-geo

                        :compact-geo="!showGeoFilters"

                        @search="submitSearch"

                    />

                    <button

                        type="button"

                        class="mt-2 text-sm text-slate-500 hover:text-slate-800 transition-colors"

                        @click="showGeoFilters = !showGeoFilters"

                    >

                        {{ showGeoFilters ? 'Ocultar filtros de zona' : 'Filtrar por departamento, provincia o distrito' }}

                    </button>

                </div>



                <GuestBrowseBanner :meta="search.guestMeta" compact class="mt-3" />



                <div

                    v-if="recentSearches.length"

                    class="mt-3 flex flex-wrap items-center gap-2"

                    aria-label="Búsquedas recientes"

                >

                    <span class="text-xs text-slate-400">Recientes:</span>

                    <button

                        v-for="item in recentSearches.slice(0, 5)"

                        :key="item.ts"

                        type="button"

                        class="filter-chip filter-chip--muted"

                        @click="applyRecent(item)"

                    >

                        {{ item.label }}

                    </button>

                </div>

            </div>

        </section>



        <HomeFeaturedSlider />



        <HomeCategoriesSection

            class="py-4 md:py-5 bg-white border-b border-slate-200/60"

            :categories="catalog.categories"

            :selected-id="search.selectedCategoryId"

            @select="pickCategory"

            @clear="clearCategory"

        />

        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6 pb-2">

            <CategorySuggestCallout inline @open="showSuggestCategory = true" />

        </div>



        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6 py-8 md:py-10">

            <NotificationsBanner v-if="auth.isCliente" class="mb-6" />



            <section id="resultados" class="scroll-mt-24" aria-label="Resultados de búsqueda">

                <div class="flex flex-wrap items-end justify-between gap-3 mb-4">

                    <div>

                        <h2 class="text-lg font-semibold text-slate-900">Anuncios cerca de ti</h2>

                        <p v-if="selectedCategoryName" class="text-sm text-slate-500 mt-0.5">

                            Categoría: {{ selectedCategoryName }}

                        </p>

                    </div>

                    <p

                        v-if="search.searched && !search.loading && !search.error"

                        class="text-sm text-slate-500 tabular-nums"

                    >

                        {{ search.searchMeta?.pagination?.total ?? search.results.length }}

                        {{ (search.searchMeta?.pagination?.total ?? search.results.length) === 1 ? 'anuncio' : 'anuncios' }}

                    </p>

                </div>



                <SearchResultsToolbar

                    v-if="search.searched && !search.loading && !search.error && search.results.length"

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

                    class="rounded-xl border border-slate-200 bg-white py-16 px-6 text-center"

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



                <div v-else :class="gridClass">

                    <ServiceCard v-for="s in search.results" :key="s.service_id" :service="s" />

                </div>



                <SearchResultsPagination v-if="search.viewMode === 'list' && search.results.length" />

            </section>



            <AdSlot placement="home" class="mt-10" />

        </div>



        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6 pb-12 md:pb-16">

            <DiscoverMarketingSections />

        </div>



        <CategorySuggestModal v-model:open="showSuggestCategory" />

    </div>

</template>
