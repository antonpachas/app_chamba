<script setup>

import { computed, ref, watch } from 'vue';

import { useGeoStore } from '@/stores/geo';

import { useSearchStore } from '@/stores/search';

import AppAlert from '@/components/ui/AppAlert.vue';



const props = defineProps({

    autoRunOnGeo: { type: Boolean, default: false },

    compactGeo: { type: Boolean, default: false },

    /** home = barra unificada estilo directorio profesional */

    variant: { type: String, default: 'default' },

});



const emit = defineEmits(['search']);



const geo = useGeoStore();

const search = useSearchStore();

const localKeyword = ref(search.keyword);

const geoErr = ref('');



const isHomeVariant = computed(() => props.variant === 'home');



watch(

    () => search.keyword,

    (v) => {

        if (v !== localKeyword.value) localKeyword.value = v;

    },

);



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

        <AppAlert v-if="geoErr" type="error" class="mb-3">

            {{ geoErr }}

        </AppAlert>



        <form @submit.prevent="submit" class="w-full">

            <!-- Barra principal -->

            <div

                class="flex flex-col md:flex-row md:items-stretch gap-0 md:gap-0 overflow-hidden"

                :class="isHomeVariant ? 'search-bar-unified' : 'flex-col gap-2'"

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



            <!-- Filtros geográficos -->

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



        <p v-if="geo.useGps" class="mt-2 text-xs text-emerald-700">

            Mostrando resultados cerca de tu ubicación.

        </p>

    </div>

</template>


