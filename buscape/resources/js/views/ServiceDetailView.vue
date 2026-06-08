<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { usePageMeta } from '@/utils/usePageMeta';
import { LISTING_PLACEHOLDER } from '@/utils/placeholderImage';
import { useSearchStore } from '@/stores/search';
import { useGeoStore } from '@/stores/geo';
import { useAuthStore } from '@/stores/auth';
import OpenHoursBadge from '@/components/common/OpenHoursBadge.vue';
import { formatDistanceKm } from '@/utils/formatDistance';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import FavoriteButton from '@/components/common/FavoriteButton.vue';
import RequestReviewForm from '@/components/requests/RequestReviewForm.vue';
import { providerPublicProfileEnabled } from '@/services/features';
import GuestBrowseBanner from '@/components/common/GuestBrowseBanner.vue';
import ListingContactActions from '@/components/listing/ListingContactActions.vue';
import { hasListingContact } from '@/utils/whatsapp';
import BusinessHoursModal from '@/components/common/BusinessHoursModal.vue';
import ListingImageCarousel from '@/components/listing/ListingImageCarousel.vue';
import AdSenseSlot from '@/components/ads/AdSenseSlot.vue';

const route = useRoute();
const router = useRouter();
const search = useSearchStore();
const geo = useGeoStore();
const auth = useAuthStore();
const { setMeta, resetMeta } = usePageMeta();
const shareOk = ref('');
const showHoursModal = ref(false);
const showRequestPanel = ref(false);

const service = ref(null);
const reviewStatus = ref({ can_review: false, already_reviewed: false });
const loading = ref(true);
const error = ref('');

const sendChannel = ref('whatsapp');
const sendMessage = ref('');
const sending = ref(false);
const sendOk = ref('');
const sendErr = ref('');

const listingParam = computed(() => String(route.params.id || '').trim());
const numericServiceId = computed(() => Number(service.value?.service_id || 0));
const galleryImages = computed(() => {
    const urls = [];
    const cover = service.value?.cover_image_url;
    const list = service.value?.images || [];
    if (cover) urls.push(cover);
    if (Array.isArray(list)) {
        for (const u of list) {
            if (u && !urls.includes(u)) urls.push(u);
        }
    }
    if (!urls.length) {
        urls.push(LISTING_PLACEHOLDER);
    }
    return urls;
});

const ratingValue = computed(() => {
    const s = service.value;
    if (!s) return null;
    const v = parseFloat(String(s.avg_rating ?? '').replace(',', '.'));
    const n = Number(s.total_reviews) || 0;
    if (n <= 0 || !Number.isFinite(v)) return null;
    return { v: v.toFixed(1), n };
});

const distanceLabel = computed(() => formatDistanceKm(service.value?.distance_km));

function openGoogleMaps() {
    if (!googleMapsUrl.value) return;
    window.open(googleMapsUrl.value, '_blank', 'noopener,noreferrer');
}

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

const hasContact = computed(
    () =>
        !!service.value &&
        (hasListingContact(service.value) || !!service.value.contact_requires_login),
);

const providerProfileId = computed(() => {
    const pid = service.value?.provider_profile_id;
    return pid != null && pid !== '' ? Number(pid) : null;
});

const showProviderProfileLink = computed(
    () => providerPublicProfileEnabled() && providerProfileId.value != null,
);

const isGuestPreview = computed(() => !auth.isAuthenticated);

