<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import Money from '@/components/common/Money.vue';

const err = ref('');
const ok = ref('');
const busy = ref(null);

const listingQ = ref('');
const listingFilter = ref('all');
const listings = ref([]);
const listingsMeta = ref({ total: 0, current_page: 1, last_page: 1 });
const listingsLoading = ref(false);

const hideReason = ref('');
const modalListing = ref(null);

async function loadListings(page = 1) {
    listingsLoading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/listings', {
            auth: true,
            params: {
                q: listingQ.value || undefined,
                filter: listingFilter.value,
                page,
                per_page: 20,
            },
        });
        listings.value = r.data || [];
        listingsMeta.value = r.meta || listingsMeta.value;
    } catch (e) {
        err.value = e.message;
        listings.value = [];
    } finally {
        listingsLoading.value = false;
    }
}

onMounted(() => loadListings());

function openHideModal(row) {
    modalListing.value = row;
    hideReason.value = '';
}

async function confirmHide() {
    if (!modalListing.value) return;
    busy.value = modalListing.value.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(
            `/admin/listings/${modalListing.value.id}/hide`,
            { reason: hideReason.value || null },
            { auth: true },
        );
        ok.value = r.message || 'Anuncio ocultado.';
        modalListing.value = null;
        await loadListings(listingsMeta.value.current_page);
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function restoreListing(row) {
    if (!confirm(`¿Mostrar de nuevo el anuncio «${row.title}»?`)) return;
    busy.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/listings/${row.id}/restore`, {}, { auth: true });
        ok.value = r.message || 'Anuncio restaurado.';
        await loadListings(listingsMeta.value.current_page);
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

function listingStatusLabel(row) {
    if (row.admin_hidden) return { text: 'Oculto (admin)', cls: 'bg-rose-100 text-rose-800' };
    if (row.is_visible) return { text: 'Visible', cls: 'bg-emerald-100 text-emerald-800' };
    if (row.expires_at && new Date(row.expires_at) <= new Date()) return { text: 'Vencido', cls: 'bg-amber-100 text-amber-900' };
    return { text: 'Pausado', cls: 'bg-slate-200 text-slate-700' };
}

const listingPages = computed(() => {
    const last = listingsMeta.value.last_page || 1;
    return Array.from({ length: last }, (_, i) => i + 1);
});

</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Administración</p>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Moderación</h1>
                <p class="text-slate-600 mt-1">Revisa y oculta anuncios que violen las políticas.</p>
            </div>
            <RouterLink
                :to="{ name: 'admin-dashboard' }"
                class="text-sm font-bold text-[#003874] hover:underline no-underline"
            >
                ← Volver al panel
            </RouterLink>
        </header>

        <div class="flex flex-wrap gap-3 mb-6 items-center">
            <span class="px-4 py-2.5 text-sm font-bold border-b-2 border-[#003874] text-[#003874]">Anuncios</span>
            <RouterLink
                :to="{ name: 'admin-users' }"
                class="px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-[#003874] no-underline"
            >
                Gestionar usuarios →
            </RouterLink>
        </div>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <!-- Anuncios -->
        <section>
            <div class="flex flex-wrap gap-3 mb-4">
                <input
                    v-model="listingQ"
                    type="search"
                    placeholder="Buscar título, negocio o email…"
                    class="flex-1 min-w-[200px] rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                    @keyup.enter="loadListings(1)"
                />
                <select v-model="listingFilter" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="loadListings(1)">
                    <option value="all">Todos</option>
                    <option value="visible">Visibles</option>
                    <option value="hidden">Ocultos (admin)</option>
                    <option value="paused">Pausados</option>
                    <option value="expired">Vencidos</option>
                </select>
                <AppButton variant="primary" @click="loadListings(1)">Buscar</AppButton>
            </div>

            <p v-if="listingsLoading" class="text-slate-500 py-8 text-center">Cargando anuncios…</p>
            <p v-else-if="!listings.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
                No hay anuncios con estos filtros.
            </p>
            <div v-else class="space-y-4">
                <article
                    v-for="row in listings"
                    :key="row.id"
                    class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 flex flex-col md:flex-row gap-4"
                >
                    <img
                        v-if="row.cover_image_url"
                        :src="row.cover_image_url"
                        alt=""
                        class="w-full md:w-28 h-28 object-cover rounded-xl ring-1 ring-slate-200 shrink-0"
                    />
                    <div
                        v-else
                        class="w-full md:w-28 h-28 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0"
                    >
                        <span class="material-symbols-outlined text-3xl">image</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-slate-500">#{{ row.id }}</span>
                            <span
                                class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                                :class="listingStatusLabel(row).cls"
                            >
                                {{ listingStatusLabel(row).text }}
                            </span>
                            <span v-if="row.category?.name" class="text-[10px] font-bold uppercase text-[#003874]">
                                {{ row.category.name }}
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">{{ row.title }}</h2>
                        <p class="text-sm text-slate-600 mt-1 line-clamp-2">{{ row.description }}</p>
                        <p class="text-sm font-semibold text-[#003874] mt-2">
                            <Money :amount="row.base_price" />
                            <span class="text-slate-500 font-normal"> · {{ row.price_type }}</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-2">
                            <strong>{{ row.provider?.business_name || row.provider?.user_name }}</strong>
                            · {{ row.provider?.user_email }}
                            <span v-if="row.provider?.user_status !== 'activo'" class="text-rose-600 font-bold">
                                · cuenta {{ row.provider?.user_status }}
                            </span>
                        </p>
                        <p v-if="row.admin_hidden_reason" class="text-xs text-rose-700 mt-2 bg-rose-50 rounded-lg px-3 py-2">
                            Motivo: {{ row.admin_hidden_reason }}
                        </p>
                    </div>
                    <div class="flex md:flex-col gap-2 shrink-0 md:items-end">
                        <RouterLink
                            :to="{ name: 'listing-detail', params: { id: row.id } }"
                            class="text-xs font-bold text-[#003874] hover:underline no-underline"
                            target="_blank"
                        >
                            Ver anuncio
                        </RouterLink>
                        <AppButton
                            v-if="!row.admin_hidden"
                            variant="ghost"
                            size="sm"
                            :loading="busy === row.id"
                            @click="openHideModal(row)"
                        >
                            Ocultar
                        </AppButton>
                        <AppButton
                            v-else
                            variant="outline"
                            size="sm"
                            :loading="busy === row.id"
                            @click="restoreListing(row)"
                        >
                            Quitar oculto
                        </AppButton>
                    </div>
                </article>
            </div>
            <div v-if="listingPages.length > 1" class="flex justify-center gap-2 mt-6 flex-wrap">
                <button
                    v-for="p in listingPages"
                    :key="p"
                    type="button"
                    class="w-9 h-9 rounded-lg text-sm font-bold border"
                    :class="p === listingsMeta.current_page ? 'bg-[#003874] text-white border-[#003874]' : 'border-slate-200'"
                    @click="loadListings(p)"
                >
                    {{ p }}
                </button>
            </div>
        </section>

        <!-- Modal ocultar anuncio -->
        <div
            v-if="modalListing"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="modalListing = null"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-slate-900">Ocultar anuncio</h3>
                <p class="text-sm text-slate-600 mt-1">«{{ modalListing.title }}» dejará de aparecer en búsqueda y perfiles públicos.</p>
                <label class="block mt-4">
                    <span class="text-sm font-bold text-slate-700">Motivo (opcional)</span>
                    <textarea
                        v-model="hideReason"
                        rows="3"
                        maxlength="500"
                        placeholder="Ej. contenido engañoso, imágenes inadecuadas…"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                </label>
                <div class="flex justify-end gap-2 mt-5">
                    <AppButton variant="ghost" @click="modalListing = null">Cancelar</AppButton>
                    <AppButton variant="primary" :loading="busy === modalListing.id" @click="confirmHide">Ocultar</AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
