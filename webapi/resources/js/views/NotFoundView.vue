<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
    <div class="max-w-3xl mx-auto px-6 py-24 text-center">
        <p class="text-sm font-bold text-[#003874] uppercase tracking-widest mb-3">Error 404</p>
        <h1 class="text-3xl md:text-4xl font-bold text-[#0b1c30] mb-4">Página no encontrada</h1>
        <p class="text-slate-600 mb-8">
            La página que buscabas no existe o fue movida. Vuelve al inicio o usa el buscador.
        </p>
        <RouterLink
            :to="{ name: 'home' }"
            class="inline-flex bg-[#003874] text-white px-8 py-3.5 rounded-full font-bold hover:shadow-xl no-underline"
        >
            Volver al inicio
        </RouterLink>
    </div>
</template>
