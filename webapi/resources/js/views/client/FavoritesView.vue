<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useFavoritesStore } from '@/stores/favorites';
import AppButton from '@/components/ui/AppButton.vue';

const favs = useFavoritesStore();

onMounted(() => favs.load());
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis favoritos</h1>
            <p class="text-slate-600 mt-1">Proveedores que guardaste para encontrarlos rápido.</p>
        </header>
        <p v-if="favs.loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!favs.items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no guardaste favoritos.
            <RouterLink :to="{ name: 'search' }" class="text-[#003874] font-bold hover:underline ml-1">Buscar anuncios</RouterLink>
        </div>
        <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article v-for="f in favs.items" :key="f.provider_profile_id" class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-[#003874]">{{ f.business_name || '—' }}</p>
                <h3 class="text-lg font-bold text-slate-900 mt-1">{{ f.provider_name || f.full_name || '—' }}</h3>
                <p class="text-sm text-slate-600 mt-1">
                    {{ [f.district_name, f.province_name].filter(Boolean).join(', ') || '—' }}
                </p>
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-sm text-slate-700">
                        ★ {{ f.avg_rating ?? '—' }} <span class="text-slate-500 text-xs">({{ f.total_reviews ?? 0 }})</span>
                    </span>
                    <AppButton variant="ghost" size="sm" @click="favs.toggle(f.provider_profile_id)">Quitar</AppButton>
                </div>
            </article>
        </div>
    </div>
</template>
