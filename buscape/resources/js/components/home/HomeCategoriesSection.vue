<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { categoryStyleFor } from '@/components/common/CategoryIcon';
import CategoriesBrowseModal from '@/components/home/CategoriesBrowseModal.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    selectedId: { type: [Number, null], default: null },
    /** chips = pills compactos; shelf = tarjetas blancas estilo marketplace */
    layout: { type: String, default: 'shelf' },
});

const emit = defineEmits(['select', 'clear']);

const showAllModal = ref(false);
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

function updateWidth() {
    viewportWidth.value = window.innerWidth;
}

onMounted(() => window.addEventListener('resize', updateWidth, { passive: true }));
onUnmounted(() => window.removeEventListener('resize', updateWidth));

const maxPreview = computed(() => {
    const w = viewportWidth.value;
    if (w >= 1280) return 10;
    if (w >= 1024) return 8;
    if (w >= 768) return 6;
    if (w >= 640) return 5;
    return 4;
});

const previewCategories = computed(() => props.categories.slice(0, maxPreview.value));
const hasMore = computed(() => props.categories.length > maxPreview.value);

function onSelect(id) {
    if (id == null) {
        emit('clear');
    } else {
        emit('select', id);
    }
}
</script>

<template>
    <section id="categorias" class="home-categories scroll-mt-24" :class="layout === 'shelf' ? 'home-shelf' : ''" aria-label="Categorías">
        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-semibold text-slate-900" :class="layout === 'shelf' ? 'home-shelf__title' : 'text-sm'">
                    Categorías
                </h2>
                <div class="flex items-center gap-3 shrink-0">
                    <button
                        v-if="selectedId != null"
                        type="button"
                        class="text-xs font-medium text-slate-500 hover:text-slate-800"
                        @click="emit('clear')"
                    >
                        Quitar filtro
                    </button>
                    <button
                        v-if="hasMore || categories.length > 0"
                        type="button"
                        class="text-xs font-semibold text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer"
                        @click="showAllModal = true"
                    >
                        Ver todas
                    </button>
                </div>
            </div>

            <!-- Tarjetas horizontales (estilo marketplace) -->
            <div v-if="layout === 'shelf'" class="home-shelf__track">
                <button
                    type="button"
                    class="home-shelf__card"
                    :class="selectedId == null ? 'home-shelf__card--active' : ''"
                    @click="emit('clear')"
                >
                    <span class="home-shelf__card-icon material-symbols-outlined">apps</span>
                    <span class="home-shelf__card-name">Todas</span>
                </button>
                <button
                    v-for="c in previewCategories"
                    :key="c.id"
                    type="button"
                    class="home-shelf__card"
                    :class="selectedId === c.id ? 'home-shelf__card--active' : ''"
                    @click="emit('select', c.id)"
                >
                    <span
                        class="home-shelf__card-icon material-symbols-outlined"
                        :style="{ color: categoryStyleFor(c.name).color }"
                    >
                        {{ categoryStyleFor(c.name).icon }}
                    </span>
                    <span class="home-shelf__card-name">{{ c.name }}</span>
                </button>
            </div>

            <!-- Pills compactos (legacy) -->
            <div v-else class="home-categories__row">
                <button
                    type="button"
                    class="home-category-chip shrink-0"
                    :class="selectedId == null ? 'home-category-chip--active' : ''"
                    @click="emit('clear')"
                >
                    <span class="home-category-chip__icon material-symbols-outlined">apps</span>
                    <span class="home-category-chip__name">Todas</span>
                </button>
                <button
                    v-for="c in previewCategories"
                    :key="c.id"
                    type="button"
                    class="home-category-chip shrink-0"
                    :class="selectedId === c.id ? 'home-category-chip--active' : ''"
                    @click="emit('select', c.id)"
                >
                    <span
                        class="home-category-chip__icon material-symbols-outlined"
                        :style="{ color: categoryStyleFor(c.name).color }"
                    >
                        {{ categoryStyleFor(c.name).icon }}
                    </span>
                    <span class="home-category-chip__name">{{ c.name }}</span>
                </button>
            </div>
        </div>

        <CategoriesBrowseModal
            :open="showAllModal"
            :categories="categories"
            :selected-id="selectedId"
            @select="onSelect"
            @close="showAllModal = false"
        />
    </section>
</template>
