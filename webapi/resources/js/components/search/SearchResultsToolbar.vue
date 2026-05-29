<script setup>
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';

const geo = useGeoStore();
const search = useSearchStore();

const sortOptions = [
    { value: 'nearest', label: 'Cercanos', needsGps: true },
    { value: 'rating', label: 'Valorados', needsGps: false },
    { value: 'recent', label: 'Recientes', needsGps: false },
];

function onSortChange(e) {
    search.setSortBy(e.target.value);
    void search.run();
}

function onMinRatingChange(e) {
    const v = e.target.value;
    search.setMinRating(v === '' ? null : Number(v));
    void search.run();
}

function setView(mode) {
    search.setViewMode(mode);
}
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 p-3 rounded-2xl bg-white border border-slate-100 shadow-sm">
        <div
            class="inline-flex p-1 rounded-xl bg-slate-100 self-start sm:self-auto"
            role="tablist"
            aria-label="Vista de resultados"
        >
            <button
                type="button"
                role="tab"
                :aria-selected="search.viewMode === 'list'"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold transition"
                :class="search.viewMode === 'list' ? 'bg-white text-[#003874] shadow-sm' : 'text-slate-600'"
                @click="setView('list')"
            >
                <span class="material-symbols-outlined text-[18px]">view_list</span>
                Lista
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="search.viewMode === 'map'"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold transition"
                :class="search.viewMode === 'map' ? 'bg-white text-[#003874] shadow-sm' : 'text-slate-600'"
                @click="setView('map')"
            >
                <span class="material-symbols-outlined text-[18px]">map</span>
                Mapa
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-bold uppercase text-slate-400">Orden</span>
                <select
                    :value="search.sortBy"
                    class="border-0 bg-transparent font-semibold text-[#003874] focus:ring-0 text-sm py-0 pr-6"
                    @change="onSortChange"
                >
                    <option
                        v-for="opt in sortOptions"
                        :key="opt.value"
                        :value="opt.value"
                        :disabled="opt.needsGps && !geo.useGps"
                    >
                        {{ opt.label }}{{ opt.needsGps && !geo.useGps ? ' · GPS' : '' }}
                    </option>
                </select>
            </label>
            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-bold uppercase text-slate-400">★</span>
                <select
                    :value="search.minRating ?? ''"
                    class="border-0 bg-transparent font-semibold text-[#003874] focus:ring-0 text-sm py-0 pr-6"
                    @change="onMinRatingChange"
                >
                    <option value="">Todas</option>
                    <option :value="3">3+</option>
                    <option :value="4">4+</option>
                    <option :value="4.5">4.5+</option>
                </select>
            </label>
        </div>
    </div>
</template>
