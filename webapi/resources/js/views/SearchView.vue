<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useCatalogStore } from '@/stores/catalog';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';
import { categoryStyleFor } from '@/components/common/CategoryIcon';
import ServiceCard from '@/components/service/ServiceCard.vue';

const route = useRoute();
const router = useRouter();
const catalog = useCatalogStore();
const geo = useGeoStore();
const search = useSearchStore();

const localKeyword = ref(search.keyword);

onMounted(async () => {
    await Promise.all([catalog.ensureCategories(), geo.ensureDepartments()]);
    if (typeof route.query.q === 'string' && route.query.q.trim()) {
        localKeyword.value = route.query.q.trim();
        search.setKeyword(localKeyword.value);
        await router.replace({ name: 'search' });
    }
    if (!search.searched) await search.run();
});

watch(
    () => search.keyword,
    (v) => {
        if (v !== localKeyword.value) localKeyword.value = v;
    },
);

async function onDepartment(e) {
    await geo.setDepartment(e.target.value);
}
async function onProvince(e) {
    await geo.setProvince(e.target.value);
}
function onDistrict(e) {
    geo.setDistrict(e.target.value);
}

function submitSearch() {
    search.setKeyword(localKeyword.value);
    void search.run();
}

function pickCategory(id) {
    search.setCategory(id);
    void search.run();
}
function clearCategory() {
    search.setCategory(null);
    void search.run();
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 pt-8 pb-12">
        <header class="mb-8 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-[#0b1c30] tracking-tight">Buscar servicios</h1>
            <p class="text-slate-600 mt-2">Filtra por rubro, ubicación o palabras clave.</p>
        </header>
        <form
            @submit.prevent="submitSearch"
            class="w-full bg-white p-4 md:p-2 rounded-xl md:rounded-full shadow-lg border border-slate-100 flex flex-col md:flex-row items-center gap-3 md:gap-2 mb-10"
        >
            <div class="flex-1 w-full flex items-center px-4 gap-3">
                <span class="material-symbols-outlined text-slate-400">search</span>
                <input
                    v-model="localKeyword"
                    type="search"
                    placeholder="¿Qué servicio necesitas?"
                    class="w-full border-none focus:ring-0 text-base placeholder:text-slate-400 bg-transparent outline-none min-w-0"
                />
            </div>
            <div class="hidden md:block w-px h-9 bg-slate-200 shrink-0"></div>
            <div class="w-full md:w-auto flex flex-col sm:flex-row gap-2 px-2 min-w-0">
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
                type="submit"
                class="w-full md:w-auto shrink-0 bg-[#ff7a2b] text-[#602500] px-8 py-3 rounded-full font-bold hover:brightness-105 active:scale-[0.98] transition-all"
            >
                Buscar
            </button>
        </form>

        <section class="mb-10">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="clearCategory"
                    class="px-4 py-2 rounded-full text-sm font-semibold border transition"
                    :class="
                        search.selectedCategoryId == null
                            ? 'bg-[#003874] text-white border-[#003874]'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/40'
                    "
                >
                    Todas
                </button>
                <button
                    v-for="c in catalog.categories"
                    :key="c.id"
                    type="button"
                    @click="pickCategory(c.id)"
                    class="px-4 py-2 rounded-full text-sm font-semibold border transition flex items-center gap-2"
                    :class="
                        search.selectedCategoryId === c.id
                            ? 'bg-[#003874] text-white border-[#003874]'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/40'
                    "
                >
                    <span class="material-symbols-outlined text-base">
                        {{ categoryStyleFor(c.name).icon }}
                    </span>
                    {{ c.name }}
                </button>
            </div>
        </section>

        <section>
            <div class="flex justify-between items-baseline gap-4 mb-6">
                <h2 class="text-2xl font-semibold text-[#0b1c30] tracking-tight">Resultados</h2>
                <p v-if="search.searched && !search.loading && !search.error" class="text-sm text-slate-500">
                    {{ search.results.length }} resultado(s)
                </p>
            </div>
            <p v-if="search.loading" class="py-16 text-center text-slate-500 font-medium">Buscando…</p>
            <div
                v-else-if="search.error"
                class="rounded-xl border border-red-100 bg-red-50 text-red-800 text-sm font-medium px-4 py-3"
            >
                {{ search.error }}
            </div>
            <div
                v-else-if="search.searched && search.results.length === 0"
                class="rounded-xl border border-slate-200 bg-white py-16 px-6 text-center text-slate-600"
            >
                Sin resultados. Prueba otras palabras, rubro o zona.
            </div>
            <div
                v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
            >
                <ServiceCard v-for="s in search.results" :key="s.service_id" :service="s" />
            </div>
        </section>
    </div>
</template>
