<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { categoryStyleFor } from '@/components/common/CategoryIcon';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    selectedId: { type: [Number, null], default: null },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'close']);

const query = ref('');

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.categories;
    return props.categories.filter((c) => String(c.name || '').toLowerCase().includes(q));
});

function pick(id) {
    emit('select', id);
    emit('close');
}

function onKeydown(e) {
    if (e.key === 'Escape' && props.open) {
        emit('close');
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (typeof document === 'undefined') return;
        document.body.style.overflow = isOpen ? 'hidden' : '';
        if (!isOpen) query.value = '';
    },
);

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    if (typeof document !== 'undefined') document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="login-modal">
            <div
                v-if="open"
                class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="categories-modal-title"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px] border-0 cursor-pointer"
                    aria-label="Cerrar"
                    @click="emit('close')"
                />
                <div
                    class="relative w-full sm:max-w-2xl max-h-[85vh] flex flex-col bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl border border-slate-200"
                    @click.stop
                >
                    <div class="shrink-0 px-5 pt-5 pb-3 border-b border-slate-100">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 id="categories-modal-title" class="text-lg font-bold text-slate-900">
                                    Todas las categorías
                                </h2>
                                <p class="text-sm text-slate-500 mt-0.5">{{ categories.length }} rubros disponibles</p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 flex items-center justify-center"
                                aria-label="Cerrar"
                                @click="emit('close')"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                        <input
                            v-if="categories.length > 12"
                            v-model="query"
                            type="search"
                            placeholder="Buscar categoría…"
                            class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                        />
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 sm:p-5">
                        <p v-if="!filtered.length" class="text-sm text-slate-500 text-center py-8">
                            No hay categorías con ese nombre.
                        </p>
                        <div v-else class="categories-modal__grid">
                            <button
                                type="button"
                                class="home-category-chip"
                                :class="selectedId == null ? 'home-category-chip--active' : ''"
                                @click="pick(null)"
                            >
                                <span class="home-category-chip__icon material-symbols-outlined">apps</span>
                                <span class="home-category-chip__name">Todas</span>
                            </button>
                            <button
                                v-for="c in filtered"
                                :key="c.id"
                                type="button"
                                class="home-category-chip"
                                :class="selectedId === c.id ? 'home-category-chip--active' : ''"
                                @click="pick(c.id)"
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
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.categories-modal__grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
</style>
