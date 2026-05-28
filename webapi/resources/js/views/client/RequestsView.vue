<script setup>
import { computed, onMounted, ref } from 'vue';
import { useClientRequestsStore } from '@/stores/clientRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import ListingPreviewModal from '@/components/listing/ListingPreviewModal.vue';
import RequestConversation from '@/components/requests/RequestConversation.vue';
import RequestReviewForm from '@/components/requests/RequestReviewForm.vue';
import StarRatingInput from '@/components/common/StarRatingInput.vue';

const store = useClientRequestsStore();
const previewListingId = ref(null);
const previewOpen = ref(false);
const detailOpen = ref(false);
const detailRequestId = ref(null);
const busyId = ref(null);
const payOpenId = ref(null);
const payForm = ref({ payment_method: 'yape', payment_reference: '', notes: '', proof: null });
const proofPreviewUrl = ref('');
const disputeOpenId = ref(null);
const disputeReason = ref('');
const localError = ref('');
const localOk = ref('');

const activeRequest = computed(() =>
    store.items.find((r) => Number(r.id) === Number(detailRequestId.value)) || null,
);

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
    payForm.value = { payment_method: 'yape', payment_reference: '', notes: '', proof: null };
    proofPreviewUrl.value = '';
    localError.value = '';
    localOk.value = '';
}

function onProofPick(event) {
    const file = event.target.files?.[0] || null;
    payForm.value.proof = file;
    if (proofPreviewUrl.value) URL.revokeObjectURL(proofPreviewUrl.value);
    proofPreviewUrl.value = file ? URL.createObjectURL(file) : '';
}

