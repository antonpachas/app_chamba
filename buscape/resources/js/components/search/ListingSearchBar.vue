<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';
import { loadRecentSearches } from '@/utils/searchHistory';
import AppAlert from '@/components/ui/AppAlert.vue';

const props = defineProps({
    autoRunOnGeo: { type: Boolean, default: false },
    compactGeo: { type: Boolean, default: false },
    variant: { type: String, default: 'default' },
    /** Muestra enlace de filtros geo a la derecha (solo home). */
    showFilterLink: { type: Boolean, default: false },
    filterExpanded: { type: Boolean, default: false },
});

const emit = defineEmits(['search', 'apply-recent', 'toggle-geo-filters']);

const geo = useGeoStore();
const search = useSearchStore();
const localKeyword = ref(search.keyword);
const geoErr = ref('');
const searchFocused = ref(false);
const rootRef = ref(null);
const recentList = ref([]);

const isHomeVariant = computed(() => props.variant === 'home');
const recentFive = computed(() => recentList.value.slice(0, 5));
const showRecentDropdown = computed(
    () => isHomeVariant.value && searchFocused.value && recentFive.value.length > 0,
);

function refreshRecents() {
    recentList.value = loadRecentSearches();
}

watch(
    () => search.keyword,
    (v) => {
        if (v !== localKeyword.value) localKeyword.value = v;
    },
);

onMounted(() => {
    refreshRecents();
    document.addEventListener('mousedown', onDocMouseDown);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', onDocMouseDown);
});

function onDocMouseDown(e) {
    if (!searchFocused.value) return;
    const root = rootRef.value;
    if (root && !root.contains(e.target)) {
        searchFocused.value = false;
    }
}

function onSearchFocus() {
    refreshRecents();
    searchFocused.value = true;
}

async function onDepartment(e) {
    geo.clearGps();
    await geo.setDepartment(e.target.value);
    if (props.autoRunOnGeo && search.searched) void search.run(1);
}

async function onProvince(e) {
    geo.clearGps();
    await geo.setProvince(e.target.value);
    if (props.autoRunOnGeo && search.searched) void search.run(1);
}

function onDistrict(e) {
    geo.clearGps();
    geo.setDistrict(e.target.value);
    if (props.autoRunOnGeo && search.searched) void search.run(1);
}

function submit() {
    search.setKeyword(localKeyword.value);
    searchFocused.value = false;
    emit('search');
}

function pickRecent(item) {
    localKeyword.value = item.keyword || item.label || '';
    search.setKeyword(localKeyword.value);
    searchFocused.value = false;
    emit('apply-recent', item);
    emit('search');
}

async function nearMe() {
    geoErr.value = '';
    try {
        geo.clearSelection();
        await geo.useMyLocation();
        search.setKeyword(localKeyword.value);
        search.setSortBy('nearest');
        searchFocused.value = false;
        emit('search');
    } catch (e) {
        geoErr.value = e.message || 'No se pudo obtener tu ubicación.';
    }
}

defineExpose({ refreshRecents });
</script>

