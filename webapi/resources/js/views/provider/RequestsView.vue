<script setup>
import { computed, onMounted, ref } from 'vue';
import { useProviderRequestsStore } from '@/stores/providerRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import { escrowEnabled } from '@/services/features';
import { useUserNotificationsStore } from '@/stores/userNotifications';
import ListingPreviewModal from '@/components/listing/ListingPreviewModal.vue';
import RequestConversation from '@/components/requests/RequestConversation.vue';

const store = useProviderRequestsStore();
const notifications = useUserNotificationsStore();
const previewListingId = ref(null);
const previewOpen = ref(false);
const detailOpen = ref(false);
const detailRequestId = ref(null);
const escrow = escrowEnabled();
const quoteOpen = ref(null);
const quoteForm = ref({ amount: '', estimated_days: '', notes: '' });
const evidenceOpenId = ref(null);
const evidenceFiles = ref([]);
const evidenceCaption = ref('');
const busy = ref(null);
const err = ref('');
const ok = ref('');

const activeRequest = computed(() =>
    store.items.find((r) => Number(r.id) === Number(detailRequestId.value)) || null,
);

onMounted(async () => {
    await store.load();
    await notifications.markAllRead();
});

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
    quoteOpen.value = null;
    evidenceOpenId.value = null;
}

function openQuote(id) {
    quoteOpen.value = id;
    quoteForm.value = { amount: '', estimated_days: '', notes: '' };
    err.value = '';
    ok.value = '';
}

