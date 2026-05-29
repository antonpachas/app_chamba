<script setup>
import { ref, watch } from 'vue';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';
import AppAlert from '@/components/ui/AppAlert.vue';

const props = defineProps({
    /** Al cambiar ubigeo, vuelve a buscar si ya hubo una búsqueda */
    autoRunOnGeo: { type: Boolean, default: false },
    /** Oculta selects de zona (se muestran con toggle en SearchView) */
    compactGeo: { type: Boolean, default: false },
});

const emit = defineEmits(['search']);

const geo = useGeoStore();
const search = useSearchStore();
const localKeyword = ref(search.keyword);
const geoErr = ref('');

watch(
    () => search.keyword,
    (v) => {
        if (v !== localKeyword.value) localKeyword.value = v;
    },
);

async function onDepartment(e) {
    geo.clearGps();
    await geo.setDepartment(e.target.value);
    if (props.autoRunOnGeo && search.searched) void search.run();
}

async function onProvince(e) {
    geo.clearGps();
    await geo.setProvince(e.target.value);
    if (props.autoRunOnGeo && search.searched) void search.run();
}

function onDistrict(e) {
    geo.clearGps();
    geo.setDistrict(e.target.value);
    if (props.autoRunOnGeo && search.searched) void search.run();
}

function submit() {
    search.setKeyword(localKeyword.value);
    emit('search');
}

async function nearMe() {
    geoErr.value = '';
    try {
        geo.clearSelection();
        await geo.useMyLocation();
        search.setKeyword(localKeyword.value);
        search.setSortBy('nearest');
        emit('search');
    } catch (e) {
        geoErr.value = e.message || 'No se pudo obtener tu ubicación.';
    }
}
</script>

<template>
    <div>
        <AppAlert v-if="geoErr" type="error" class="mb-4 max-w-xl" :class="hero ? 'mx-auto' : 'mx-auto md:mx-0'">
            {{ geoErr }}
        </AppAlert>
        <form
            @submit.prevent="submit"
            class="ui-card w-full p-4 md:p-2 md:rounded-full flex flex-col md:flex-row items-center gap-3 md:gap-2"
        >
            <div class="flex-1 w-full flex items-center px-4 gap-3">
                <span class="material-symbols-outlined text-slate-400">search</span>
                <input
                    v-model="localKeyword"
                    type="search"
                    placeholder="¿Qué buscas? (ej. ferretería, disco, taller)"
                    class="w-full border-none focus:ring-0 text-base placeholder:text-slate-400 bg-transparent outline-none min-w-0"
                />
            </div>
            <div class="hidden md:block w-px h-9 bg-slate-200 shrink-0"></div>
            <div v-if="!compactGeo" class="w-full md:w-auto flex flex-col sm:flex-row gap-2 px-2 min-w-0">
                <select
                    :value="geo.selectedDepartmentId || ''"
                    @change="onDepartment"
                    class="border-none rounded-lg bg-slate-50 md:bg-transparent px-2 py-2 text-xs font-semibold uppercase tracking-wide text-[#003874] focus:ring-0 w-full md:w-auto"
                >
                    <option value="">Departamento</option>
                    <option v-for="d in geo.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
                <select
                    :value="geo.selectedProvinceId || ''"
                    @change="onProvince"
                    :disabled="!geo.selectedDepartmentId"
                    class="border-none rounded-lg bg-slate-50 md:bg-transparent px-2 py-2 text-xs font-semibold uppercase tracking-wide text-[#003874] focus:ring-0 w-full md:w-auto disabled:opacity-40"
                >
                    <option value="">Provincia</option>
                    <option v-for="p in geo.provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select
                    :value="geo.selectedDistrictId || ''"
                    @change="onDistrict"
                    :disabled="!geo.selectedProvinceId"
                    class="border-none rounded-lg bg-slate-50 md:bg-transparent px-2 py-2 text-xs font-semibold uppercase tracking-wide text-[#003874] focus:ring-0 w-full md:w-auto disabled:opacity-40"
                >
                    <option value="">Distrito</option>
                    <option v-for="d in geo.districts" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
            </div>
            <button
                type="button"
                class="w-full md:w-auto shrink-0 rounded-full border border-[#003874]/30 bg-white px-5 py-3 text-sm font-bold text-[#003874] hover:bg-slate-50 transition-all inline-flex items-center justify-center gap-1.5"
                title="Usar tu ubicación actual"
                @click="nearMe"
            >
                <span class="material-symbols-outlined text-lg">my_location</span>
                Cerca de mí
            </button>
            <button
                type="submit"
                class="w-full md:w-auto shrink-0 btn-grad-warm px-8 py-3 rounded-full font-bold active:scale-[0.98]"
            >
                Buscar
            </button>
        </form>
        <p v-if="geo.useGps" class="text-center text-xs text-emerald-700 font-semibold mt-3">
            Buscando cerca de tu ubicación (GPS)
        </p>
        <p v-else class="text-center text-xs text-slate-500 mt-3">
            Filtra por departamento, provincia y distrito, o usa «Cerca de mí».
        </p>
    </div>
</template>
