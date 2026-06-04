<script setup>
import { computed, onMounted, ref } from 'vue';
import { useClientRequestsStore } from '@/stores/clientRequests';
import { useUserNotificationsStore } from '@/stores/userNotifications';
import NotificationsBanner from '@/components/notifications/NotificationsBanner.vue';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import ListingPreviewModal from '@/components/listing/ListingPreviewModal.vue';
import RequestConversation from '@/components/requests/RequestConversation.vue';
import RequestReviewForm from '@/components/requests/RequestReviewForm.vue';
import StarRatingInput from '@/components/common/StarRatingInput.vue';

const store = useClientRequestsStore();
const notifications = useUserNotificationsStore();
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
    await notifications.markAllRead();
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
        localOk.value = 'Confirmaste la finalización. Pago liberado al negocio.';
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
        <header class="mb-6">
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#0b1c30] tracking-tight">Mis solicitudes</h1>
            <p class="text-slate-500 mt-0.5 text-sm">Toca una solicitud para ver el chat y todas las acciones disponibles.</p>
        </header>

        <NotificationsBanner />

        <AppAlert v-if="localError" type="error" class="mb-4">{{ localError }}</AppAlert>
        <AppAlert v-if="localOk" type="success" class="mb-4">{{ localOk }}</AppAlert>

        <div v-if="store.loading" class="flex items-center justify-center py-20">
            <span class="material-symbols-outlined text-[2rem] text-slate-300 animate-spin">progress_activity</span>
        </div>

        <div v-else-if="!store.items.length" class="flex flex-col items-center justify-center py-20 text-center">
            <span class="material-symbols-outlined text-[3.5rem] text-slate-200 mb-3">inbox</span>
            <p class="text-base font-semibold text-slate-600 mb-1">Sin solicitudes aún</p>
            <p class="text-sm text-slate-400">Cuando contactes a un negocio desde un anuncio, aparecerá aquí.</p>
        </div>

        <div v-else class="space-y-2">
            <button
                v-for="r in store.items"
                :key="r.id"
                type="button"
                class="w-full text-left rounded-xl border border-slate-200 bg-white px-4 py-4 flex items-center gap-4 hover:border-[#003874]/30 hover:shadow-sm transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#003874]/30"
                @click="openDetail(r.id)"
            >
                <!-- Ícono de estado -->
                <div
                    class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white"
                    :class="{
                        'bg-emerald-500': r.status === 'cerrado',
                        'bg-amber-400': r.status === 'cotizado',
                        'bg-blue-500': r.status === 'en_proceso',
                        'bg-slate-300': r.status === 'cancelado',
                        'bg-[#003874]': !['cerrado','cotizado','en_proceso','cancelado'].includes(r.status),
                    }"
                >
                    <span class="material-symbols-outlined text-[20px]">
                        {{ r.status === 'cerrado' ? 'check_circle' : r.status === 'cancelado' ? 'cancel' : r.status === 'cotizado' ? 'request_quote' : 'send' }}
                    </span>
                </div>

                <!-- Contenido -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                        <span class="text-[11px] font-bold uppercase tracking-widest text-[#003874]">#{{ r.id }}</span>
                        <span v-if="r.service?.category?.name" class="text-[11px] text-slate-400">· {{ r.service.category.name }}</span>
                        <StatusPill :status="r.status" class="ml-auto" />
                    </div>
                    <p class="font-semibold text-slate-900 truncate leading-snug">{{ r.service?.title || '—' }}</p>
                    <div class="flex flex-wrap items-center gap-3 mt-1">
                        <span class="flex items-center gap-1 text-xs text-slate-500">
                            <span class="material-symbols-outlined text-[13px]">storefront</span>
                            {{ r.provider?.name || '—' }}
                        </span>
                        <span v-if="r.messages_count" class="flex items-center gap-1 text-xs text-slate-400">
                            <span class="material-symbols-outlined text-[13px]">chat_bubble</span>
                            {{ r.messages_count }}
                        </span>
                        <span class="flex items-center gap-1 text-xs text-slate-400 ml-auto">
                            <span class="material-symbols-outlined text-[13px]">schedule</span>
                            {{ new Date(r.created_at).toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                        </span>
                    </div>
                </div>

                <!-- Flecha -->
                <span class="material-symbols-outlined text-[20px] text-slate-300 shrink-0">chevron_right</span>
            </button>
        </div>

        <div
            v-if="detailOpen && activeRequest"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/50 backdrop-blur-sm"
            @click.self="closeDetail"
        >
            <div class="w-full sm:max-w-4xl max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white shadow-2xl">
                <!-- Modal header -->
                <div class="sticky top-0 bg-white/95 backdrop-blur border-b border-slate-100 px-5 py-4 flex items-start justify-between gap-3 z-10">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-[#003874] mb-0.5">
                            Solicitud #{{ activeRequest.id }}
                        </p>
                        <h3 class="text-base font-bold text-[#0b1c30] leading-snug truncate">
                            {{ activeRequest.service?.title || 'Detalle de solicitud' }}
                        </h3>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ activeRequest.provider?.name || '—' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 mt-0.5">
                        <StatusPill :status="activeRequest.status" />
                        <button
                            type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors"
                            @click="closeDetail"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
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
                            liberamos el dinero al negocio (menos comisión).
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
                            Cuando el negocio termine el trabajo y estés conforme, libera el pago.
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

                    <!-- Evidencia del trabajo entregada por el negocio -->
                    <div v-if="(activeRequest.evidence?.length || 0) > 0" class="rounded-xl border border-slate-100 bg-white p-4 mb-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
                        Evidencia entregada por el negocio
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
