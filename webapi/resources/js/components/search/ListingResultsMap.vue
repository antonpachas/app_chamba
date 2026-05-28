<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    results: { type: Array, default: () => [] },
    userLat: { type: Number, default: null },
    userLng: { type: Number, default: null },
});

const router = useRouter();
const mapEl = ref(null);
let map = null;
let markersLayer = null;

const mappable = computed(() =>
    (props.results || []).filter((s) => {
        const lat = parseFloat(String(s.provider_latitude ?? ''));
        const lng = parseFloat(String(s.provider_longitude ?? ''));
        return Number.isFinite(lat) && Number.isFinite(lng);
    }),
);

function fixLeafletIcons() {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });
}

function popupHtml(service) {
    const title = service.title || 'Anuncio';
    const provider = service.provider_name || '';
    const rating = service.avg_rating ? ` · ${service.avg_rating}★` : '';
    return `<strong>${title}</strong><br/><span style="font-size:12px">${provider}${rating}</span>`;
}

function renderMarkers() {
    if (!map || !markersLayer) return;
    markersLayer.clearLayers();
    const bounds = [];

    for (const s of mappable.value) {
        const lat = parseFloat(String(s.provider_latitude));
        const lng = parseFloat(String(s.provider_longitude));
        const marker = L.marker([lat, lng]).bindPopup(popupHtml(s));
        marker.on('click', () => {
            router.push({ name: 'listing-detail', params: { id: Number(s.service_id) } });
        });
        markersLayer.addLayer(marker);
        bounds.push([lat, lng]);
    }

    if (props.userLat != null && props.userLng != null) {
        const you = L.circleMarker([props.userLat, props.userLng], {
            radius: 8,
            color: '#003874',
            fillColor: '#0ea5e9',
            fillOpacity: 0.9,
            weight: 2,
        }).bindPopup('Tu ubicación');
        markersLayer.addLayer(you);
        bounds.push([props.userLat, props.userLng]);
    }

    if (bounds.length === 1) {
        map.setView(bounds[0], 14);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
    } else if (props.userLat != null && props.userLng != null) {
        map.setView([props.userLat, props.userLng], 13);
    }
}

onMounted(() => {
    fixLeafletIcons();
    const center =
        props.userLat != null && props.userLng != null
            ? [props.userLat, props.userLng]
            : [-12.0464, -77.0428];
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
    () => renderMarkers(),
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
        <div
            ref="mapEl"
            class="w-full rounded-2xl border border-slate-200 overflow-hidden bg-slate-100"
            :class="mappable.length ? 'h-[min(70vh,520px)]' : 'h-0'"
            aria-label="Mapa de anuncios"
        ></div>
        <p v-if="mappable.length" class="text-xs text-slate-500 text-center">
            {{ mappable.length }} negocio(s) en el mapa · Toca un pin para ver el anuncio
        </p>
    </div>
</template>
