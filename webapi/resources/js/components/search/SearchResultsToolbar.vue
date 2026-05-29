<script setup>

import { useGeoStore } from '@/stores/geo';

import { useSearchStore } from '@/stores/search';



const geo = useGeoStore();

const search = useSearchStore();



const sortOptions = [

    { value: 'nearest', label: 'Más cercanos', needsGps: true },

    { value: 'rating', label: 'Mejor valorados', needsGps: false },

    { value: 'recent', label: 'Más recientes', needsGps: false },

];



function onSortChange(e) {

    search.setSortBy(e.target.value);

    void search.run(1);

}



function onMinRatingChange(e) {

    const v = e.target.value;

    search.setMinRating(v === '' ? null : Number(v));

    void search.run(1);

}



function setView(mode) {

    search.setViewMode(mode);

}

</script>



<template>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-slate-200">

        <div

            class="inline-flex p-0.5 rounded-lg border border-slate-200 bg-slate-50 self-start"

            role="tablist"

            aria-label="Vista de resultados"

        >

            <button

                type="button"

                role="tab"

                :aria-selected="search.viewMode === 'list'"

                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition"

                :class="search.viewMode === 'list' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"

                @click="setView('list')"

            >

                <span class="material-symbols-outlined text-[18px]">view_list</span>

                Lista

            </button>

            <button

                type="button"

                role="tab"

                :aria-selected="search.viewMode === 'map'"

                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition"

                :class="search.viewMode === 'map' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"

                @click="setView('map')"

            >

                <span class="material-symbols-outlined text-[18px]">map</span>

                Mapa

            </button>

        </div>



        <div class="flex flex-wrap items-center gap-3 text-sm">

            <label class="inline-flex items-center gap-2 text-slate-600">

                <span class="text-slate-500">Ordenar</span>

                <select

                    :value="search.sortBy"

                    class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm font-medium text-slate-800 focus:border-[#003874] focus:ring-1 focus:ring-[#003874]/20 outline-none"

                    @change="onSortChange"

                >

                    <option

                        v-for="opt in sortOptions"

                        :key="opt.value"

                        :value="opt.value"

                        :disabled="opt.needsGps && !geo.useGps"

                    >

                        {{ opt.label }}{{ opt.needsGps && !geo.useGps ? ' (requiere GPS)' : '' }}

                    </option>

                </select>

            </label>

            <label class="inline-flex items-center gap-2 text-slate-600">

                <span class="text-slate-500">Mínimo</span>

                <select

                    :value="search.minRating ?? ''"

                    class="rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm font-medium text-slate-800 focus:border-[#003874] focus:ring-1 focus:ring-[#003874]/20 outline-none"

                    @change="onMinRatingChange"

                >

                    <option value="">Cualquiera</option>

                    <option :value="3">3+ estrellas</option>

                    <option :value="4">4+ estrellas</option>

                    <option :value="4.5">4.5+ estrellas</option>

                </select>

            </label>

        </div>

    </div>

</template>

