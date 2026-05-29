<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useGeoStore } from '@/stores/geo';
import { formatDistanceKm } from '@/utils/formatDistance';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    results: { type: Array, default: () => [] },
    userLat: { type: Number, default: null },
    userLng: { type: Number, default: null },
    locationLoading: { type: Boolean, default: false },
});

const router = useRouter();
const geo = useGeoStore();
const mapEl = ref(null);
const locationDenied = ref(false);
let map = null;
let markersLayer = null;
let userMarker = null;

const mappable = computed(() =>
    (props.results || []).filter((s) => {
        const lat = parseFloat(String(s.provider_latitude ?? ''));
        const lng = parseFloat(String(s.provider_longitude ?? ''));
        return Number.isFinite(lat) && Number.isFinite(lng);
    }),
);

const hasUserLocation = computed(
    () => props.userLat != null && props.userLng != null && Number.isFinite(props.userLat) && Number.isFinite(props.userLng),
);

function fixLeafletIcons() {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });
}

function userLocationIcon() {
    return L.divIcon({
        className: 'chamba-user-location-marker',
        html: '<span class="chamba-user-dot"></span><span class="chamba-user-pulse"></span>',
        iconSize: [24, 24],
        iconAnchor: [12, 12],
    });
}

function escHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function popupHtml(service) {
    const business = escHtml(service.provider_name || 'Negocio');
    const title = escHtml(service.title || 'Anuncio');
    const category = escHtml(service.category_name || '');
    const district = escHtml(service.district_name || '');
    const dist = formatDistanceKm(service.distance_km);
    const distLine = dist ? escHtml(dist) : '';
    const reviews = Number(service.total_reviews) || 0;
    const ratingVal = parseFloat(String(service.avg_rating ?? '').replace(',', '.'));
    const ratingLine =
        reviews > 0 && Number.isFinite(ratingVal)
            ? `<span class="chamba-map-popup-rating">${ratingVal.toFixed(1)} ★ · ${reviews} valoración${reviews === 1 ? '' : 'es'}</span>`
            : '';
    const cover =
        service.cover_image_url || (Array.isArray(service.images) && service.images[0]) || '';
    const imgBlock = cover
        ? `<img class="chamba-map-popup-img" src="${escHtml(cover)}" alt="" loading="lazy" />`
        : '';
    const metaParts = [category, district, distLine].filter(Boolean);
    const meta = metaParts.length ? `<p class="chamba-map-popup-meta">${metaParts.join(' · ')}</p>` : '';

    return `
        <div class="chamba-map-popup">
            ${imgBlock}
            <p class="chamba-map-popup-business">${business}</p>
            <p class="chamba-map-popup-title">${title}</p>
            ${meta}
            ${ratingLine}
            <button type="button" class="chamba-map-popup-btn" data-chamba-go-detail="${Number(service.service_id)}">
                Ver anuncio completo
            </button>
        </div>
    `;
}

function wirePopupDetailButton(marker, serviceId) {
    marker.on('popupopen', (e) => {
        const btn = e.popup.getElement()?.querySelector('[data-chamba-go-detail]');
        if (!btn) return;
        L.DomEvent.off(btn);
        L.DomEvent.on(btn, 'click', (ev) => {
            L.DomEvent.stop(ev);
            const row = props.results.find((s) => Number(s.service_id) === serviceId);
            const param = row?.listing_ref || String(serviceId);
            router.push({ name: 'listing-detail', params: { id: param } });
        });
    });
}

function addUserMarker() {
    if (!map || !markersLayer || !hasUserLocation.value) return;

    userMarker = L.marker([props.userLat, props.userLng], {
        icon: userLocationIcon(),
        zIndexOffset: 1000,
    }).bindPopup('<strong>Tu ubicación</strong>');
    markersLayer.addLayer(userMarker);
}

function renderMarkers() {
    if (!map || !markersLayer) return;
    markersLayer.clearLayers();
    userMarker = null;
    const bounds = [];

    for (const s of mappable.value) {
        const lat = parseFloat(String(s.provider_latitude));
        const lng = parseFloat(String(s.provider_longitude));
        const serviceId = Number(s.service_id);
        const marker = L.marker([lat, lng]).bindPopup(popupHtml(s), {
            minWidth: 220,
            maxWidth: 280,
            className: 'chamba-map-popup-wrap',
        });
        wirePopupDetailButton(marker, serviceId);
        markersLayer.addLayer(marker);
        bounds.push([lat, lng]);
    }

    addUserMarker();

    if (hasUserLocation.value) {
        bounds.push([props.userLat, props.userLng]);
    }

    if (bounds.length === 1) {
        map.setView(bounds[0], 14);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [48, 48], maxZoom: 15 });
    } else if (hasUserLocation.value) {
        map.setView([props.userLat, props.userLng], 14);
    }
}

