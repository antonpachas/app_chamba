<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useFavoritesStore } from '@/stores/favorites';

const props = defineProps({
    providerServiceId: { type: [Number, String], default: null },
    size: { type: String, default: 'md' },
    showLabel: { type: Boolean, default: false },
});

const router = useRouter();
const auth = useAuthStore();
const favs = useFavoritesStore();
const busy = ref(false);

const pid = computed(() => {
    const n = Number(props.providerServiceId);
    return Number.isFinite(n) && n > 0 ? n : null;
});

const canUse = computed(() => auth.isAuthenticated && auth.isCliente && pid.value != null);
const isFav = computed(() => pid.value != null && favs.isFavorite(pid.value));
const tooltip = computed(() => (
    isFav.value
        ? 'Quitar anuncio de favoritos'
        : 'Guardar anuncio en favoritos'
));

const btnClass = computed(() => {
    const base = 'inline-flex items-center justify-center rounded-full border transition shrink-0';
    const sz = props.size === 'lg'
        ? 'w-11 h-11'
        : props.size === 'sm'
            ? 'w-8 h-8'
            : 'w-10 h-10';
    const tone = isFav.value
        ? 'bg-rose-50 border-rose-200 text-rose-600 hover:bg-rose-100'
        : 'bg-white/95 border-slate-200 text-slate-500 hover:border-rose-200 hover:text-rose-600';
    return `${base} ${sz} ${tone}`;
});

async function ensure() {
    if (canUse.value) await favs.ensureLoaded();
}

onMounted(ensure);
watch(() => pid.value, ensure);

async function onClick(event) {
    event?.preventDefault?.();
    event?.stopPropagation?.();

    if (!pid.value) return;

    if (!auth.isAuthenticated) {
        router.push({ name: 'login', query: { next: router.currentRoute.value.fullPath } });
        return;
    }
    if (!auth.isCliente) return;
    if (busy.value) return;

    busy.value = true;
    try {
        await favs.toggle(pid.value);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <button
        v-if="pid"
        type="button"
        :class="btnClass"
        :disabled="busy"
        :title="tooltip"
        :aria-label="tooltip"
        :aria-pressed="isFav"
        @click="onClick"
    >
        <span
            v-if="!busy"
            class="material-symbols-outlined"
            :class="size === 'lg' ? 'text-[22px]' : size === 'sm' ? 'text-[18px]' : 'text-[20px]'"
            :style="isFav ? { fontVariationSettings: '\'FILL\' 1' } : undefined"
        >favorite</span>
        <span
            v-else
            class="inline-block rounded-full border-2 border-current border-t-transparent animate-spin"
            :class="size === 'lg' ? 'w-5 h-5' : size === 'sm' ? 'w-4 h-4' : 'w-[18px] h-[18px]'"
            aria-hidden="true"
        ></span>
    </button>
    <span v-if="showLabel && canUse" class="text-xs font-bold text-slate-600 ml-1">
        {{ busy ? 'Guardando...' : isFav ? 'Anuncio en favoritos' : 'Guardar anuncio' }}
    </span>
</template>
