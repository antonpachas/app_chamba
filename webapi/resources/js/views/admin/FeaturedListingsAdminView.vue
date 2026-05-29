<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const err = ref('');
const ok = ref('');
const busy = ref(null);

const featured = ref([]);
const featuredLoading = ref(false);

const searchQ = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);

async function loadFeatured() {
    featuredLoading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/listings', {
            auth: true,
            params: { filter: 'home_featured', per_page: 50 },
        });
        featured.value = (r.data || []).sort(
            (a, b) => (a.home_featured_sort ?? 999) - (b.home_featured_sort ?? 999),
        );
    } catch (e) {
        err.value = e.message;
        featured.value = [];
    } finally {
        featuredLoading.value = false;
    }
}

async function searchListings() {
    if (!searchQ.value.trim()) return;
    searchLoading.value = true;
    try {
        const r = await api.get('/admin/listings', {
            auth: true,
            params: { q: searchQ.value.trim(), filter: 'visible', per_page: 10 },
        });
        searchResults.value = r.data || [];
    } catch (e) {
        err.value = e.message;
        searchResults.value = [];
    } finally {
        searchLoading.value = false;
    }
}

async function addFeatured(row) {
    busy.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/listings/${row.id}/feature-home`, {}, { auth: true });
        ok.value = r.message || 'Agregado al carrusel.';
        searchResults.value = searchResults.value.filter((x) => x.id !== row.id);
        await loadFeatured();
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function removeFeatured(row) {
    if (!confirm(`¿Quitar «${row.title}» del carrusel del inicio?`)) return;
    busy.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/listings/${row.id}/unfeature-home`, {}, { auth: true });
        ok.value = r.message || 'Quitado del carrusel.';
        await loadFeatured();
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function moveFeatured(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= featured.value.length) return;
    const next = [...featured.value];
    const tmp = next[index];
    next[index] = next[target];
    next[target] = tmp;
    featured.value = next;
    busy.value = 'reorder';
    try {
        await api.post(
            '/admin/listings/home-featured/reorder',
            { ids: next.map((x) => x.id) },
            { auth: true },
        );
        ok.value = 'Orden actualizado.';
    } catch (e) {
        err.value = e.message;
        await loadFeatured();
    } finally {
        busy.value = null;
    }
}

const featuredCount = computed(() => featured.value.length);

onMounted(loadFeatured);
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Administración</p>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Destacados en inicio</h1>
                <p class="text-slate-600 mt-1">
                    Anuncios que aparecen en el carrusel principal del directorio ({{ featuredCount }} activos).
                </p>
            </div>
            <RouterLink
                :to="{ name: 'admin-moderation' }"
                class="text-sm font-bold text-[#003874] hover:underline no-underline"
            >
                ← Moderación
            </RouterLink>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 mb-8">
            <h2 class="text-lg font-bold text-slate-900 mb-3">Agregar anuncio al carrusel</h2>
            <div class="flex flex-wrap gap-2 mb-4">
                <input
                    v-model="searchQ"
                    type="search"
                    placeholder="Buscar por título, negocio o email…"
                    class="flex-1 min-w-[200px] rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                    @keyup.enter="searchListings"
                />
                <AppButton variant="primary" :loading="searchLoading" @click="searchListings">Buscar visibles</AppButton>
            </div>
            <p v-if="searchLoading" class="text-sm text-slate-500">Buscando…</p>
            <ul v-else-if="searchResults.length" class="space-y-2">
                <li
                    v-for="row in searchResults"
                    :key="row.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 truncate">{{ row.title }}</p>
                        <p class="text-xs text-slate-500">{{ row.provider?.business_name || row.provider?.user_email }}</p>
                    </div>
                    <AppButton
                        v-if="!row.home_featured"
                        variant="outline"
                        size="sm"
                        :loading="busy === row.id"
                        @click="addFeatured(row)"
                    >
                        Destacar en inicio
                    </AppButton>
                    <span v-else class="text-xs font-bold text-emerald-700">Ya destacado</span>
                </li>
            </ul>
            <p v-else class="text-sm text-slate-500">Busca anuncios visibles para agregarlos al carrusel.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 mb-4">Carrusel actual</h2>
            <p v-if="featuredLoading" class="text-slate-500 py-8 text-center">Cargando…</p>
            <p v-else-if="!featured.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
                Aún no hay anuncios destacados en el inicio.
            </p>
            <div v-else class="space-y-3">
                <article
                    v-for="(row, index) in featured"
                    :key="row.id"
                    class="rounded-2xl border border-slate-200 bg-white p-4 flex flex-wrap gap-4 items-center"
                >
                    <span class="text-xs font-bold text-slate-400 w-6">{{ index + 1 }}</span>
                    <img
                        v-if="row.cover_image_url"
                        :src="row.cover_image_url"
                        alt=""
                        class="w-20 h-20 object-cover rounded-xl ring-1 ring-slate-200 shrink-0"
                    />
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-slate-900">{{ row.title }}</h3>
                        <p class="text-sm text-slate-600">{{ row.provider?.business_name }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <AppButton
                            variant="ghost"
                            size="sm"
                            :disabled="index === 0 || busy === 'reorder'"
                            @click="moveFeatured(index, -1)"
                        >
                            ↑
                        </AppButton>
                        <AppButton
                            variant="ghost"
                            size="sm"
                            :disabled="index === featured.length - 1 || busy === 'reorder'"
                            @click="moveFeatured(index, 1)"
                        >
                            ↓
                        </AppButton>
                        <AppButton
                            variant="outline"
                            size="sm"
                            :loading="busy === row.id"
                            @click="removeFeatured(row)"
                        >
                            Quitar
                        </AppButton>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
