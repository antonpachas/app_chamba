<script setup>
import { onMounted } from 'vue';
import { useClientRequestsStore } from '@/stores/clientRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';

const store = useClientRequestsStore();

onMounted(() => store.loadPayments());
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis pagos</h1>
            <p class="text-slate-600 mt-1">Histórico de pagos hechos en Busca PE.</p>
        </header>

        <div v-if="store.platformPayoutInfo" class="rounded-2xl border border-slate-200 bg-white p-6 mb-8">
            <p class="text-xs font-bold uppercase tracking-wide text-[#003874] mb-2">Cuentas para pagar a Busca PE</p>
            <ul class="text-sm text-slate-700 space-y-1">
                <li><strong>Yape/Plin:</strong> {{ store.platformPayoutInfo.yape }}</li>
                <li><strong>{{ store.platformPayoutInfo.bank_name }}:</strong> {{ store.platformPayoutInfo.bank_account }} · {{ store.platformPayoutInfo.bank_holder }}</li>
            </ul>
        </div>

        <div v-if="!store.payments.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            No has hecho pagos todavía.
        </div>
        <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Servicio</th>
                        <th class="text-left px-4 py-3">Proveedor</th>
                        <th class="text-right px-4 py-3">Monto</th>
                        <th class="text-left px-4 py-3">Método</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in store.payments" :key="p.id" class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ p.service_title || '—' }}</td>
                        <td class="px-4 py-3">{{ p.provider_name || '—' }}</td>
                        <td class="px-4 py-3 text-right font-bold"><Money :amount="p.amount" /></td>
                        <td class="px-4 py-3 capitalize">{{ p.payment_method }}</td>
                        <td class="px-4 py-3"><StatusPill :status="p.status" /></td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ new Date(p.created_at).toLocaleString('es-PE') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
