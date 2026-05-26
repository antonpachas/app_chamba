<script setup>
import { onMounted, ref } from 'vue';
import { useClientRequestsStore } from '@/stores/clientRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const store = useClientRequestsStore();
const busyId = ref(null);
const payOpenId = ref(null);
const payForm = ref({ payment_method: 'yape', payment_reference: '', notes: '' });
const localError = ref('');
const localOk = ref('');

onMounted(async () => {
    await Promise.all([store.load(), store.loadPayments()]);
});

async function decide(quoteId, decision) {
    busyId.value = quoteId;
    localError.value = '';
    try {
        await store.decideQuote(quoteId, decision);
    } catch (e) {
        localError.value = e.message;
    } finally {
        busyId.value = null;
    }
}

function openPay(reqId) {
    payOpenId.value = reqId;
    payForm.value = { payment_method: 'yape', payment_reference: '', notes: '' };
    localError.value = '';
    localOk.value = '';
}

async function submitPay(quoteId) {
    busyId.value = quoteId;
    localError.value = '';
    localOk.value = '';
    try {
        await store.pay(quoteId, payForm.value);
        payOpenId.value = null;
        localOk.value = 'Pago registrado. Estamos validando la transferencia.';
    } catch (e) {
        localError.value = e.message;
    } finally {
        busyId.value = null;
    }
}

