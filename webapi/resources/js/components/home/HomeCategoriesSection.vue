<script setup>
import { categoryStyleFor } from '@/components/common/CategoryIcon';

defineProps({
    categories: { type: Array, default: () => [] },
    selectedId: { type: [Number, null], default: null },
});

const emit = defineEmits(['select', 'clear']);
</script>

<template>
    <section id="categorias" class="home-categories scroll-mt-24" aria-label="Categorías">
        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-base font-semibold text-slate-900">Nuestras categorías</h2>
                <button
                    v-if="selectedId != null"
                    type="button"
                    class="text-sm text-slate-500 hover:text-slate-800"
                    @click="emit('clear')"
                >
                    Ver todas
                </button>
            </div>

            <div class="home-categories__grid">
                <button
                    type="button"
                    class="home-category-card"
                    :class="selectedId == null ? 'home-category-card--active' : ''"
                    @click="emit('clear')"
                >
                    <span class="home-category-card__icon material-symbols-outlined">apps</span>
                    <span class="home-category-card__name">Todas</span>
                </button>
                <button
                    v-for="c in categories"
                    :key="c.id"
                    type="button"
                    class="home-category-card"
                    :class="selectedId === c.id ? 'home-category-card--active' : ''"
                    @click="emit('select', c.id)"
                >
                    <span
                        class="home-category-card__icon material-symbols-outlined"
                        :style="{ color: categoryStyleFor(c.name).color }"
                    >
                        {{ categoryStyleFor(c.name).icon }}
                    </span>
                    <span class="home-category-card__name">{{ c.name }}</span>
                </button>
            </div>
        </div>
    </section>
</template>
