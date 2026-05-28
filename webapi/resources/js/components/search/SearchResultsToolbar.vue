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
    <div class="flex flex-col gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="px-4 py-2 rounded-full text-sm font-bold border transition"
                :class="
                    search.viewMode === 'list'
                        ? 'bg-[#003874] text-white border-[#003874]'
                        : 'bg-white text-slate-700 border-slate-200'
                "
                @click="setView('list')"
            >
                <span class="material-symbols-outlined text-base align-middle mr-1">view_list</span>
                Lista
            </button>
            <button
                type="button"
                class="px-4 py-2 rounded-full text-sm font-bold border transition"
                :class="
                    search.viewMode === 'map'
                        ? 'bg-[#003874] text-white border-[#003874]'
                        : 'bg-white text-slate-700 border-slate-200'
                "
                @click="setView('map')"
            >
                <span class="material-symbols-outlined text-base align-middle mr-1">map</span>
                Mapa
            </button>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-xs font-bold uppercase tracking-wide text-slate-500">
                Ordenar
                <select
                    :value="search.sortBy"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-[#003874] min-w-[10rem]"
                    @change="onSortChange"
                >
                    <option
                        v-for="opt in sortOptions"
                        :key="opt.value"
                        :value="opt.value"
                        :disabled="opt.needsGps && !geo.useGps"
                    >
                        {{ opt.label }}{{ opt.needsGps && !geo.useGps ? ' (usa GPS)' : '' }}
                    </option>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs font-bold uppercase tracking-wide text-slate-500">
                Mín. estrellas
                <select
                    :value="search.minRating ?? ''"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-[#003874] min-w-[8rem]"
                    @change="onMinRatingChange"
                >
                    <option value="">Cualquiera</option>
                    <option :value="3">3+ ★</option>
                    <option :value="4">4+ ★</option>
                    <option :value="4.5">4.5+ ★</option>
                </select>
            </label>
        </div>
    </div>
</template>
