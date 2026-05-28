<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useSearchStore } from '@/stores/search';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import FavoriteButton from '@/components/common/FavoriteButton.vue';
import RequestReviewForm from '@/components/requests/RequestReviewForm.vue';
import { providerPublicProfileEnabled } from '@/services/features';
import { useClientRequestsStore } from '@/stores/clientRequests';

const route = useRoute();
const router = useRouter();
const search = useSearchStore();
const auth = useAuthStore();
const clientRequests = useClientRequestsStore();

const service = ref(null);
const reviewableRequest = ref(null);
const loading = ref(true);
const error = ref('');

const sendChannel = ref('whatsapp');
const sendMessage = ref('');
const sending = ref(false);
const sendOk = ref('');
const sendErr = ref('');

const id = computed(() => Number(route.params.id));
const image = computed(() => service.value?.cover_image_url || `https://picsum.photos/seed/chamba_svc_${id.value}/1200/600`);
const gallery = computed(() => {
    const list = service.value?.images || [];
    return Array.isArray(list) ? list.filter(Boolean) : [];
});

const ratingValue = computed(() => {
    const s = service.value;
    if (!s) return null;
    const v = parseFloat(String(s.avg_rating ?? '').replace(',', '.'));
    const n = Number(s.total_reviews) || 0;
    if (n <= 0 || !Number.isFinite(v)) return null;
    return { v: v.toFixed(1), n };
});

const locationLine = computed(() => {
    const s = service.value;
    if (!s) return '';
    return [s.district_name, s.province_name, s.department_name].filter(Boolean).join(' · ');
});

const lat = computed(() => parseFloat(String(service.value?.provider_latitude || '').replace(',', '.')));
const lng = computed(() => parseFloat(String(service.value?.provider_longitude || '').replace(',', '.')));
const hasMap = computed(() => Number.isFinite(lat.value) && Number.isFinite(lng.value));
const mapSrc = computed(() => {
    if (!hasMap.value) return '';
    const pad = 0.014;
    const minlon = lng.value - pad;
    const minlat = lat.value - pad;
    const maxlon = lng.value + pad;
    const maxlat = lat.value + pad;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(`${minlon},${minlat},${maxlon},${maxlat}`)}&layer=mapnik&marker=${encodeURIComponent(`${lat.value},${lng.value}`)}`;
});

const waUrl = computed(() => {
    const s = service.value;
    const d = String(s?.whatsapp || '').replace(/\D/g, '');
    return d ? `https://wa.me/${d}` : null;
});
const telUrl = computed(() => {
    const s = service.value;
    const d = String(s?.contact_phone || '').replace(/\D/g, '') || String(s?.whatsapp || '').replace(/\D/g, '');
    return d ? `tel:${d}` : null;
});

const providerProfileId = computed(() => {
    const pid = service.value?.provider_profile_id;
    return pid != null && pid !== '' ? Number(pid) : null;
});

const showProviderProfileLink = computed(
    () => providerPublicProfileEnabled() && providerProfileId.value != null,
);

async function loadListing() {
    loading.value = true;
    error.value = '';
    service.value = null;
    try {
        const res = await api.get(`/listings/${id.value}`, { auth: auth.isAuthenticated });
        if (res.data) {
            service.value = res.data;
            return;
        }
    } catch (e) {
        if (e.status && e.status !== 404) {
            error.value = e.message || 'No se pudo cargar el anuncio.';
            return;
        }
    } finally {
        loading.value = false;
    }
    const cached = search.findById(id.value);
    if (cached) {
        service.value = cached;
        error.value = '';
        return;
    }
    if (!error.value) {
        error.value = 'No encontramos este anuncio.';
    }
}

async function loadReviewable() {
    reviewableRequest.value = null;
    if (!auth.isAuthenticated || !auth.isCliente) return;
    try {
        await clientRequests.load();
        reviewableRequest.value =
            clientRequests.items.find(
                (r) => Number(r.service?.id) === id.value && r.can_review,
            ) || null;
    } catch {
        reviewableRequest.value = null;
    }
}

onMounted(async () => {
    await loadListing();
    await loadReviewable();
});

async function submitRequest() {
    if (!auth.isAuthenticated || auth.user?.role !== 'cliente' || !service.value) return;
    sending.value = true;
    sendErr.value = '';
    sendOk.value = '';
    try {
        await api.post(
            '/client/service-requests',
            {
                provider_service_id: Number(service.value.service_id),
                contact_channel: sendChannel.value,
                message: sendMessage.value.trim() || null,
            },
            { auth: true },
        );
        sendOk.value = 'Solicitud enviada. Te llevamos a "Mis solicitudes".';
        sendMessage.value = '';
        setTimeout(() => router.push({ name: 'client-requests' }), 900);
    } catch (e) {
        sendErr.value = e.message || 'No se pudo enviar.';
    } finally {
        sending.value = false;
    }
}