<template>
    <div ref="rootRef" class="search-bar-root">
        <AppAlert v-if="geoErr" type="error" class="mb-3">
            {{ geoErr }}
        </AppAlert>

        <form @submit.prevent="submit" class="w-full">
            <div
                class="relative"
                :class="isHomeVariant && showRecentDropdown ? 'z-30' : ''"
            >
                <div
                    class="flex flex-col md:flex-row md:items-stretch gap-0 md:gap-0 overflow-hidden"
                    :class="[
                        isHomeVariant ? 'search-bar-unified' : 'flex-col gap-2',
                        isHomeVariant && showRecentDropdown ? 'search-bar-unified--open' : '',
                    ]"
                >
                    <div
                        class="flex flex-1 items-center gap-2 min-w-0"
                        :class="isHomeVariant ? 'search-bar-unified__field px-4 py-3' : 'rounded-lg border border-slate-200 bg-white px-3 py-2.5'"
                    >
                        <span class="material-symbols-outlined text-slate-400 text-[22px] shrink-0" aria-hidden="true">search</span>
                        <input
                            v-model="localKeyword"
                            type="search"
                            placeholder="¿Qué buscas? Ej. ferretería, veterinaria, taller"
                            class="w-full border-none bg-transparent text-[15px] text-slate-900 placeholder:text-slate-400 outline-none focus:ring-0 min-w-0"
                            autocomplete="off"
                            @focus="onSearchFocus"
                        />
                    </div>

                    <template v-if="isHomeVariant">
                        <div class="hidden md:block w-px bg-slate-200 self-stretch my-2" aria-hidden="true" />
                        <button
                            type="button"
                            class="search-bar-unified__action px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 inline-flex items-center justify-center gap-2 border-t md:border-t-0 border-slate-200"
                            title="Usar tu ubicación"
                            @click="nearMe"
                        >
                            <span class="material-symbols-outlined text-[20px] text-slate-500">my_location</span>
                            <span class="hidden sm:inline">Cerca de mí</span>
                        </button>
                        <button
                            type="submit"
                            class="search-bar-unified__submit px-6 py-3 text-sm font-semibold text-white bg-[#003874] hover:bg-[#002e60] border-t md:border-t-0 border-slate-200 md:rounded-r-xl"
                        >
                            Buscar
                        </button>
                    </template>

                    <template v-else>
                        <div class="flex flex-col sm:flex-row gap-2 mt-2 md:mt-0">
                            <button
                                type="button"
                                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 inline-flex items-center justify-center gap-2"
                                @click="nearMe"
                            >
                                <span class="material-symbols-outlined text-lg">my_location</span>
                                Cerca de mí
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg bg-[#003874] text-white px-6 py-2.5 text-sm font-semibold hover:bg-[#002e60]"
                            >
                                Buscar
                            </button>
                        </div>
                    </template>
                </div>

                <ul
                    v-if="showRecentDropdown"
                    class="search-recent-dropdown"
                    role="listbox"
                    aria-label="Búsquedas recientes"
                >
                    <li
                        v-for="item in recentFive"
                        :key="item.ts"
                        role="option"
                    >
                        <button
                            type="button"
                            class="search-recent-dropdown__item"
                            @mousedown.prevent="pickRecent(item)"
                        >
                            <span class="material-symbols-outlined text-[18px] text-slate-400 shrink-0">history</span>
                            <span class="truncate">{{ item.label }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div
                v-if="!compactGeo"
                class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2"
            >
                <select
                    :value="geo.selectedDepartmentId || ''"
                    class="ui-select text-sm py-2"
                    @change="onDepartment"
                >
                    <option value="">Departamento</option>
                    <option v-for="d in geo.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <select
                    :value="geo.selectedProvinceId || ''"
                    :disabled="!geo.selectedDepartmentId"
                    class="ui-select text-sm py-2 disabled:opacity-50"
                    @change="onProvince"
                >
                    <option value="">Provincia</option>
                    <option v-for="p in geo.provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select
                    :value="geo.selectedDistrictId || ''"
                    :disabled="!geo.selectedProvinceId"
                    class="ui-select text-sm py-2 disabled:opacity-50"
                    @change="onDistrict"
                >
                    <option value="">Distrito</option>
                    <option v-for="d in geo.districts" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </div>
        </form>

        <div
            v-if="isHomeVariant && showFilterLink"
            class="flex items-center justify-between gap-3 mt-1.5 min-h-[1.25rem]"
        >
            <span v-if="recentFive.length" class="text-xs text-white/60 truncate">
                {{ recentFive.length }} búsqueda{{ recentFive.length === 1 ? '' : 's' }} reciente{{ recentFive.length === 1 ? '' : 's' }}
            </span>
            <span v-else aria-hidden="true"></span>
            <button
                type="button"
                class="home-hero-band__link text-xs shrink-0"
                @click="emit('toggle-geo-filters')"
            >
                {{ filterExpanded ? 'Ocultar filtros de zona' : 'Filtrar por departamento, provincia o distrito' }}
            </button>
        </div>

        <p v-if="geo.useGps" class="mt-1.5 text-xs text-emerald-200/90">
            Mostrando resultados cerca de tu ubicación.
        </p>
    </div>
</template>

<style scoped>
.search-bar-unified--open {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.search-recent-dropdown {
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    margin: 0;
    padding: 0.25rem 0;
    list-style: none;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 0.75rem 0.75rem;
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.18);
    max-height: 14rem;
    overflow-y: auto;
    z-index: 40;
}

.search-recent-dropdown__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.625rem 1rem;
    border: 0;
    background: transparent;
    text-align: left;
    font-size: 0.875rem;
    color: #334155;
    cursor: pointer;
    transition: background 0.12s ease;
}
.search-recent-dropdown__item:hover,
.search-recent-dropdown__item:focus {
    background: #f1f5f9;
    outline: none;
}
</style>