async function sendQuote(reqId) {
    busy.value = reqId;
    err.value = '';
    ok.value = '';
    try {
        await store.sendQuote(reqId, {
            amount: Number(quoteForm.value.amount),
            estimated_days: quoteForm.value.estimated_days === '' ? null : Number(quoteForm.value.estimated_days),
            notes: quoteForm.value.notes || null,
        });
        ok.value = 'Cotización enviada.';
        quoteOpen.value = null;
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function setStatus(id, status) {
    busy.value = id;
    err.value = '';
    try {
        await store.setStatus(id, status);
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

function openEvidence(id) {
    evidenceOpenId.value = id;
    evidenceFiles.value = [];
    evidenceCaption.value = '';
    err.value = '';
    ok.value = '';
}

function onEvidencePick(event) {
    evidenceFiles.value = Array.from(event.target.files || []);
}

async function uploadEvidence(id) {
    if (!evidenceFiles.value.length) {
        err.value = 'Selecciona al menos una foto.';
        return;
    }
    busy.value = id;
    err.value = '';
    ok.value = '';
    try {
        await store.uploadEvidence(id, evidenceFiles.value, evidenceCaption.value || null);
        evidenceFiles.value = [];
        evidenceCaption.value = '';
        ok.value = 'Evidencia subida.';
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function deleteEvidence(reqId, evId) {
    busy.value = evId;
    err.value = '';
    try {
        await store.deleteEvidence(reqId, evId);
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

function requestClosed(r) {
    return ['cerrado', 'cancelado'].includes(r.status);
}

async function markDelivered(id) {
    if (!confirm('¿Marcar como entregado? El cliente recibirá una notificación y tendrá unos días para confirmar o disputar.')) return;
    busy.value = id;
    err.value = '';
    try {
        await store.markDelivered(id);
        ok.value = 'Trabajo marcado como entregado. Esperando confirmación del cliente.';
        evidenceOpenId.value = null;
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Contactos recibidos</h1>
            <p class="text-slate-600 mt-1">Bandeja compacta. Abre cada solicitud para ver chat y acciones.</p>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>
        <p v-else-if="!store.items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no tienes solicitudes recibidas.
        </p>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[860px] text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Solicitud</th>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in store.items" :key="r.id" class="border-t border-slate-100 hover:bg-slate-50/50">
                        <td class="px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">#{{ r.id }} · {{ r.service?.category?.name || '—' }}</p>
                            <p class="font-semibold text-slate-900 truncate max-w-[280px]">{{ r.service?.title || '—' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ r.client?.name || '—' }}</p>
                            <p class="text-xs text-slate-500">{{ r.messages_count || 0 }} mensaje(s)</p>
                        </td>
                        <td class="px-4 py-3"><StatusPill :status="r.status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ new Date(r.created_at).toLocaleDateString() }}</td>
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
                        <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">Solicitud #{{ activeRequest.id }}</p>
                        <h3 class="text-lg font-bold text-slate-900 truncate">{{ activeRequest.service?.title || 'Detalle de solicitud' }}</h3>
                        <p class="text-sm text-slate-600">Cliente: <strong>{{ activeRequest.client?.name || '—' }}</strong></p>
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
                        <a v-if="activeRequest.client?.phone" :href="`tel:${String(activeRequest.client.phone).replace(/\\D/g,'')}`" class="rounded-lg border border-slate-200 hover:border-[#003874]/40 font-bold px-4 py-2 text-sm text-slate-800 no-underline">Llamar cliente</a>
                    </div>

                    <RequestConversation
                        class="mb-4"
                        :request-id="activeRequest.id"
                        :closed="requestClosed(activeRequest)"
                        @sent="store.load"
                    />

                    <div v-if="escrow && activeRequest.latest_quote" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-3">
                        <div class="flex justify-between items-start gap-3 flex-wrap">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tu última cotización</p>
                                <p class="text-xl font-black text-[#003874]"><Money :amount="activeRequest.latest_quote.amount" /></p>
                            </div>
                            <StatusPill :status="activeRequest.latest_quote.status" />
                        </div>
                    </div>

                    <div v-if="escrow && activeRequest.active_payment" class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 mb-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Pago del cliente</p>
                        <p class="text-sm text-slate-700">
                            Bruto <strong><Money :amount="activeRequest.active_payment.amount" /></strong>
                            · Comisión Busca PE ({{ activeRequest.active_payment.commission_rate }}%)
                            <strong><Money :amount="activeRequest.active_payment.commission_amount" /></strong>
                            · Te corresponde <strong class="text-emerald-700"><Money :amount="activeRequest.active_payment.net_amount" /></strong>
                        </p>
                        <StatusPill :status="activeRequest.active_payment.status" class="mt-2" />
                        <a
                            v-if="activeRequest.active_payment.proof_image_url"
                            :href="activeRequest.active_payment.proof_image_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-block mt-2 text-xs text-[#003874] underline"
                        >Ver captura del cliente</a>
                    </div>

                    <form v-if="escrow && quoteOpen === activeRequest.id" @submit.prevent="sendQuote(activeRequest.id)" class="rounded-xl border border-slate-200 p-4 mb-3 space-y-3">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Monto (S/)</span>
                                <input v-model="quoteForm.amount" type="number" step="0.01" min="1" required class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Días estimados</span>
                                <input v-model="quoteForm.estimated_days" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Detalle (opcional)</span>
                            <textarea v-model="quoteForm.notes" rows="2" maxlength="1000" class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]"></textarea>
                        </label>
                        <div class="flex justify-end gap-2">
                            <AppButton variant="ghost" type="button" @click="quoteOpen = null">Cancelar</AppButton>
                            <AppButton variant="primary" type="submit" :loading="busy === activeRequest.id">Enviar cotización</AppButton>
                        </div>
                    </form>

                    <div v-if="escrow && (activeRequest.evidence?.length || 0) > 0" class="rounded-xl border border-slate-100 bg-white p-4 mb-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Evidencia subida</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                            <div v-for="ev in activeRequest.evidence" :key="ev.id" class="relative">
                                <a :href="ev.url" target="_blank" rel="noopener">
                                    <img :src="ev.url" :alt="ev.caption || 'Evidencia'" class="w-full h-24 object-cover rounded-md border border-slate-200" />
                                </a>
                                <button
                                    v-if="!['entregado','confirmado','cerrado'].includes(activeRequest.status)"
                                    type="button"
                                    class="absolute top-1 right-1 rounded-full bg-white/90 border border-rose-300 text-rose-700 text-xs px-2 py-0.5"
                                    :disabled="busy === ev.id"
                                    @click="deleteEvidence(activeRequest.id, ev.id)"
                                >Quitar</button>
                            </div>
                        </div>
                    </div>

                    <form v-if="escrow && evidenceOpenId === activeRequest.id" @submit.prevent="uploadEvidence(activeRequest.id)" class="rounded-xl border border-slate-200 p-4 mb-3 space-y-3">
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Fotos del trabajo</span>
                            <input type="file" multiple accept="image/jpeg,image/png,image/webp" @change="onEvidencePick" class="text-sm" />
                            <p class="text-xs text-slate-500 mt-1">Hasta 10 fotos por subida. Mín. 1 para marcar como entregado.</p>
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Descripción (opcional)</span>
                            <input v-model="evidenceCaption" maxlength="255" class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
                        </label>
                        <div class="flex justify-end gap-2">
                            <AppButton variant="ghost" type="button" @click="evidenceOpenId = null">Cerrar</AppButton>
                            <AppButton variant="primary" type="submit" :loading="busy === activeRequest.id">Subir fotos</AppButton>
                        </div>
                    </form>

                    <div class="flex flex-wrap gap-2">
                        <template v-if="!escrow">
                            <AppButton v-if="activeRequest.status === 'nuevo'" size="sm" variant="primary" :loading="busy === activeRequest.id" @click="setStatus(activeRequest.id, 'visto')">Marcar visto</AppButton>
                            <AppButton v-if="['nuevo','visto'].includes(activeRequest.status)" size="sm" variant="ghost" :loading="busy === activeRequest.id" @click="setStatus(activeRequest.id, 'cerrado')">Cerrar</AppButton>
                        </template>
                        <template v-else>
                            <AppButton v-if="['nuevo','contactado','cotizado'].includes(activeRequest.status)" size="sm" variant="primary" @click="openQuote(activeRequest.id)">
                                {{ activeRequest.latest_quote ? 'Re-cotizar' : 'Cotizar' }}
                            </AppButton>
                            <AppButton v-if="activeRequest.status === 'nuevo'" size="sm" variant="ghost" :loading="busy === activeRequest.id" @click="setStatus(activeRequest.id, 'contactado')">Marcar contactado</AppButton>
                            <AppButton v-if="activeRequest.status === 'en_custodia'" size="sm" variant="primary" :loading="busy === activeRequest.id" @click="setStatus(activeRequest.id, 'en_progreso')">Iniciar trabajo</AppButton>
                            <AppButton
                                v-if="['en_custodia','en_progreso'].includes(activeRequest.status)"
                                size="sm"
                                variant="ghost"
                                @click="openEvidence(activeRequest.id)"
                            >
                                {{ (activeRequest.evidence?.length || 0) > 0 ? 'Agregar más evidencia' : 'Subir evidencia' }}
                            </AppButton>
                            <AppButton
                                v-if="['en_custodia','en_progreso'].includes(activeRequest.status) && (activeRequest.evidence?.length || 0) > 0"
                                size="sm"
                                variant="primary"
                                :loading="busy === activeRequest.id"
                                @click="markDelivered(activeRequest.id)"
                            >
                                Marcar como entregado
                            </AppButton>
                            <span v-if="activeRequest.status === 'entregado'" class="text-xs text-emerald-700 font-semibold">
                                Esperando confirmación del cliente.
                            </span>
                        </template>
                    </div>
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