function goLogin() {
    router.push({ name: 'login', query: { next: route.fullPath } });
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <div class="mb-6 flex items-center gap-3 text-sm">
            <button
                type="button"
                class="text-[#003874] font-semibold hover:underline bg-transparent border-0 p-0 cursor-pointer"
                @click="$router.back()"
            >
                ← Volver
            </button>
            <span class="text-slate-400">·</span>
            <RouterLink :to="{ name: 'search' }" class="text-slate-600 hover:text-[#003874]">Resultados</RouterLink>
        </div>

        <div v-if="loading" class="py-20 text-center text-slate-500 font-medium">Cargando…</div>
        <AppAlert v-else-if="error" type="error">{{ error }}</AppAlert>
        <article v-else-if="service">
            <div class="rounded-2xl overflow-hidden border border-slate-100 bg-white shadow-sm mb-8">
                <div class="relative h-64 md:h-96 bg-slate-200">
                    <img :src="image" alt="" class="w-full h-full object-cover" />
                    <div class="absolute top-4 left-4 z-10 flex items-center gap-2">
                        <FavoriteButton
                            v-if="providerProfileId"
                            :provider-profile-id="providerProfileId"
                            size="lg"
                            show-label
                        />
                    </div>
                    <div
                        v-if="ratingValue"
                        class="absolute top-4 right-4 bg-white/95 backdrop-blur px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-md z-10"
                    >
                        <span class="material-symbols-outlined text-amber-500" style="font-variation-settings: 'FILL' 1">star</span>
                        <span class="text-sm font-bold text-slate-900">{{ ratingValue.v }}</span>
                        <span class="text-xs text-slate-500 font-medium">({{ ratingValue.n }})</span>
                    </div>
                </div>
                <div v-if="gallery.length > 1" class="px-4 md:px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                    <div class="flex gap-2 overflow-x-auto">
                        <a v-for="(img, i) in gallery" :key="i" :href="img" target="_blank" rel="noopener" class="shrink-0">
                            <img :src="img" alt="" class="w-20 h-20 object-cover rounded-lg ring-1 ring-slate-200 hover:ring-chamba-500 transition" />
                        </a>
                    </div>
                </div>
                <div class="p-6 md:p-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">{{ service.category_name }}</p>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ service.title }}</h1>
                    <p class="text-base font-semibold text-slate-700 mt-2">{{ service.provider_name }}</p>
                    <RouterLink
                        v-if="showProviderProfileLink"
                        :to="{ name: 'provider-public', params: { id: providerProfileId } }"
                        class="inline-flex items-center gap-2 mt-2 text-sm font-bold text-[#003874] hover:underline no-underline"
                    >
                        <span class="material-symbols-outlined text-[18px]">storefront</span>
                        Ver todos los anuncios de este negocio
                    </RouterLink>
                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">location_on</span>
                        {{ locationLine || '—' }}
                    </p>
                    <p
                        v-if="service.description"
                        class="mt-6 text-slate-800 leading-relaxed whitespace-pre-wrap"
                    >
                        {{ service.description }}
                    </p>
                </div>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-7 space-y-6">
                    <section class="rounded-2xl border border-slate-100 bg-white p-6 md:p-8">
                        <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">Precio</h2>
                        <p class="text-2xl font-black text-[#003874]">
                            <template v-if="service.base_price != null && service.base_price !== ''">
                                S/ {{ service.base_price }}
                            </template>
                            <template v-else>Consultar</template>
                            <span v-if="service.price_type" class="text-sm text-slate-500 font-medium ml-2">
                                ({{ service.price_type }})
                            </span>
                        </p>
                        <p v-if="service.address_text" class="text-sm text-slate-600 mt-4">
                            <strong>Zona:</strong> {{ service.address_text }}
                        </p>
                    </section>

                    <section v-if="hasMap" class="rounded-2xl border border-slate-100 bg-white p-6 md:p-8">
                        <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">
                            Ubicación referencial
                        </h2>
                        <iframe
                            class="w-full h-64 md:h-80 rounded-xl border border-slate-200 bg-slate-100"
                            :src="mapSrc"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Mapa del servicio"
                        ></iframe>
                        <p class="text-xs text-slate-500 mt-2">Aprox. según el distrito registrado.</p>
                        <a
                            :href="`https://www.google.com/maps?q=${lat},${lng}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex mt-2 text-sm font-bold text-[#003874] hover:underline"
                        >
                            Abrir en Google Maps →
                        </a>
                    </section>
                </div>

                <aside class="lg:col-span-5 lg:sticky lg:top-24 space-y-6">
                    <div v-if="!auth.isAuthenticated" class="rounded-2xl border border-slate-200 bg-white p-6 md:p-8 space-y-4">
                        <div v-if="providerProfileId" class="flex items-center gap-2 pb-4 border-b border-slate-100">
                            <FavoriteButton :provider-profile-id="providerProfileId" show-label />
                            <span class="text-xs text-slate-500">Guarda negocios que te interesan</span>
                        </div>
                        <h2 class="text-base font-bold text-[#0b1c30] mb-2">Solicitar contacto</h2>
                        <p class="text-sm text-slate-600 mb-4">
                            Inicia sesión como <strong>cliente</strong> para enviar una solicitud, guardar favoritos y valorar.
                        </p>
                        <AppButton variant="primary" block @click="goLogin">Iniciar sesión</AppButton>
                        <RouterLink
                            :to="{ name: 'register', query: { next: route.fullPath } }"
                            class="inline-flex w-full justify-center rounded-lg border-2 border-[#003874]/30 bg-white px-6 py-2.5 text-sm font-bold text-[#003874] hover:bg-slate-50 no-underline"
                        >
                            Crear cuenta
                        </RouterLink>
                    </div>

                    <div
                        v-else-if="auth.isProveedor"
                        class="rounded-2xl border border-slate-200 bg-white p-6 md:p-8"
                    >
                        <h2 class="text-base font-bold text-[#0b1c30] mb-2">Contacto</h2>
                        <p class="text-sm text-slate-600 mb-4">
                            Con tu cuenta de proveedor no puedes registrar solicitudes de cliente, pero sí contactar
                            directamente.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a
                                v-if="waUrl"
                                :href="waUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-3 text-sm shadow-md no-underline"
                            >
                                WhatsApp
                            </a>
                            <a
                                v-if="telUrl"
                                :href="telUrl"
                                class="inline-flex items-center justify-center rounded-xl border-2 border-slate-200 hover:border-[#003874]/40 font-bold px-5 py-3 text-sm text-slate-800 no-underline"
                            >
                                Llamar
                            </a>
                        </div>
                    </div>

                    <template v-else-if="auth.isCliente">
                        <RequestReviewForm
                            v-if="reviewableRequest"
                            :service-request-id="reviewableRequest.id"
                            :provider-name="service.provider_name"
                            @submitted="loadReviewable"
                        />

                        <div
                            v-else
                            class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600"
                        >
                            <p class="font-bold text-[#0b1c30] mb-1">Valorar este negocio</p>
                            <p>
                                Después de enviar una solicitud, podrás calificar en
                                <RouterLink :to="{ name: 'client-requests' }" class="text-[#003874] font-bold hover:underline">Mis solicitudes</RouterLink>.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
                        <h2 class="text-base font-bold text-[#0b1c30] mb-4">Solicitar contacto</h2>
                        <form @submit.prevent="submitRequest" class="space-y-4">
                            <label class="block">
                                <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
                                    Canal preferido
                                </span>
                                <select
                                    v-model="sendChannel"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                                >
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="telefono">Teléfono</option>
                                    <option value="app">Por la aplicación</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
                                    Mensaje (opcional)
                                </span>
                                <textarea
                                    v-model="sendMessage"
                                    rows="3"
                                    maxlength="800"
                                    placeholder="Describe lo que necesitas…"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15 resize-y min-h-[5rem]"
                                ></textarea>
                            </label>
                            <AppAlert v-if="sendErr" type="error">{{ sendErr }}</AppAlert>
                            <AppAlert v-if="sendOk" type="success">{{ sendOk }}</AppAlert>
                            <AppButton variant="primary" type="submit" :loading="sending" block>
                                {{ sending ? 'Enviando…' : 'Enviar solicitud' }}
                            </AppButton>
                        </form>
                        <div v-if="waUrl || telUrl" class="mt-6 pt-6 border-t border-slate-100">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">Contacto directo</p>
                            <div class="flex flex-wrap gap-3">
                                <a
                                    v-if="waUrl"
                                    :href="waUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-3 text-sm shadow-md no-underline"
                                >
                                    WhatsApp
                                </a>
                                <a
                                    v-if="telUrl"
                                    :href="telUrl"
                                    class="inline-flex items-center justify-center rounded-xl border-2 border-slate-200 hover:border-[#003874]/40 font-bold px-5 py-3 text-sm text-slate-800 no-underline"
                                >
                                    Llamar
                                </a>
                            </div>
                        </div>
                        </div>
                    </template>
                </aside>
            </div>
        </article>
    </div>
</template>
