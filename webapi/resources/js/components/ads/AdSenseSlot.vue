<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    placement: { type: String, default: 'home' },
    /** inline = banner horizontal · rail = columna lateral (skyscraper) */
    variant: { type: String, default: 'inline' },
    side: { type: String, default: 'left' },
    showPlaceholder: { type: Boolean, default: true },
});

const auth = useAuthStore();
const config = ref(null);
const loaded = ref(false);

const PLACEMENT_LABELS = {
    home: 'Inicio',
    search: 'Búsqueda',
    detail: 'Detalle',
    all: 'Global',
};

const placementLabel = computed(() => PLACEMENT_LABELS[props.placement] || props.placement);

const isRail = computed(() => props.variant === 'rail');

async function load() {
    if (auth.isAuthenticated && auth.entitlements && auth.entitlements.shows_ads === false) {
        loaded.value = true;
        config.value = null;
        return;
    }
    try {
        config.value = await api.get('/ads/config');
    } catch {
        config.value = null;
    } finally {
        loaded.value = true;
    }
}

const adsenseEnabled = computed(
    () => config.value?.adsense?.enabled && config.value?.adsense?.client_id,
);

const slotId = computed(() => {
    const slots = config.value?.adsense?.slots || {};
    if (props.placement === 'detail' && isRail.value) {
        const key = props.side === 'right' ? 'detail_right' : 'detail_left';
        return slots[key] || slots.detail || '';
    }
    return slots[props.placement] || '';
});

const adsHidden = computed(
    () => auth.isAuthenticated && auth.entitlements && auth.entitlements.shows_ads === false,
);

const visible = computed(
    () => loaded.value && !adsHidden.value && props.showPlaceholder,
);

onMounted(load);
watch(() => props.placement, load);
watch(() => auth.isAuthenticated, load);
</script>

<template>
    <div
        v-if="visible"
        class="adsense-slot"
        :class="isRail ? 'adsense-slot--rail' : 'adsense-slot--inline my-4'"
        :data-adsense-placement="placement"
        :data-adsense-variant="variant"
    >
        <div
            class="adsense-slot__frame"
            :class="isRail ? 'adsense-slot__frame--rail' : 'adsense-slot__frame--inline'"
            role="complementary"
            :aria-label="`Google AdSense ${placementLabel}`"
        >
            <span class="adsense-slot__badge">
                <span class="material-symbols-outlined text-[12px]">ads_click</span>
                Google AdSense
            </span>
            <p class="adsense-slot__label">
                {{ placementLabel }}
                <template v-if="isRail"> · lateral {{ side === 'right' ? 'derecho' : 'izquierdo' }}</template>
            </p>
            <p v-if="adsenseEnabled && slotId" class="adsense-slot__hint">
                Bloque activo (slot configurado)
            </p>
            <p v-else-if="adsenseEnabled" class="adsense-slot__hint">
                AdSense activo — pendiente slot en Configuración
            </p>
            <p v-else class="adsense-slot__hint">
                Configura AdSense en Admin → Configuración
            </p>
            <!-- Punto de inserción real cuando se conecte el script de Google -->
            <div
                v-if="adsenseEnabled && slotId"
                class="adsense-slot__unit"
                :data-ad-client="config?.adsense?.client_id"
                :data-ad-slot="slotId"
            />
        </div>
    </div>
</template>