const formattedDescription = computed(() => {
    const raw = String(service.value?.description || '').trim();
    if (!raw) return '';
    return raw
        .replace(/\r\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .replace(/[ \t]+\n/g, '\n')
        .replace(/\n[ \t]+/g, '\n');
});

const hasDescription = computed(() => formattedDescription.value.length > 0);

const priceDisplay = computed(() => {
    const s = service.value;
    if (!s) return null;
    if (s.base_price != null && String(s.base_price).trim() !== '') {
        const type = s.price_type ? ` (${s.price_type})` : '';
        return `S/ ${String(s.base_price).trim()}${type}`;
    }
    return null;
});

const priceLabel = computed(() => priceDisplay.value || 'Consultar precio');

const geoLabel = computed(() => {
    const s = service.value;
    if (!s) return '';
    const parts = [s.district_name, s.province_name, s.department_name].filter(Boolean);
    if (distanceLabel.value) {
        parts.unshift(distanceLabel.value);
    }
    return parts.join(' · ');
});

const googleMapsUrl = computed(() => {
    if (hasMap.value) {
        return `https://www.google.com/maps?q=${lat.value},${lng.value}`;
    }
    const s = service.value;
    if (!s) return '';
    const q = [s.address_text, geoLabel.value].filter((x) => String(x || '').trim()).join(', ');
    if (!q.trim()) return '';
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`;
});

const canOpenMaps = computed(() => !!googleMapsUrl.value);

const hasBusinessHours = computed(() => {
    const h = service.value?.business_hours;
    return !!h && typeof h === 'object' && Object.keys(h).length > 0;
});

const showOpenBadge = computed(() => {
    const s = service.value;
    return s && s.is_open_now !== null && s.is_open_now !== undefined;
});

const showAddressLine = computed(() => {
    const addr = String(service.value?.address_text || '').trim();
    if (!addr) return false;
    const geo = geoLabel.value.trim().toLowerCase();
    return !geo || !addr.toLowerCase().includes(geo.split(' · ')[0] || '___');
});

function listingQueryParams() {
    const params = {};
    if (geo.useGps && geo.userLat != null && geo.userLng != null) {
        params.user_lat = geo.userLat;
        params.user_lng = geo.userLng;
    }
    return params;
}

function canonicalizeUrl(loaded) {
    const slug = loaded?.slug;
    if (slug && slug !== listingParam.value) {
        router.replace({ name: 'listing-detail', params: { id: slug } });
    }
}

async function loadListing() {
    loading.value = true;
    error.value = '';
    service.value = null;
    try {
        try {
            const res = await api.get(`/listings/${encodeURIComponent(listingParam.value)}`, {
                auth: true,
                params: listingQueryParams(),
            });
            if (res.data) {
                service.value = res.data;
                canonicalizeUrl(res.data);
                return;
            }
        } catch (e) {
            if (e.status && e.status !== 404) {
                error.value = e.message || 'No se pudo cargar el anuncio.';
                return;
            }
        }
        if (!auth.isAuthenticated) {
            const cached = search.results.find(
                (row) =>
                    String(row.slug || '') === listingParam.value
                    || String(row.listing_ref || '') === listingParam.value
                    || String(row.service_id) === listingParam.value,
            );
            if (cached) {
                service.value = cached;
                canonicalizeUrl(cached);
                error.value = '';
                return;
            }
        }
        if (!error.value) {
            error.value = 'No encontramos este anuncio.';
        }
    } finally {
        loading.value = false;
    }
}

watch(
    () => auth.isAuthenticated,
    async (loggedIn) => {
        if (loggedIn && service.value?.guest_preview) {
            await loadListing();
        }
    },
);

async function loadReviewStatus() {
    reviewStatus.value = { can_review: false, already_reviewed: false };
    if (!auth.isAuthenticated || !auth.isCliente || numericServiceId.value <= 0) return;
    try {
        const r = await api.get('/client/reviews/status', {
            auth: true,
            params: { provider_service_id: numericServiceId.value },
        });
        reviewStatus.value = {
            can_review: !!r.can_review,
            already_reviewed: !!r.already_reviewed,
        };
    } catch {
        reviewStatus.value = { can_review: false, already_reviewed: false };
    }
}

async function onReviewSubmitted() {
    await loadReviewStatus();
    await loadListing();
}

watch(service, (s) => {
    if (!s) return;
    const desc = String(s.description || '').replace(/\s+/g, ' ').trim().slice(0, 155);
    setMeta({
        title: `${s.title} | ${s.provider_name}`,
        description: desc || `${s.provider_name} en ${s.district_name || 'Perú'}. Contáctalo directo en Busca PE.`,
        image: s.cover_image_url || (Array.isArray(s.images) ? s.images[0] : null) || null,
        url: window.location.href,
    });
});

onUnmounted(resetMeta);

onMounted(async () => {
    await loadListing();
    await loadReviewStatus();
});

watch(listingParam, async () => {
    await loadListing();
    await loadReviewStatus();
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

async function shareListing() {
    shareOk.value = '';
    const url = window.location.href;
    const title = service.value?.title || 'Anuncio en Busca PE';
    try {
        if (navigator.share) {
            await navigator.share({ title, text: title, url });
        } else if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(url);
            shareOk.value = 'Enlace copiado.';
        }
    } catch {
        /* cancelado */
    }
}
</script>

<template>
    <div class="listing-detail-shell">
        <!-- Google AdSense — columna lateral izquierda (solo pantallas anchas) -->
        <aside class="ad-rail ad-rail--left" aria-label="Publicidad Google">
            <AdSenseSlot placement="detail" variant="rail" side="left" />
        </aside>

        <div class="listing-detail-page max-w-5xl mx-auto px-4 md:px-6 py-4 md:py-5">
        <div class="listing-detail__toolbar mb-3 flex items-center gap-2 text-sm">
            <button
                type="button"
                class="text-[#003874] font-semibold hover:underline bg-transparent border-0 p-0 cursor-pointer"
                @click="$router.back()"
            >
                ← Volver
            </button>
            <span class="text-slate-400">·</span>
            <RouterLink :to="{ name: 'home' }" class="text-slate-600 hover:text-[#003874]">Explorar</RouterLink>
            <span class="text-slate-400">·</span>
            <button
                type="button"
                class="text-[#003874] font-semibold hover:underline bg-transparent border-0 p-0 cursor-pointer"
                @click="shareListing"
            >
                Compartir
            </button>
        </div>
        <p v-if="shareOk" class="text-sm text-emerald-700 font-medium mb-4">{{ shareOk }}</p>

        <div v-if="loading" class="py-20 text-center text-slate-500 font-medium">Cargando…</div>
        <AppAlert v-else-if="error" type="error">{{ error }}</AppAlert>
        <article v-else-if="service" class="listing-detail">
            <GuestBrowseBanner v-if="isGuestPreview" compact class="mb-4" />

            <!-- Galería a ancho completo del contenedor -->
            <div class="listing-detail__gallery -mx-4 md:-mx-6 overflow-hidden border-y md:border border-slate-200 bg-slate-900 md:rounded-xl">
                <div class="listing-detail__gallery-frame relative w-full aspect-[5/3] max-h-[min(52vw,15.5rem)] md:max-h-[17.5rem]">
                    <ListingImageCarousel
                        :images="galleryImages"
                        :alt="service.title || 'Anuncio'"
                        edge-to-edge
                        lightbox
                    />
                    <div
                        v-if="service?.service_id && (auth.isCliente || !auth.isAuthenticated)"
                        class="absolute top-3 right-3 z-10"
                    >
                        <FavoriteButton
                            :provider-service-id="service.service_id"
                            size="lg"
                        />
                    </div>
                    <div
                        v-if="ratingValue"
                        class="absolute top-3 left-3 z-10 bg-white/95 backdrop-blur px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-sm"
                    >
                        <span class="material-symbols-outlined text-amber-500 text-[18px]" style="font-variation-settings: 'FILL' 1">star</span>
                        <span class="text-sm font-bold text-slate-900">{{ ratingValue.v }}</span>
                        <span class="text-xs text-slate-500">({{ ratingValue.n }})</span>
                    </div>
                </div>
            </div>

            <BusinessHoursModal
                :open="showHoursModal"
                :schedule="service.business_hours"
                :provider-name="service.provider_name"
                @close="showHoursModal = false"
            />

            <!-- Grid principal: izquierda (info) + derecha (acción sticky) -->
            <div class="ld-grid mt-4">

                <!-- ── COLUMNA IZQUIERDA ───────────────────────────── -->
                <div class="ld-main">

                    <!-- Tarjeta unificada: cabecera + descripción -->
                    <div class="ld-section">
                        <!-- Cabecera: categoría, título, empresa, meta -->
                        <div class="ld-head">
                            <span
                                v-if="service.category_name"
                                class="ld-category-badge"
                            >{{ service.category_name }}</span>

                            <h1 class="ld-title">{{ service.title }}</h1>

                            <RouterLink
                                v-if="showProviderProfileLink"
                                :to="{ name: 'provider-public', params: { id: providerProfileId } }"
                                class="ld-business-link"
                            >
                                <span class="material-symbols-outlined text-[18px]">storefront</span>
                                {{ service.provider_name }}
                            </RouterLink>
                            <p v-else class="ld-business-name">
                                <span class="material-symbols-outlined text-[17px] text-slate-400">storefront</span>
                                {{ service.provider_name }}
                            </p>

                            <!-- Chips: ubicación y sede -->
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <button
                                    v-if="geoLabel && canOpenMaps"
                                    type="button"
                                    class="ld-chip ld-chip--link"
                                    @click="openGoogleMaps"
                                >
                                    <span class="material-symbols-outlined text-[15px]">location_on</span>
                                    {{ geoLabel }}
                                    <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                                </button>
                                <span v-else-if="geoLabel" class="ld-chip">
                                    <span class="material-symbols-outlined text-[15px]">location_on</span>
                                    {{ geoLabel }}
                                </span>
                                <span v-if="service.location_label" class="ld-chip">
                                    <span class="material-symbols-outlined text-[15px]">store</span>
                                    {{ service.location_label }}
                                </span>
                            </div>

                            <!-- Horario (antes de dirección) -->
                            <div v-if="showOpenBadge || hasBusinessHours" class="mt-2">
                                <OpenHoursBadge
                                    v-if="showOpenBadge"
                                    :is-open="service.is_open_now"
                                    :interactive="hasBusinessHours"
                                    @click="showHoursModal = true"
                                />
                                <button
                                    v-else
                                    type="button"
                                    class="ld-chip ld-chip--link"
                                    @click="showHoursModal = true"
                                >
                                    <span class="material-symbols-outlined text-[15px]">schedule</span>
                                    Ver horario
                                    <span class="material-symbols-outlined text-[13px]">chevron_right</span>
                                </button>
                            </div>

                            <!-- Dirección -->
                            <p v-if="showAddressLine" class="ld-address">
                                <span class="material-symbols-outlined text-[15px] text-slate-400 shrink-0">near_me</span>
                                <button
                                    v-if="canOpenMaps"
                                    type="button"
                                    class="text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer text-left text-sm"
                                    @click="openGoogleMaps"
                                >{{ service.address_text }}</button>
                                <span v-else class="text-sm text-slate-600">{{ service.address_text }}</span>
                            </p>
                        </div>

                        <!-- Descripción (dentro de la misma tarjeta) -->
                        <div v-if="hasDescription" class="border-t border-slate-100 pt-4 mt-4">
                            <h2 class="ld-section-title">Descripción</h2>
                            <div class="ld-description">{{ formattedDescription }}</div>
                            <p v-if="isGuestPreview && service.description_truncated" class="mt-3 pt-3 border-t border-slate-100 text-sm text-slate-500 m-0">
                                Texto abreviado.
                                <button type="button" class="text-[#003874] font-semibold hover:underline bg-transparent border-0 p-0 cursor-pointer" @click="goLogin">Inicia sesión</button>
                                para ver la descripción completa.
                            </p>
                        </div>
                    </div>

                    <!-- Tarjeta de acción (solo en móvil, debajo del bloque info) -->
                    <div class="lg:hidden">
                        <!-- Anuncio patrocinado (encima del precio, móvil) -->
                        <AdSenseSlot placement="detail" class="px-0" />

                        <div class="ld-card">
                            <div class="ld-card__section ld-card__section--price">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="ld-label">Precio</p>
                                        <p class="ld-price">{{ priceLabel }}</p>
                                        <p v-if="!priceDisplay" class="ld-hint mt-1">El negocio no publicó un monto fijo.</p>
                                    </div>
                                    <div v-if="ratingValue" class="flex items-center gap-1 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1 shrink-0">
                                        <span class="material-symbols-outlined text-amber-400 text-[16px]" style="font-variation-settings:'FILL' 1">star</span>
                                        <span class="text-sm font-bold text-slate-800">{{ ratingValue.v }}</span>
                                        <span class="text-xs text-slate-500">({{ ratingValue.n }})</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ld-card__section">
                                <ListingContactActions v-if="hasContact" :service="service" />
                                <template v-else-if="!auth.isAuthenticated">
                                    <p class="text-sm text-slate-600 leading-relaxed mb-3">
                                        Inicia sesión como <strong>cliente</strong> para ver el teléfono y escribir por WhatsApp.
                                    </p>
                                    <AppButton variant="primary" block @click="goLogin" class="mb-2">Iniciar sesión</AppButton>
                                    <RouterLink :to="{ name: 'register', query: { next: route.fullPath } }" class="ld-outline-btn">Crear cuenta gratis</RouterLink>
                                </template>
                                <p v-else-if="auth.isProveedor" class="text-sm text-slate-500 m-0">Contacta directamente por WhatsApp o teléfono.</p>
                                <div v-else-if="auth.isCliente" class="flex items-start gap-2.5 rounded-lg bg-amber-50 border border-amber-100 px-3 py-3">
                                    <span class="material-symbols-outlined text-[17px] text-amber-500 shrink-0 mt-0.5">info</span>
                                    <p class="text-sm text-amber-800 leading-relaxed m-0">Este negocio aún no ha publicado datos de contacto. Puedes dejar una solicitud usando la opción de abajo.</p>
                                </div>
                            </div>
                            <div v-if="auth.isCliente" class="ld-card__section ld-card__section--accordion">
                                <button type="button" class="ld-accordion-toggle" @click="showRequestPanel = !showRequestPanel">
                                    <span class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[17px] text-slate-400">send</span>
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-700 leading-tight">Solicitar vía plataforma</span>
                                            <span class="block text-xs text-slate-400 mt-0.5 font-normal">Opcional, además de WhatsApp</span>
                                        </span>
                                    </span>
                                    <span class="material-symbols-outlined text-[20px] text-slate-300 transition-transform duration-200" :class="showRequestPanel ? 'rotate-180' : ''">keyboard_arrow_down</span>
                                </button>
                                <div v-show="showRequestPanel" class="pt-3 mt-3 border-t border-slate-100 space-y-3 px-5 pb-4">
                                    <div>
                                        <label class="ld-field-label">Canal preferido</label>
                                        <select v-model="sendChannel" class="ld-input">
                                            <option value="whatsapp">WhatsApp</option>
                                            <option value="telefono">Teléfono</option>
                                            <option value="app">Por la aplicación</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ld-field-label">Mensaje <span class="font-normal normal-case text-slate-400">(opcional)</span></label>
                                        <textarea v-model="sendMessage" rows="2" maxlength="800" placeholder="Describe lo que necesitas…" class="ld-input ld-textarea"></textarea>
                                    </div>
                                    <AppAlert v-if="sendErr" type="error">{{ sendErr }}</AppAlert>
                                    <AppAlert v-if="sendOk" type="success">{{ sendOk }}</AppAlert>
                                    <AppButton variant="primary" :loading="sending" block size="sm" @click="submitRequest">{{ sending ? 'Enviando…' : 'Enviar solicitud' }}</AppButton>
                                </div>
                            </div>
                        </div>

                        <template v-if="auth.isCliente">
                            <RequestReviewForm
                                v-if="reviewStatus.can_review && service.service_id"
                                :provider-service-id="service.service_id"
                                :provider-name="service.provider_name"
                                embedded
                                @submitted="onReviewSubmitted"
                                class="mt-3"
                            />
                            <div v-else-if="reviewStatus.already_reviewed" class="ld-reviewed mt-3">
                                <span class="material-symbols-outlined text-emerald-500 text-[22px]" style="font-variation-settings:'FILL' 1">verified</span>
                                <div>
                                    <p class="text-sm font-bold text-emerald-900 m-0">Ya valoraste este negocio</p>
                                    <p class="text-xs text-emerald-700 mt-0.5 m-0">Gracias por compartir tu experiencia.</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Mapa -->
                    <section v-if="hasMap || canOpenMaps" class="ld-section">
                        <h2 class="ld-section-title">Ubicación</h2>
                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
                            <iframe
                                v-if="hasMap"
                                class="w-full h-48 md:h-56 block"
                                :src="mapSrc"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Mapa del servicio"
                            ></iframe>
                        </div>
                        <button
                            v-if="canOpenMaps"
                            type="button"
                            class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer"
                            @click="openGoogleMaps"
                        >
                            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                            Abrir en Google Maps
                        </button>
                    </section>

                </div><!-- /ld-main -->

                <!-- ── COLUMNA DERECHA (sticky en desktop) ────────── -->
                <aside class="ld-sidebar hidden lg:block">

                    <!-- Anuncio patrocinado (encima del precio) -->
                    <AdSenseSlot placement="detail" />

                    <!-- Tarjeta principal de acción -->
                    <div class="ld-card">
                        <!-- Precio -->
                        <div class="ld-card__section ld-card__section--price">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="ld-label">Precio</p>
                                    <p class="ld-price">{{ priceLabel }}</p>
                                    <p v-if="!priceDisplay" class="ld-hint mt-1">El negocio no publicó un monto fijo.</p>
                                </div>
                                <div v-if="ratingValue" class="flex items-center gap-1 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1 shrink-0">
                                    <span class="material-symbols-outlined text-amber-400 text-[16px]" style="font-variation-settings:'FILL' 1">star</span>
                                    <span class="text-sm font-bold text-slate-800">{{ ratingValue.v }}</span>
                                    <span class="text-xs text-slate-500">({{ ratingValue.n }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto -->
                        <div class="ld-card__section">
                            <ListingContactActions v-if="hasContact" :service="service" />

                            <template v-else-if="!auth.isAuthenticated">
                                <p class="text-sm text-slate-600 leading-relaxed mb-3">
                                    Inicia sesión como <strong>cliente</strong> para ver el teléfono y escribir por WhatsApp.
                                </p>
                                <AppButton variant="primary" block @click="goLogin" class="mb-2">
                                    Iniciar sesión
                                </AppButton>
                                <RouterLink
                                    :to="{ name: 'register', query: { next: route.fullPath } }"
                                    class="ld-outline-btn"
                                >
                                    Crear cuenta gratis
                                </RouterLink>
                            </template>

                            <p v-else-if="auth.isProveedor" class="text-sm text-slate-500 m-0">
                                Contacta directamente por WhatsApp o teléfono si el negocio lo publica.
                            </p>

                            <div v-else-if="auth.isCliente" class="flex items-start gap-2.5 rounded-lg bg-amber-50 border border-amber-100 px-3 py-3">
                                <span class="material-symbols-outlined text-[17px] text-amber-500 shrink-0 mt-0.5">info</span>
                                <p class="text-sm text-amber-800 leading-relaxed m-0">Este negocio aún no ha publicado datos de contacto. Puedes enviar una solicitud desde la sección de abajo.</p>
                            </div>
                        </div>

                        <!-- Solicitar por la plataforma (solo clientes) -->
                        <div v-if="auth.isCliente" class="ld-card__section ld-card__section--accordion">
                            <button
                                type="button"
                                class="ld-accordion-toggle"
                                :aria-expanded="showRequestPanel"
                                @click="showRequestPanel = !showRequestPanel"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[17px] text-slate-400">send</span>
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-700 leading-tight">Solicitar vía plataforma</span>
                                        <span class="block text-xs text-slate-400 mt-0.5 font-normal">Deja constancia además de WhatsApp</span>
                                    </span>
                                </span>
                                <span
                                    class="material-symbols-outlined text-[20px] text-slate-300 transition-transform duration-200"
                                    :class="showRequestPanel ? 'rotate-180' : ''"
                                >keyboard_arrow_down</span>
                            </button>
                            <div v-show="showRequestPanel" class="px-5 pb-5 space-y-3">
                                <div>
                                    <label class="ld-field-label">Canal preferido</label>
                                    <select v-model="sendChannel" class="ld-input">
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="telefono">Teléfono</option>
                                        <option value="app">Por la aplicación</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="ld-field-label">Mensaje <span class="font-normal normal-case text-slate-400">(opcional)</span></label>
                                    <textarea
                                        v-model="sendMessage"
                                        rows="2"
                                        maxlength="800"
                                        placeholder="Describe lo que necesitas…"
                                        class="ld-input ld-textarea"
                                    ></textarea>
                                </div>
                                <AppAlert v-if="sendErr" type="error">{{ sendErr }}</AppAlert>
                                <AppAlert v-if="sendOk" type="success">{{ sendOk }}</AppAlert>
                                <AppButton variant="primary" :loading="sending" block size="sm" @click="submitRequest">
                                    {{ sending ? 'Enviando…' : 'Enviar solicitud' }}
                                </AppButton>
                            </div>
                        </div>
                    </div><!-- /ld-card -->

                    <!-- Valoración -->
                    <template v-if="auth.isCliente">
                        <RequestReviewForm
                            v-if="reviewStatus.can_review && service.service_id"
                            :provider-service-id="service.service_id"
                            :provider-name="service.provider_name"
                            embedded
                            @submitted="onReviewSubmitted"
                        />
                        <div v-else-if="reviewStatus.already_reviewed" class="ld-reviewed">
                            <span class="material-symbols-outlined text-emerald-500 text-[22px]" style="font-variation-settings:'FILL' 1">verified</span>
                            <div>
                                <p class="text-sm font-bold text-emerald-900 m-0">Ya valoraste este negocio</p>
                                <p class="text-xs text-emerald-700 mt-0.5 m-0">Gracias por compartir tu experiencia.</p>
                            </div>
                        </div>
                    </template>

                </aside><!-- /ld-sidebar -->

            </div><!-- /ld-grid -->
        </article>
        </div><!-- /listing-detail-page -->

        <!-- Google AdSense — columna lateral derecha (solo pantallas anchas) -->
        <aside class="ad-rail ad-rail--right" aria-label="Publicidad Google">
            <AdSenseSlot placement="detail" variant="rail" side="right" />
        </aside>
    </div><!-- /listing-detail-shell -->
</template>
