<script setup>
import { RouterLink } from 'vue-router';
import AppButton from '@/components/ui/AppButton.vue';
</script>

<template>
    <div class="chamba-container py-24 md:py-32 text-center">
        <div class="ui-card max-w-md mx-auto py-12 px-8">
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-chamba-50 text-chamba-700 mb-6">
                <span class="material-symbols-outlined text-4xl">explore_off</span>
            </span>
            <p class="page-eyebrow">Error 404</p>
            <h1 class="page-title mb-3">Página no encontrada</h1>
            <p class="text-slate-600 text-sm mb-8">
                El enlace no existe o cambió. Vuelve al inicio o busca un anuncio.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <RouterLink :to="{ name: 'home' }" class="no-underline">
                    <AppButton variant="primary">Ir al inicio</AppButton>
                </RouterLink>
                <RouterLink :to="{ name: 'search' }" class="no-underline">
                    <AppButton variant="outline">Buscar anuncios</AppButton>
                </RouterLink>
            </div>
        </div>
    </div>
</template>
