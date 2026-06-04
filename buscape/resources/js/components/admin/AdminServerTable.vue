<script setup>
import { computed } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
        /** { key, label, headerClass?, cellClass?, align?: 'left'|'center'|'right' } */
    },
    rows: { type: Array, default: () => [] },
    meta: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
            from: null,
            to: null,
        }),
    },
    loading: { type: Boolean, default: false },
    emptyMessage: { type: String, default: 'Sin registros.' },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
    showPerPage: { type: Boolean, default: true },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['page', 'per-page']);

const currentPage = computed(() => props.meta?.current_page || 1);
const lastPage = computed(() => Math.max(1, props.meta?.last_page || 1));
const total = computed(() => props.meta?.total ?? props.rows.length);

const pageNumbers = computed(() => {
    const last = lastPage.value;
    const cur = currentPage.value;
    const window = 2;
    const pages = new Set([1, last, cur]);
    for (let i = cur - window; i <= cur + window; i++) {
        if (i >= 1 && i <= last) pages.add(i);
    }
    return [...pages].sort((a, b) => a - b);
});

function alignClass(col) {
    if (col.align === 'right') return 'text-right';
    if (col.align === 'center') return 'text-center';
    return 'text-left';
}

function goPage(p) {
    if (p < 1 || p > lastPage.value || p === currentPage.value || props.loading) return;
    emit('page', p);
}

function onPerPageChange(ev) {
    emit('per-page', Number(ev.target.value));
}
</script>

<template>
    <div class="admin-server-table space-y-3">
        <div v-if="$slots.toolbar" class="flex flex-wrap items-end gap-3">
            <slot name="toolbar" />
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm">
            <div
                v-if="loading"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-[1px]"
            >
                <span class="material-symbols-outlined animate-spin text-chamba-700 text-3xl">progress_activity</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse" :class="compact ? 'text-xs' : ''">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200">
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                scope="col"
                                class="px-4 py-3 font-bold text-slate-600 uppercase tracking-wide text-[11px]"
                                :class="[alignClass(col), col.headerClass]"
                            >
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!rows.length && !loading">
                            <td :colspan="columns.length" class="px-4 py-12 text-center text-slate-500">
                                {{ emptyMessage }}
                            </td>
                        </tr>
                        <tr
                            v-for="(row, ri) in rows"
                            :key="row.id ?? ri"
                            class="border-b border-slate-100 last:border-0 hover:bg-slate-50/60 transition-colors"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-4 py-3 text-slate-800 align-middle"
                                :class="[alignClass(col), col.cellClass]"
                            >
                                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                    {{ row[col.key] ?? '—' }}
                                </slot>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/50 text-xs text-slate-600"
            >
                <p>
                    <template v-if="total">
                        Mostrando {{ meta.from ?? '—' }}–{{ meta.to ?? '—' }} de {{ total }}
                    </template>
                    <template v-else>Sin resultados</template>
                </p>
                <div class="flex flex-wrap items-center gap-2 justify-end">
                    <label v-if="showPerPage" class="flex items-center gap-1.5">
                        <span class="font-semibold">Por página</span>
                        <select
                            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm"
                            :value="meta.per_page || 25"
                            :disabled="loading"
                            @change="onPerPageChange"
                        >
                            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </label>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40"
                            :disabled="currentPage <= 1 || loading"
                            aria-label="Anterior"
                            @click="goPage(currentPage - 1)"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </button>
                        <template v-for="(p, i) in pageNumbers" :key="'p-' + p">
                            <span
                                v-if="i > 0 && pageNumbers[i - 1] !== p - 1"
                                class="px-1 text-slate-400"
                            >…</span>
                            <button
                                type="button"
                                class="min-w-[2rem] h-8 px-2 rounded-lg border text-sm font-semibold"
                                :class="
                                    p === currentPage
                                        ? 'border-chamba-600 bg-chamba-600 text-white'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'
                                "
                                :disabled="loading"
                                @click="goPage(p)"
                            >
                                {{ p }}
                            </button>
                        </template>
                        <button
                            type="button"
                            class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40"
                            :disabled="currentPage >= lastPage || loading"
                            aria-label="Siguiente"
                            @click="goPage(currentPage + 1)"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
