<script setup>
import { computed } from 'vue';
import { useSearchStore } from '@/stores/search';
import { scrollToHash } from '@/utils/scroll';

const search = useSearchStore();

const pagination = computed(() => search.searchMeta?.pagination || null);

const pages = computed(() => {
    const last = pagination.value?.last_page || 1;
    const current = pagination.value?.current_page || 1;
    const maxButtons = 7;
    if (last <= maxButtons) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    const half = Math.floor(maxButtons / 2);
    let start = Math.max(1, current - half);
    let end = Math.min(last, start + maxButtons - 1);
    start = Math.max(1, end - maxButtons + 1);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

function goTo(page) {
    const last = pagination.value?.last_page || 1;
    const target = Math.max(1, Math.min(page, last));
    if (target === search.page) return;
    void search.run(target).then(() => {
        scrollToHash('#resultados');
    });
}
</script>

<template>
    <nav
        v-if="pagination && pagination.last_page > 1"
        class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 pt-6"
        aria-label="Paginación de anuncios"
    >
        <p class="text-sm text-slate-500 tabular-nums">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
            <span class="text-slate-400">·</span>
            {{ pagination.total }} anuncios en total
        </p>
        <div class="flex flex-wrap items-center justify-center gap-1.5">
            <button
                type="button"
                class="search-page-btn"
                :disabled="pagination.current_page <= 1 || search.loading"
                aria-label="Página anterior"
                @click="goTo(pagination.current_page - 1)"
            >
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>
            <button
                v-for="p in pages"
                :key="p"
                type="button"
                class="search-page-btn search-page-btn--num"
                :class="p === pagination.current_page ? 'search-page-btn--active' : ''"
                :disabled="search.loading"
                @click="goTo(p)"
            >
                {{ p }}
            </button>
            <button
                type="button"
                class="search-page-btn"
                :disabled="pagination.current_page >= pagination.last_page || search.loading"
                aria-label="Página siguiente"
                @click="goTo(pagination.current_page + 1)"
            >
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>
        </div>
    </nav>
</template>
