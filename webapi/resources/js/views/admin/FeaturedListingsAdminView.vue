<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const err = ref('');
const ok = ref('');
const busy = ref(null);
const rejectReason = ref('');
const rejectModal = ref(null);

const pending = ref([]);
const pendingLoading = ref(false);
const featured = ref([]);
const featuredLoading = ref(false);

const pendingCount = computed(() => pending.value.length);
const featuredCount = computed(() => featured.value.length);

async function loadPending() {
    pendingLoading.value = true;
    try {
        const r = await api.get('/admin/listings', {
            auth: true,
            params: { filter: 'home_featured_pending', per_page: 50 },
        });
        pending.value = r.data || [];
    } catch (e) {
        err.value = e.message;
        pending.value = [];
    } finally {
        pendingLoading.value = false;
    }
}

async function loadFeatured() {
    featuredLoading.value = true;
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

async function reloadAll() {
    await Promise.all([loadPending(), loadFeatured()]);
}

async function approve(row) {
    busy.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/listings/${row.id}/feature-home`, {}, { auth: true });
        ok.value = r.message || 'Anuncio aprobado para el banner.';
        await reloadAll();
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

function openReject(row) {
    rejectModal.value = row;
    rejectReason.value = '';
}

async function confirmReject() {
    if (!rejectModal.value) return;
    busy.value = rejectModal.value.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(
            `/admin/listings/${rejectModal.value.id}/reject-home-featured`,
            { reason: rejectReason.value.trim() || null },
            { auth: true },
        );
        ok.value = r.message || 'Solicitud rechazada.';
        rejectModal.value = null;
        await reloadAll();
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function removeFeatured(row) {
    if (!confirm(`¿Quitar «${row.title}» del banner del inicio?`)) return;
    busy.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/listings/${row.id}/unfeature-home`, {}, { auth: true });
        ok.value = r.message || 'Quitado del banner.';
        await reloadAll();
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
        ok.value = 'Orden del banner actualizado.';
    } catch (e) {
        err.value = e.message;
        await loadFeatured();
    } finally {
        busy.value = null;
    }
}

onMounted(reloadAll);
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#7c3aed]">Administración</p>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Banner del inicio</h1>
                <p class="text-slate-600 mt-1">
                    Los negocios solicitan aparecer arriba; tú apruebas o rechazas. {{ featuredCount }} activo(s), {{ pendingCount }} pendiente(s).
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

        <section class="mb-10">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Solicitudes pendientes</h2>
            <p v-if="pendingLoading" class="text-slate-500 py-8 text-center">Cargando…</p>
            <p v-else-if="!pending.length" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-600">
                No hay solicitudes de banner por revisar.
            </p>
            <div v-else class="space-y-3">
                <article
                    v-for="row in pending"
                    :key="row.id"
                    class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4 flex flex-wrap gap-4 items-center"
                >
                    <img
                        v-if="row.cover_image_url"
                        :src="row.cover_image_url"
                        alt=""
                        class="w-24 h-16 object-cover rounded-lg ring-1 ring-slate-200 shrink-0"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-amber-800 uppercase">Solicitud de banner</p>
                        <h3 class="font-bold text-slate-900">{{ row.title }}</h3>
                        <p class="text-sm text-slate-600">{{ row.provider?.business_name }}</p>
                        <p v-if="row.home_featured_requested_at" class="text-xs text-slate-500 mt-1">
                            Solicitado: {{ new Date(row.home_featured_requested_at).toLocaleString('es-PE') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <AppButton variant="primary" size="sm" :loading="busy === row.id" @click="approve(row)">
                            Aprobar
                        </AppButton>
                        <AppButton variant="outline" size="sm" :loading="busy === row.id" @click="openReject(row)">
                            Rechazar
                        </AppButton>
                    </div>
                </article>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-bold text-slate-900 mb-4">Banner activo (carrusel)</h2>
            <p v-if="featuredLoading" class="text-slate-500 py-8 text-center">Cargando…</p>
            <p v-else-if="!featured.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
                Aún no hay anuncios aprobados en el banner del inicio.
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
                        class="w-24 h-16 object-cover rounded-lg ring-1 ring-slate-200 shrink-0"
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
                            Quitar del banner
                        </AppButton>
                    </div>
                </article>
            </div>
        </section>

        <div
            v-if="rejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="rejectModal = null"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-slate-900">Rechazar solicitud</h3>
                <p class="text-sm text-slate-600 mt-1">«{{ rejectModal.title }}» no aparecerá en el banner.</p>
                <label class="block mt-4">
                    <span class="text-sm font-bold text-slate-700">Motivo (opcional)</span>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        maxlength="500"
                        placeholder="Ej. imagen poco clara, información incompleta…"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                </label>
                <div class="flex justify-end gap-2 mt-5">
                    <AppButton variant="ghost" @click="rejectModal = null">Cancelar</AppButton>
                    <AppButton variant="primary" :loading="busy === rejectModal.id" @click="confirmReject">Rechazar</AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
