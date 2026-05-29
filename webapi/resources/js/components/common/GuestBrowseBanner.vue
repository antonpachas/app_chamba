<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useLoginModalStore } from '@/stores/loginModal';

defineProps({
    meta: { type: Object, default: null },
    compact: { type: Boolean, default: false },
});

const route = useRoute();
const auth = useAuthStore();
const loginModal = useLoginModalStore();

const visible = computed(() => !auth.isAuthenticated);

function openLogin() {
    loginModal.showLogin(route.fullPath);
}
</script>

<template>
    <template v-if="visible">
        <div
            v-if="compact"
            class="rounded-lg border border-slate-200/80 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm text-slate-600 leading-relaxed"
        >
            Estás explorando sin cuenta. Puedes ver anuncios; para contacto y favoritos,
            <button
                type="button"
                class="text-[#003874] font-medium hover:underline bg-transparent border-0 p-0 cursor-pointer"
                @click="openLogin"
            >inicia sesión</button>
            o
            <RouterLink
                :to="{ name: 'register', query: { next: route.fullPath } }"
                class="text-[#003874] font-medium hover:underline no-underline"
            >crea una cuenta</RouterLink>.
            <span v-if="meta?.guest_limited" class="block mt-1 text-xs text-slate-500">
                Vista previa: {{ meta.guest_limit }} de {{ meta.guest_total }} resultados.
            </span>
        </div>

        <div
            v-else
            class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700"
        >
            <p class="font-medium text-slate-900">Explorando sin cuenta</p>
            <p class="mt-1 text-slate-600 leading-relaxed">
                Puedes ver fotos, precio, categoría y zona. Teléfono, WhatsApp y descripción completa requieren iniciar sesión.
                <span v-if="meta?.guest_limited" class="block mt-1 text-slate-500">
                    Vista previa: {{ meta.guest_limit }} de {{ meta.guest_total }} anuncios.
                </span>
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex rounded-md bg-[#003874] text-white font-medium px-4 py-2 text-sm hover:bg-[#002e60] border-0 cursor-pointer"
                    @click="openLogin"
                >
                    Iniciar sesión
                </button>
                <RouterLink
                    :to="{ name: 'register', query: { next: route.fullPath } }"
                    class="inline-flex rounded-md border border-slate-300 text-slate-700 font-medium px-4 py-2 text-sm no-underline hover:bg-slate-50"
                >
                    Crear cuenta
                </RouterLink>
            </div>
        </div>
    </template>
</template>
