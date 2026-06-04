<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useFavoritesStore } from '@/stores/favorites';

const props = defineProps({
    providerServiceId: { type: [Number, String], default: null },
    size: { type: String, default: 'md' },
    showLabel: { type: Boolean, default: false },
});

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const favs = useFavoritesStore();
const busy = ref(false);
const errMsg = ref('');

const pid = computed(() => {
    const n = Number(props.providerServiceId);
    return Number.isFinite(n) && n > 0 ? n : null;
});

/** Solo clientes logueados pueden usar favoritos. */
const canToggle = computed(() => auth.isAuthenticated && auth.isCliente && pid.value != null);

/** Visitante: CTA para iniciar sesión. */
const showGuestCta = computed(() => !auth.isAuthenticated && pid.value != null);

/** Proveedor/admin logueado: no mostrar control. */
const visible = computed(() => canToggle.value || showGuestCta.value);

const isFav = computed(() => canToggle.value && favs.isFavorite(pid.value));

const tooltip = computed(() => {
    if (showGuestCta.value) {
        return 'Inicia sesión como cliente para guardar favoritos';
    }
    return isFav.value
        ? 'Quitar anuncio de favoritos'
        : 'Guardar anuncio en favoritos';
});

const btnClass = computed(() => {
    const base = 'inline-flex items-center justify-center rounded-full border transition shrink-0';
    const sz = props.size === 'lg'
        ? 'w-11 h-11'
        : props.size === 'sm'
            ? 'w-8 h-8'
            : 'w-10 h-10';
    if (showGuestCta.value) {
        return `${base} ${sz} bg-white/95 border-slate-200 text-slate-400 hover:border-[#003874]/40 hover:text-[#003874]`;
    }
    const tone = isFav.value
        ? 'bg-rose-50 border-rose-200 text-rose-600 hover:bg-rose-100'
        : 'bg-white/95 border-slate-200 text-slate-500 hover:border-rose-200 hover:text-rose-600';
    return `${base} ${sz} ${tone}`;
});

function loginNext() {
    return route.fullPath || '/';
}

function goLogin() {
    router.push({ name: 'login', query: { next: loginNext(), cuenta: 'cliente' } });
}

async function ensure() {
    if (canToggle.value) {
        await favs.ensureLoaded();
    }
}

onMounted(ensure);
watch(() => [pid.value, auth.isAuthenticated, auth.isCliente], ensure);

async function onClick(event) {
    event?.preventDefault?.();
    event?.stopPropagation?.();
    errMsg.value = '';

    if (!pid.value) return;

    if (showGuestCta.value) {
        goLogin();
        return;
    }

    if (!canToggle.value) return;
    if (busy.value) return;

    busy.value = true;
    try {
        await favs.toggle(pid.value);
    } catch (e) {
        errMsg.value = e.message || 'No se pudo guardar el favorito.';
        if (e.status === 401) {
            goLogin();
        }
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div v-if="visible" class="inline-flex flex-col items-start gap-1" @click.stop>
        <div class="inline-flex items-center gap-1">
            <button
                type="button"
                :class="btnClass"
                :disabled="busy && canToggle"
                :title="tooltip"
                :aria-label="tooltip"
                :aria-pressed="canToggle ? isFav : undefined"
                @click="onClick"
            >
                <span
                    v-if="!busy"
                    class="material-symbols-outlined"
                    :class="size === 'lg' ? 'text-[22px]' : size === 'sm' ? 'text-[18px]' : 'text-[20px]'"
                    :style="isFav ? { fontVariationSettings: '\'FILL\' 1' } : undefined"
                >{{ showGuestCta ? 'favorite_border' : 'favorite' }}</span>
                <span
                    v-else
                    class="inline-block rounded-full border-2 border-current border-t-transparent animate-spin"
                    :class="size === 'lg' ? 'w-5 h-5' : size === 'sm' ? 'w-4 h-4' : 'w-[18px] h-[18px]'"
                    aria-hidden="true"
                ></span>
            </button>
            <span v-if="showLabel && canToggle" class="text-xs font-bold text-slate-600">
                {{ busy ? 'Guardando...' : isFav ? 'Anuncio en favoritos' : 'Guardar anuncio' }}
            </span>
            <button
                v-else-if="showLabel && showGuestCta"
                type="button"
                class="text-xs font-bold text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer text-left"
                @click="onClick"
            >
                Inicia sesión para guardar
            </button>
        </div>
        <p v-if="errMsg && canToggle" class="text-[10px] text-rose-600 font-medium max-w-[12rem]">{{ errMsg }}</p>
    </div>
</template>
