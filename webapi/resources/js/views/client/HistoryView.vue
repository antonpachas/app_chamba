<script setup>
import { computed, onMounted, ref } from 'vue';
import { useClientRequestsStore } from '@/stores/clientRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';

const store = useClientRequestsStore();
const filter = ref('all');

const tabs = [
    { id: 'all', label: 'Todos' },
    { id: 'membership', label: 'Membresía' },
    { id: 'service', label: 'Servicios' },
];

onMounted(() => store.loadHistory('all'));

async function setFilter(id) {
    filter.value = id;
    await store.loadHistory(id);
}

const totals = computed(() => store.historyTotals || { all: 0, membership: 0, service: 0 });
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Historial de pagos</h1>
            <p class="text-slate-600 mt-1">
                Aquí ves todo lo que has pagado: membresías y servicios contratados, con sus capturas.
            </p>
        </header>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 mb-6 grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                <p class="text-xl font-black text-[#003874]"><Money :amount="totals.all" /></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Membresía</p>
                <p class="text-xl font-black text-slate-700"><Money :amount="totals.membership" /></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Servicios</p>
                <p class="text-xl font-black text-slate-700"><Money :amount="totals.service" /></p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="t in tabs"
                :key="t.id"
                type="button"
                class="px-3 py-1.5 rounded-full text-sm border"
                :class="filter === t.id
                    ? 'bg-[#003874] text-white border-[#003874]'
                    : 'bg-white text-slate-700 border-slate-200 hover:border-[#003874]/40'"
                @click="setFilter(t.id)"
            >{{ t.label }}</button>
        </div>

        <ul v-if="store.history.length" class="space-y-3">
            <li
                v-for="item in store.history"
                :key="item.id"
                class="rounded-2xl border border-slate-200 bg-white p-4 flex flex-wrap items-start justify-between gap-4"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span
                            class="text-[10px] uppercase tracking-widest font-bold px-2 py-0.5 rounded border"
                            :class="item.kind === 'membership'
                                ? 'bg-indigo-50 text-indigo-700 border-indigo-100'
                                : 'bg-emerald-50 text-emerald-700 border-emerald-100'"
                        >
                            {{ item.kind === 'membership' ? 'Membresía' : 'Servicio' }}
                        </span>
                        <StatusPill :status="item.status" />
                    </div>
                    <p class="font-bold text-slate-900">{{ item.concept }}</p>
                    <p class="text-xs text-slate-500">{{ item.concept_detail }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ item.payment_method || '—' }}
                        <span v-if="item.payment_reference"> · ref {{ item.payment_reference }}</span>
                    </p>
                    <p class="text-xs text-slate-500">{{ new Date(item.date).toLocaleString() }}</p>
                </div>
                <div class="text-right min-w-0">
                    <p class="text-xl font-black text-[#003874]"><Money :amount="item.amount" :currency="item.currency" /></p>
                    <a
                        v-if="item.proof_image_url"
                        :href="item.proof_image_url"
                        target="_blank"
                        rel="noopener"
                        class="text-xs text-[#003874] underline"
                    >Ver comprobante</a>
                </div>
            </li>
        </ul>
        <div v-else class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no tienes pagos registrados.
        </div>
    </div>
</template>