function centerOnUser() {
    if (hasUserLocation.value && map) {
        map.setView([props.userLat, props.userLng], 15, { animate: true });
        userMarker?.openPopup();
        return;
    }
    locationDenied.value = false;
    geo.ensureMapLocation().then((ok) => {
        if (!ok) locationDenied.value = true;
    });
}

onMounted(() => {
    fixLeafletIcons();
    const center = hasUserLocation.value ? [props.userLat, props.userLng] : [-12.0464, -77.0428];
    map = L.map(mapEl.value, { scrollWheelZoom: true }).setView(center, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);
    markersLayer = L.layerGroup().addTo(map);
    renderMarkers();
});

watch(mappable, () => renderMarkers(), { deep: true });
watch(
    () => [props.userLat, props.userLng],
    () => {
        locationDenied.value = false;
        renderMarkers();
    },
);

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div class="space-y-3">
        <p v-if="!mappable.length" class="text-sm text-slate-500 text-center py-12 ui-card">
            No hay anuncios con ubicación en el mapa para esta búsqueda. Prueba «Cerca de mí» o otro distrito.
        </p>
        <div v-if="mappable.length" class="relative">
            <div
                ref="mapEl"
                class="w-full rounded-2xl border border-slate-200 overflow-hidden bg-slate-100 h-[min(70vh,520px)]"
                aria-label="Mapa de anuncios"
            />
            <button
                type="button"
                class="absolute bottom-4 right-4 z-[1000] flex items-center gap-1.5 rounded-full bg-white border border-slate-200 shadow-lg px-4 py-2.5 text-sm font-bold text-[#003874] hover:bg-slate-50 transition"
                :disabled="locationLoading"
                title="Centrar en tu ubicación"
                @click="centerOnUser"
            >
                <span
                    class="material-symbols-outlined text-lg"
                    :class="locationLoading ? 'animate-spin' : ''"
                >{{ locationLoading ? 'progress_activity' : 'my_location' }}</span>
                {{ locationLoading ? 'Ubicando…' : 'Mi ubicación' }}
            </button>
        </div>
        <p v-if="mappable.length" class="text-xs text-slate-500 text-center">
            <span
                v-if="hasUserLocation"
                class="inline-flex items-center gap-1.5 mr-2 text-[#003874] font-semibold"
            >
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-sky-500 ring-2 ring-[#003874]/30"></span>
                Punto azul = tú
            </span>
            {{ mappable.length }} negocio(s) en el mapa · Toca un pin para ver el nombre · «Ver anuncio» para el detalle
        </p>
        <p v-if="locationDenied" class="text-xs text-amber-800 text-center bg-amber-50 border border-amber-100 rounded-xl px-3 py-2">
            Activa la ubicación en el navegador o usa «Cerca de mí» en la búsqueda.
        </p>
    </div>
</template>

<style>
.chamba-user-location-marker {
    background: transparent !important;
    border: none !important;
}
.chamba-user-location-marker .chamba-user-dot {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 14px;
    height: 14px;
    margin: -7px 0 0 -7px;
    background: #0ea5e9;
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 1px 4px rgba(0, 56, 116, 0.45);
    z-index: 2;
}
.chamba-user-location-marker .chamba-user-pulse {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 32px;
    height: 32px;
    margin: -16px 0 0 -16px;
    background: rgba(14, 165, 233, 0.35);
    border-radius: 50%;
    animation: chamba-user-pulse 2s ease-out infinite;
    z-index: 1;
}
@keyframes chamba-user-pulse {
    0% {
        transform: scale(0.5);
        opacity: 0.9;
    }
    100% {
        transform: scale(1.4);
        opacity: 0;
    }
}

.chamba-map-popup-wrap .leaflet-popup-content {
    margin: 10px 12px 12px;
    line-height: 1.35;
}
.chamba-map-popup {
    font-family: inherit;
    color: #0f172a;
}
.chamba-map-popup-img {
    display: block;
    width: 100%;
    height: 88px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 8px;
    background: #e2e8f0;
}
.chamba-map-popup-business {
    margin: 0 0 2px;
    font-size: 15px;
    font-weight: 800;
    color: #003874;
    line-height: 1.25;
}
.chamba-map-popup-title {
    margin: 0 0 6px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
.chamba-map-popup-meta {
    margin: 0 0 4px;
    font-size: 11px;
    color: #64748b;
}
.chamba-map-popup-rating {
    display: block;
    margin: 0 0 10px;
    font-size: 12px;
    font-weight: 700;
    color: #b45309;
}
.chamba-map-popup-btn {
    display: block;
    width: 100%;
    margin: 0;
    padding: 9px 12px;
    border: none;
    border-radius: 999px;
    background: #003874;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    text-align: center;
}
.chamba-map-popup-btn:hover {
    background: #002a57;
}
</style>
