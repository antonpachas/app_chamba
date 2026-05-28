<script setup>
import { RouterLink, useRoute } from 'vue-router';

defineProps({
    meta: { type: Object, default: null },
    compact: { type: Boolean, default: false },
});

const route = useRoute();
</script>

<template>
    <div
        class="rounded-xl border border-sky-200 bg-sky-50 text-sky-950"
        :class="compact ? 'px-3 py-2.5 text-xs mb-3' : 'px-4 py-3 text-sm mb-4'"
    >
        <p class="font-bold" :class="compact ? 'text-xs' : 'text-sm'">Explorando sin cuenta</p>
        <p class="mt-1 text-sky-900/90 leading-snug">
            Puedes ver fotos, precio, rubro, zona y contactar por WhatsApp. La descripción completa y las solicitudes en plataforma requieren cuenta de cliente.
            <span v-if="meta?.guest_limited" class="block mt-1 font-medium">
                Vista previa: {{ meta.guest_limit }} de {{ meta.guest_total }} anuncios en esta búsqueda.
            </span>
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
            <RouterLink
                :to="{ name: 'login', query: { next: route.fullPath } }"
                class="inline-flex rounded-full bg-[#003874] text-white font-bold px-4 py-1.5 text-xs no-underline hover:bg-[#08458b]"
            >
                Iniciar sesión
            </RouterLink>
            <RouterLink
                :to="{ name: 'register', query: { next: route.fullPath } }"
                class="inline-flex rounded-full border border-[#003874]/30 text-[#003874] font-bold px-4 py-1.5 text-xs no-underline hover:bg-white"
            >
                Crear cuenta
            </RouterLink>
        </div>
    </div>
</template>