async function confirmCompleted(paymentId) {
    busyId.value = paymentId;
    localError.value = '';
    try {
        await store.confirmCompleted(paymentId);
        localOk.value = 'Confirmaste la finalización. Pago liberado al proveedor.';
    } catch (e) {
        localError.value = e.message;
    } finally {
        busyId.value = null;
    }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis solicitudes</h1>
            <p class="text-slate-600 mt-1">Sigue el estado, acepta cotizaciones y registra pagos.</p>
        </header>

        <AppAlert v-if="localError" type="error" class="mb-4">{{ localError }}</AppAlert>
        <AppAlert v-if="localOk" type="success" class="mb-4">{{ localOk }}</AppAlert>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!store.items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no tienes solicitudes. Cuando contactes a un proveedor desde un servicio, aparecerá aquí.
        </div>
        <div v-else class="space-y-5">
            <article
                v-for="r in store.items"
                :key="r.id"
                class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm"
            >
                <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">
                            #{{ r.id }} · {{ r.service?.category?.name || '—' }}
                        </p>
                        <h2 class="text-lg font-bold text-[#0b1c30] truncate">{{ r.service?.title }}</h2>
                        <p class="text-sm text-slate-600 mt-0.5">
                            <strong>{{ r.provider?.name }}</strong>
                        </p>
                    </div>
                    <StatusPill :status="r.status" />
                </div>

                <p v-if="r.message" class="text-sm text-slate-700 bg-slate-50 rounded-lg p-3 mb-4 whitespace-pre-wrap">
                    {{ r.message }}
                </p>

                <div v-if="r.latest_quote" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-4">
                    <div class="flex justify-between items-start gap-4 flex-wrap">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Cotización</p>
                            <p class="text-2xl font-black text-[#003874]">
                                <Money :amount="r.latest_quote.amount" :currency="r.latest_quote.currency" />
                            </p>
                            <p v-if="r.latest_quote.estimated_days" class="text-xs text-slate-600 mt-1">
                                Tiempo estimado: {{ r.latest_quote.estimated_days }} días
                            </p>
                            <p v-if="r.latest_quote.notes" class="text-sm text-slate-700 mt-2 whitespace-pre-wrap">
                                {{ r.latest_quote.notes }}
                            </p>
                        </div>
                        <StatusPill :status="r.latest_quote.status" />
                    </div>
                    <div v-if="r.latest_quote.status === 'pendiente'" class="mt-4 flex flex-wrap gap-2">
                        <AppButton
                            variant="primary"
                            size="sm"
                            :loading="busyId === r.latest_quote.id"
                            @click="decide(r.latest_quote.id, 'aceptar')"
                        >
                            Aceptar cotización
                        </AppButton>
                        <AppButton
                            variant="ghost"
                            size="sm"
                            @click="decide(r.latest_quote.id, 'rechazar')"
                        >
                            Rechazar
                        </AppButton>
                    </div>
                </div>

                <div v-if="r.latest_quote?.status === 'aceptada' && !r.payment" class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-4">
                    <p class="text-sm text-amber-900 mb-3">
                        Para asegurar el trabajo, paga el monto cotizado a Chamba. Cuando confirmes que el servicio terminó,
                        liberamos el dinero al proveedor (menos comisión).
                    </p>
                    <AppButton variant="secondary" size="sm" @click="openPay(r.id)">
                        Registrar pago
                    </AppButton>
                </div>

                <form
                    v-if="payOpenId === r.id && r.latest_quote"
                    @submit.prevent="submitPay(r.latest_quote.id)"
                    class="rounded-xl border border-slate-200 bg-white p-4 mb-4 space-y-3"
                >
                    <p class="text-sm text-slate-700">
                        Paga vía Yape/Plin/Transferencia a Chamba:
                        <strong>{{ store.platformPayoutInfo?.yape || '999999999' }}</strong>
                        ({{ store.platformPayoutInfo?.bank_name }} · {{ store.platformPayoutInfo?.bank_account }} ·
                        {{ store.platformPayoutInfo?.bank_holder }}).
                        Luego registra aquí la transacción.
                    </p>
                    <label class="block text-sm">
                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Método</span>
                        <select v-model="payForm.payment_method" class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]">
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="transferencia">Transferencia bancaria</option>
                            <option value="otro">Otro</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Referencia (código u operación)</span>
                        <input v-model="payForm.payment_reference" maxlength="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
                    </label>
                    <label class="block text-sm">
                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">Notas (opcional)</span>
                        <textarea v-model="payForm.notes" rows="2" maxlength="500" class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]"></textarea>
                    </label>
                    <div class="flex justify-end gap-2">
                        <AppButton variant="ghost" size="sm" type="button" @click="payOpenId = null">Cancelar</AppButton>
                        <AppButton variant="primary" size="sm" type="submit" :loading="busyId === r.latest_quote.id">
                            Confirmar pago
                        </AppButton>
                    </div>
                </form>

                <div v-if="r.payment" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-4">
                    <div class="flex justify-between items-start gap-4 flex-wrap">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pago</p>
                            <p class="text-xl font-black text-[#003874]">
                                <Money :amount="r.payment.amount" />
                            </p>
                            <p class="text-xs text-slate-600 mt-1">
                                {{ r.payment.payment_method }} · ref: {{ r.payment.payment_reference || '—' }}
                            </p>
                        </div>
                        <StatusPill :status="r.payment.status" />
                    </div>
                    <div v-if="r.payment.status === 'en_custodia'" class="mt-4">
                        <p class="text-sm text-slate-700 mb-3">
                            Cuando el proveedor termine el trabajo y estés conforme, libera el pago.
                        </p>
                        <AppButton
                            variant="primary"
                            size="sm"
                            :loading="busyId === r.payment.id"
                            @click="confirmCompleted(r.payment.id)"
                        >
                            Confirmar trabajo terminado
                        </AppButton>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 text-sm">
                    <a
                        v-if="r.provider?.whatsapp"
                        :href="`https://wa.me/${String(r.provider.whatsapp).replace(/\\D/g,'')}`"
                        target="_blank" rel="noopener noreferrer"
                        class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 no-underline"
                    >WhatsApp</a>
                    <a
                        v-if="r.provider?.contact_phone"
                        :href="`tel:${String(r.provider.contact_phone).replace(/\\D/g,'')}`"
                        class="rounded-lg border border-slate-200 hover:border-[#003874]/40 font-bold px-4 py-2 text-slate-800 no-underline"
                    >Llamar</a>
                </div>
            </article>
        </div>
    </div>
</template>
