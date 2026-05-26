<script setup>
import { onMounted, ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useCatalogStore } from '@/stores/catalog';
import { useGeoStore } from '@/stores/geo';
import { useSearchStore } from '@/stores/search';
import { categoryStyleFor } from '@/components/common/CategoryIcon';
import ServiceCard from '@/components/service/ServiceCard.vue';

const router = useRouter();
const catalog = useCatalogStore();
const geo = useGeoStore();
const search = useSearchStore();

const localKeyword = ref('');

onMounted(async () => {
    await Promise.all([catalog.ensureCategories(), geo.ensureDepartments()]);
    if (!search.searched) {
        await search.run();
    }
});

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
    router.push({ name: 'search' });
}

function pickCategory(id) {
    search.setCategory(id);
    router.push({ name: 'search' });
}
</script>

<template>
    <div>
        <section id="hero" class="relative overflow-hidden">
            <div class="bg-grad-hero text-white">
                <div class="max-w-7xl mx-auto px-4 py-16 md:py-24 text-center flex flex-col items-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur border border-white/20 px-4 py-1.5 text-xs font-bold uppercase tracking-widest mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ff9c2b]"></span>
                        Marketplace de servicios locales
                    </span>
                    <h1 class="text-4xl md:text-6xl font-black leading-tight tracking-tight max-w-3xl">
                        Encuentra al
                        <span class="bg-gradient-to-r from-[#ff9c2b] via-[#ff7a2b] to-[#ff5e7e] bg-clip-text text-transparent">experto ideal</span>
                        para tu hogar
                    </h1>
                    <p class="mt-5 text-base md:text-lg text-white/85 max-w-xl">
                        Plomeros, electricistas, carpinteros y más. Compara, contacta y paga seguro con custodia Chamba.
                    </p>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 -mt-10 md:-mt-12 relative z-10">
            <form
                @submit.prevent="submitSearch"
                class="w-full bg-white p-4 md:p-2 rounded-xl md:rounded-full shadow-2xl shadow-[#003874]/10 border border-slate-100 flex flex-col md:flex-row items-center gap-3 md:gap-2"
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
                    class="w-full md:w-auto shrink-0 btn-grad-warm px-8 py-3 rounded-full font-bold active:scale-[0.98]"
                >
                    Buscar
                </button>
            </form>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 md:px-8 mt-16 mb-16">
            <div class="flex justify-between items-end mb-8 flex-wrap gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Explora</p>
                    <h2 class="text-2xl md:text-3xl font-bold text-[#0b1c30] tracking-tight">Categorías populares</h2>
                </div>
                <RouterLink :to="{ name: 'search' }" class="text-[#003874] font-semibold text-sm hover:underline">
                    Ver todas
                </RouterLink>
            </div>
            <p v-if="catalog.categoriesLoading" class="text-sm text-slate-500">Cargando categorías…</p>
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <button
                    v-for="c in catalog.categories"
                    :key="c.id"
                    type="button"
                    @click="pickCategory(c.id)"
                    class="group flex flex-col items-center gap-3 p-6 bg-white rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all"
                >
                    <div
                        class="w-16 h-16 rounded-2xl flex items-center justify-center transition-colors"
                        :class="categoryStyleFor(c.name).box"
                    >
                        <span class="material-symbols-outlined text-3xl">
                            {{ categoryStyleFor(c.name).icon }}
                        </span>
                    </div>
                    <span class="font-semibold text-slate-900 text-center text-sm leading-snug">{{ c.name }}</span>
                </button>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 md:px-8 mb-16">
            <div class="flex justify-between items-end mb-8 gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-[#ff5e7e]">Top picks</p>
                    <h2 class="text-2xl md:text-3xl font-bold text-[#0b1c30] tracking-tight">Servicios destacados</h2>
                </div>
                <RouterLink :to="{ name: 'search' }" class="text-[#003874] font-semibold text-sm hover:underline">
                    Ver todos
                </RouterLink>
            </div>
            <p v-if="search.loading" class="text-slate-500">Cargando…</p>
            <div v-else-if="search.results.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <ServiceCard
                    v-for="(s, idx) in search.results.slice(0, 4)"
                    :key="s.service_id"
                    :service="s"
                    :featured="idx < 2"
                />
            </div>
            <p v-else class="text-slate-500 text-sm">Aún no hay servicios disponibles.</p>
        </section>

        <section
            id="como-funciona"
            class="scroll-mt-24 mx-4 md:mx-8 max-w-[calc(100%-2rem)] md:max-w-7xl md:mx-auto bg-white rounded-3xl p-8 md:p-16 border border-slate-100 relative overflow-hidden mb-16 shadow-sm"
        >
            <div class="relative z-10">
                <p class="text-center text-xs font-bold uppercase tracking-widest text-[#0ea5e9] mb-2">3 pasos</p>
                <h2 class="text-2xl md:text-3xl font-bold text-center text-[#0b1c30] mb-12">¿Cómo funciona Chamba?</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-grad-brand text-white flex items-center justify-center font-black text-2xl mb-6 glow-blue">1</div>
                        <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Busca el servicio</h3>
                        <p class="text-slate-600 text-sm">Explora categorías y encuentra profesionales por zona.</p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-grad-violet text-white flex items-center justify-center font-black text-2xl mb-6 shadow-lg shadow-[#7c3aed]/30">2</div>
                        <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Compara y cotiza</h3>
                        <p class="text-slate-600 text-sm">Revisa reputación y precio antes de contratar.</p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-grad-warm text-white flex items-center justify-center font-black text-2xl mb-6 glow-coral">3</div>
                        <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Paga con confianza</h3>
                        <p class="text-slate-600 text-sm">Tu pago queda en custodia. Liberas cuando termina el trabajo.</p>
                    </div>
                </div>
                <div class="mt-12 text-center">
                    <RouterLink
                        :to="{ name: 'search' }"
                        class="inline-flex btn-grad-primary px-10 py-4 rounded-full font-bold text-lg active:scale-[0.99] no-underline"
                    >
                        ¡Empieza ahora!
                    </RouterLink>
                </div>
            </div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 w-72 h-72 bg-[#0ea5e9]/15 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -top-24 -left-24 w-72 h-72 bg-[#ff7a2b]/15 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute top-1/2 -translate-y-1/2 right-1/3 w-64 h-64 bg-[#7c3aed]/10 rounded-full blur-3xl"></div>
        </section>
    </div>
</template>