async function submitPay(quoteId) {
    if (store.proofRequired && !payForm.value.proof) {
        localError.value = 'Adjunta la captura de la transferencia para registrar el pago.';
        return;
    }
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

function openDispute(paymentId) {
    disputeOpenId.value = paymentId;
    disputeReason.value = '';
}

function openListingPreview(serviceId) {
    if (!serviceId) return;
    previewListingId.value = Number(serviceId);
    previewOpen.value = true;
}

function openDetail(requestId) {
    detailRequestId.value = Number(requestId);
    detailOpen.value = true;
}

function closeDetail() {
    detailOpen.value = false;
    detailRequestId.value = null;
    payOpenId.value = null;
    disputeOpenId.value = null;
}

function requestClosed(r) {
    return ['cerrado', 'cancelado'].includes(r.status);
}

async function submitDispute(paymentId) {
    if ((disputeReason.value || '').trim().length < 10) {
        localError.value = 'Describe el problema con al menos 10 caracteres.';
        return;
    }
    busyId.value = paymentId;
    localError.value = '';
    try {
        await store.disputePayment(paymentId, disputeReason.value.trim());
        disputeOpenId.value = null;
        localOk.value = 'Disputa registrada. Un administrador la revisará.';
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
            <p class="text-slate-600 mt-1">Vista compacta tipo inbox. Abre cada solicitud para ver chat y acciones.</p>
        </header>

        <AppAlert v-if="localError" type="error" class="mb-4">{{ localError }}</AppAlert>
        <AppAlert v-if="localOk" type="success" class="mb-4">{{ localOk }}</AppAlert>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!store.items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no tienes solicitudes. Cuando contactes a un proveedor desde un servicio, aparecerá aquí.
        </div>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[860px] text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Solicitud</th>
                        <th class="text-left px-4 py-3">Negocio</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in store.items" :key="r.id" class="border-t border-slate-100 hover:bg-slate-50/50">
                        <td class="px-4 py-3 min-w-0">
                            <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">
                                #{{ r.id }} · {{ r.service?.category?.name || '—' }}
                            </p>
                            <p class="font-semibold text-slate-900 truncate max-w-[280px]">{{ r.service?.title || '—' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ r.provider?.name || '—' }}</p>
                            <p class="text-xs text-slate-500">{{ r.messages_count || 0 }} mensaje(s)</p>
                        </td>
                        <td class="px-4 py-3"><StatusPill :status="r.status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                            {{ new Date(r.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <AppButton variant="primary" size="sm" @click="openDetail(r.id)">Ver detalle</AppButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="detailOpen && activeRequest"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="closeDetail"
        >
            <div class="w-full max-w-4xl max-h-[88vh] overflow-y-auto rounded-2xl bg-white border border-slate-200 shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3 z-10">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">
                            Solicitud #{{ activeRequest.id }}
                        </p>
                        <h3 class="text-lg font-bold text-[#0b1c30] truncate">
                            {{ activeRequest.service?.title || 'Detalle de solicitud' }}
                        </h3>
                        <p class="text-sm text-slate-600">
                            {{ activeRequest.provider?.name || '—' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusPill :status="activeRequest.status" />
                        <button type="button" class="text-slate-500 hover:text-slate-800 text-xl leading-none" @click="closeDetail">×</button>
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <AppButton
                            v-if="activeRequest.service?.id"
                            variant="outline"
                            size="sm"
                            @click="openListingPreview(activeRequest.service.id)"
                        >
                            Ver anuncio
                        </AppButton>
                        <a
                            v-if="activeRequest.provider?.whatsapp"
                            :href="`https://wa.me/${String(activeRequest.provider.whatsapp).replace(/\\D/g,'')}`"
                            target="_blank" rel="noopener noreferrer"
                            class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 no-underline text-sm"
                        >WhatsApp</a>
                        <a
                            v-if="activeRequest.provider?.contact_phone"
                            :href="`tel:${String(activeRequest.provider.contact_phone).replace(/\\D/g,'')}`"
                            class="rounded-lg border border-slate-200 hover:border-[#003874]/40 font-bold px-4 py-2 text-slate-800 no-underline text-sm"
                        >Llamar</a>
                    </div>

                    <RequestConversation
                        class="mb-4"
                        :request-id="activeRequest.id"
                        :closed="requestClosed(activeRequest)"
                        @sent="store.load"
                    />

                    <RequestReviewForm
                        v-if="activeRequest.can_review"
                        class="mb-4"
                        :service-request-id="activeRequest.id"
                        :provider-name="activeRequest.provider?.name"
                        @submitted="store.load"
                    />

                    <div
                        v-else-if="activeRequest.review"
                        class="rounded-xl border border-amber-100 bg-amber-50/60 p-4 mb-4"
                    >
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-800 mb-2">Tu valoración</p>
                        <StarRatingInput :model-value="activeRequest.review.rating" readonly size="sm" />
                        <p v-if="activeRequest.review.comment" class="text-sm text-slate-700 mt-2 whitespace-pre-wrap">{{ activeRequest.review.comment }}</p>
                    </div>

                    <div v-if="activeRequest.latest_quote" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-4">
                    <div class="flex justify-between items-start gap-4 flex-wrap">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Cotización</p>
                            <p class="text-2xl font-black text-[#003874]">
                                <Money :amount="activeRequest.latest_quote.amount" :currency="activeRequest.latest_quote.currency" />
                            </p>
                            <p v-if="activeRequest.latest_quote.estimated_days" class="text-xs text-slate-600 mt-1">
                                Tiempo estimado: {{ activeRequest.latest_quote.estimated_days }} días
                            </p>
                            <p v-if="activeRequest.latest_quote.notes" class="text-sm text-slate-700 mt-2 whitespace-pre-wrap">
                                {{ activeRequest.latest_quote.notes }}
                            </p>
                        </div>
                        <StatusPill :status="activeRequest.latest_quote.status" />
                    </div>
                    <div v-if="activeRequest.latest_quote.status === 'pendiente'" class="mt-4 flex flex-wrap gap-2">
                        <AppButton
                            variant="primary"
                            size="sm"
                            :loading="busyId === activeRequest.latest_quote.id"
                            @click="decide(activeRequest.latest_quote.id, 'aceptar')"
                        >
                            Aceptar cotización
                        </AppButton>
                        <AppButton
                            variant="ghost"
                            size="sm"
                            @click="decide(activeRequest.latest_quote.id, 'rechazar')"
                        >
                            Rechazar
                        </AppButton>
                    </div>
                    </div>

                    <div v-if="activeRequest.latest_quote?.status === 'aceptada' && !activeRequest.payment" class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-4">
                        <p class="text-sm text-amber-900 mb-3">
                            Para asegurar el trabajo, paga el monto cotizado a Busca PE. Cuando confirmes que el servicio terminó,
                            liberamos el dinero al proveedor (menos comisión).
                        </p>
                        <AppButton variant="secondary" size="sm" @click="openPay(activeRequest.id)">
                            Registrar pago
                        </AppButton>
                    </div>

                    <form
                        v-if="payOpenId === activeRequest.id && activeRequest.latest_quote"
                        @submit.prevent="submitPay(activeRequest.latest_quote.id)"
                        class="rounded-xl border border-slate-200 bg-white p-4 mb-4 space-y-3"
                    >
                    <p class="text-sm text-slate-700">
                        Paga vía Yape/Plin/Transferencia a Busca PE:
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
                    <label class="block text-sm">
                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1">
                            Captura de la transferencia
                            <span v-if="store.proofRequired" class="text-rose-600">*</span>
                        </span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            @change="onProofPick"
                            class="w-full text-sm"
                        />
                        <p v-if="store.proofRequired" class="text-xs text-slate-500 mt-1">
                            Adjunta la captura del Yape/Plin o de la transferencia. Máx 5 MB.
                        </p>
                        <img
                            v-if="proofPreviewUrl"
                            :src="proofPreviewUrl"
                            alt="Vista previa"
                            class="mt-2 max-h-48 rounded-lg border border-slate-200"
                        />
                    </label>
                    <div class="flex justify-end gap-2">
                        <AppButton variant="ghost" size="sm" type="button" @click="payOpenId = null">Cancelar</AppButton>
                        <AppButton variant="primary" size="sm" type="submit" :loading="busyId === activeRequest.latest_quote.id">
                            Confirmar pago
                        </AppButton>
                    </div>
                    </form>

                    <div v-if="activeRequest.payment" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-4">
                    <div class="flex justify-between items-start gap-4 flex-wrap">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pago</p>
                            <p class="text-xl font-black text-[#003874]">
                                <Money :amount="activeRequest.payment.amount" />
                            </p>
                            <p class="text-xs text-slate-600 mt-1">
                                {{ activeRequest.payment.payment_method }} · ref: {{ activeRequest.payment.payment_reference || '—' }}
                            </p>
                        </div>
                        <StatusPill :status="activeRequest.payment.status" />
                    </div>
                    <a
                        v-if="activeRequest.payment.proof_image_url"
                        :href="activeRequest.payment.proof_image_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-block mt-3 text-xs text-[#003874] underline"
                    >Ver mi captura del pago</a>
                    <div v-if="activeRequest.payment.status === 'en_custodia'" class="mt-4 space-y-3">
                        <p class="text-sm text-slate-700">
                            Cuando el proveedor termine el trabajo y estés conforme, libera el pago.
                            Si algo no está bien, abre una disputa.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <AppButton
                                variant="primary"
                                size="sm"
                                :loading="busyId === activeRequest.payment.id"
                                @click="confirmCompleted(activeRequest.payment.id)"
                            >
                                Confirmar trabajo terminado
                            </AppButton>
                            <AppButton variant="ghost" size="sm" @click="openDispute(activeRequest.payment.id)">
                                Reportar problema
                            </AppButton>
                        </div>
                        <form
                            v-if="disputeOpenId === activeRequest.payment.id"
                            @submit.prevent="submitDispute(activeRequest.payment.id)"
                            class="rounded-lg border border-rose-200 bg-rose-50/50 p-3 space-y-2"
                        >
                            <textarea
                                v-model="disputeReason"
                                rows="3"
                                maxlength="500"
                                placeholder="Describe qué falló (mínimo 10 caracteres)…"
                                class="w-full rounded-md border border-rose-200 bg-white px-3 py-2 text-sm outline-none focus:border-rose-500"
                            ></textarea>
                            <div class="flex justify-end gap-2">
                                <AppButton variant="ghost" size="sm" type="button" @click="disputeOpenId = null">Cancelar</AppButton>
                                <AppButton variant="primary" size="sm" type="submit" :loading="busyId === activeRequest.payment.id">
                                    Enviar disputa
                                </AppButton>
                            </div>
                        </form>
                    </div>
                    </div>

                    <!-- Evidencia del trabajo entregada por el proveedor -->
                    <div v-if="(activeRequest.evidence?.length || 0) > 0" class="rounded-xl border border-slate-100 bg-white p-4 mb-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
                        Evidencia entregada por el proveedor
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        <a
                            v-for="ev in activeRequest.evidence"
                            :key="ev.id"
                            :href="ev.url"
                            target="_blank"
                            rel="noopener"
                            class="block"
                        >
                            <img :src="ev.url" :alt="ev.caption || 'Evidencia'"
                                class="w-full h-24 object-cover rounded-md border border-slate-200" />
                        </a>
                    </div>
                    <p v-if="activeRequest.delivered_at" class="text-xs text-slate-500 mt-2">
                        Entregado el {{ new Date(activeRequest.delivered_at).toLocaleString() }}.
                        <span v-if="activeRequest.auto_release_at">
                            Si no respondes, el pago se libera automáticamente el
                            {{ new Date(activeRequest.auto_release_at).toLocaleDateString() }}.
                        </span>
                    </p>
                    </div>

                    <!-- Timeline -->
                    <details v-if="(activeRequest.timeline?.length || 0) > 0" class="rounded-xl border border-slate-100 bg-white p-4 mb-4">
                    <summary class="text-xs font-bold uppercase tracking-wide text-slate-500 cursor-pointer">
                        Historial ({{ activeRequest.timeline.length }} eventos)
                    </summary>
                    <ul class="mt-2 space-y-1 text-xs text-slate-600">
                        <li v-for="ev in activeRequest.timeline" :key="ev.id" class="flex gap-2">
                            <span class="text-slate-400 shrink-0">{{ new Date(ev.created_at).toLocaleString() }}</span>
                            <span><strong>{{ ev.actor_role || 'sistema' }}</strong>: {{ ev.from_status || '—' }} → {{ ev.to_status }}{{ ev.note ? ' · ' + ev.note : '' }}</span>
                        </li>
                    </ul>
                    </details>
                </div>
            </div>
        </div>

        <ListingPreviewModal
            :open="previewOpen"
            :listing-id="previewListingId"
            @close="previewOpen = false"
        />
    </div>
</template>
