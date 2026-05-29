<script setup>
import { computed, ref } from 'vue';
import { useCatalogStore } from '@/stores/catalog';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';

const emit = defineEmits(['apply']);

const catalog = useCatalogStore();
const geo = useGeoStore();
const search = useSearchStore();
const geoErr = ref('');

const sortOptions = [
    { value: 'nearest', label: 'Más cercanos', needsGps: true },
    { value: 'rating', label: 'Mejor valorados', needsGps: false },
    { value: 'recent', label: 'Más recientes', needsGps: false },
];

const selectedCategoryName = computed(() => {
    if (search.selectedCategoryId == null) return null;
    return catalog.categories.find((c) => Number(c.id) === Number(search.selectedCategoryId))?.name || null;
});

const locationSummary = computed(() => {
    const parts = [];
    if (geo.selectedDistrictId) {
        const d = geo.districts.find((x) => Number(x.id) === Number(geo.selectedDistrictId));
        if (d?.name) parts.push(d.name);
    }
    if (geo.selectedProvinceId) {
        const p = geo.provinces.find((x) => Number(x.id) === Number(geo.selectedProvinceId));
        if (p?.name && !parts.length) parts.push(p.name);
    }
    if (geo.selectedDepartmentId) {
        const dep = geo.departments.find((x) => Number(x.id) === Number(geo.selectedDepartmentId));
        if (dep?.name && !parts.length) parts.push(dep.name);
    }
    if (geo.useGps) return 'Cerca de tu ubicación';
    return parts.join(' · ') || 'Todo el Perú';
});

async function onDepartment(e) {
    geo.clearGps();
    await geo.setDepartment(e.target.value || null);
    emit('apply');
}

async function onProvince(e) {
    geo.clearGps();
    await geo.setProvince(e.target.value || null);
    emit('apply');
}

async function onDistrict(e) {
    geo.clearGps();
    await geo.setDistrict(e.target.value || null);
    emit('apply');
}

async function nearMe() {
    geoErr.value = '';
    try {
        geo.clearSelection();
        await geo.useMyLocation();
        search.setSortBy('nearest');
        emit('apply');
    } catch (e) {
        geoErr.value = e.message || 'No se pudo obtener tu ubicación.';
    }
}

function onCategoryChange(e) {
    const v = e.target.value;
    search.setCategory(v === '' ? null : Number(v));
    emit('apply');
}

function onMinRatingChange(e) {
    const v = e.target.value;
    search.setMinRating(v === '' ? null : Number(v));
    emit('apply');
}

function onSortChange(e) {
    search.setSortBy(e.target.value);
    emit('apply');
}

function setView(mode) {
    search.setViewMode(mode);
}

function clearFilters() {
    search.setCategory(null);
    search.setMinRating(null);
    geo.clearSelection();
    geo.clearGps();
    emit('apply');
}
</script>

<template>
    <aside class="search-sidebar" aria-label="Filtros de búsqueda">
        <div class="search-sidebar__block">
            <h3 class="search-sidebar__heading">Ubicación</h3>
            <p class="search-sidebar__hint">{{ locationSummary }}</p>
            <button
                type="button"
                class="search-sidebar__near w-full mb-3"
                @click="nearMe"
            >
                <span class="material-symbols-outlined text-[18px]">my_location</span>
                Cerca de mí
            </button>
            <p v-if="geoErr" class="text-xs text-rose-600 mb-2">{{ geoErr }}</p>
            <label class="search-sidebar__field">
                <span class="search-sidebar__label">Departamento</span>
                <select
                    :value="geo.selectedDepartmentId || ''"
                    class="search-sidebar__select"
                    @change="onDepartment"
                >
                    <option value="">Todos</option>
                    <option v-for="d in geo.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </label>
            <label class="search-sidebar__field">
                <span class="search-sidebar__label">Provincia</span>
                <select
                    :value="geo.selectedProvinceId || ''"
                    :disabled="!geo.selectedDepartmentId"
                    class="search-sidebar__select disabled:opacity-50"
                    @change="onProvince"
                >
                    <option value="">Todas</option>
                    <option v-for="p in geo.provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </label>
            <label class="search-sidebar__field">
                <span class="search-sidebar__label">Distrito</span>
                <select
                    :value="geo.selectedDistrictId || ''"
                    :disabled="!geo.selectedProvinceId"
                    class="search-sidebar__select disabled:opacity-50"
                    @change="onDistrict"
                >
                    <option value="">Todos</option>
                    <option v-for="d in geo.districts" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </label>
        </div>

        <div class="search-sidebar__block">
            <h3 class="search-sidebar__heading">Categoría</h3>
            <select
                :value="search.selectedCategoryId ?? ''"
                class="search-sidebar__select"
                @change="onCategoryChange"
            >
                <option value="">Todas las categorías</option>
                <option v-for="c in catalog.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <p v-if="selectedCategoryName" class="search-sidebar__hint mt-2">
                Filtrando: <strong>{{ selectedCategoryName }}</strong>
            </p>
        </div>

        <div class="search-sidebar__block">
            <h3 class="search-sidebar__heading">Valoración</h3>
            <select
                :value="search.minRating ?? ''"
                class="search-sidebar__select"
                @change="onMinRatingChange"
            >
                <option value="">Cualquiera</option>
                <option :value="3">3+ estrellas</option>
                <option :value="4">4+ estrellas</option>
                <option :value="4.5">4.5+ estrellas</option>
            </select>
        </div>

        <div class="search-sidebar__block">
            <h3 class="search-sidebar__heading">Ordenar por</h3>
            <select
                :value="search.sortBy"
                class="search-sidebar__select"
                @change="onSortChange"
            >
                <option
                    v-for="opt in sortOptions"
                    :key="opt.value"
                    :value="opt.value"
                    :disabled="opt.needsGps && !geo.useGps"
                >
                    {{ opt.label }}{{ opt.needsGps && !geo.useGps ? ' (GPS)' : '' }}
                </option>
            </select>
        </div>

        <div class="search-sidebar__block">
            <h3 class="search-sidebar__heading">Vista</h3>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="search-sidebar__view-btn flex-1"
                    :class="search.viewMode === 'list' ? 'search-sidebar__view-btn--active' : ''"
                    @click="setView('list')"
                >
                    <span class="material-symbols-outlined text-[18px]">view_list</span>
                    Lista
                </button>
                <button
                    type="button"
                    class="search-sidebar__view-btn flex-1"
                    :class="search.viewMode === 'map' ? 'search-sidebar__view-btn--active' : ''"
                    @click="setView('map')"
                >
                    <span class="material-symbols-outlined text-[18px]">map</span>
                    Mapa
                </button>
            </div>
        </div>

        <button type="button" class="search-sidebar__clear" @click="clearFilters">
            Limpiar filtros
        </button>
    </aside>
</template>
