<script setup>
import { onMounted, ref } from 'vue';
import { useProviderRequestsStore } from '@/stores/providerRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import { escrowEnabled } from '@/services/features';
import { useProviderNotificationsStore } from '@/stores/providerNotifications';
import ListingPreviewModal from '@/components/listing/ListingPreviewModal.vue';
import RequestConversation from '@/components/requests/RequestConversation.vue';

const store = useProviderRequestsStore();
const notifications = useProviderNotificationsStore();
const previewListingId = ref(null);
const previewOpen = ref(false);
const escrow = escrowEnabled();
const quoteOpen = ref(null);
const quoteForm = ref({ amount: '', estimated_days: '', notes: '' });
const evidenceOpenId = ref(null);
const evidenceFiles = ref([]);
const evidenceCaption = ref('');
const busy = ref(null);
const err = ref('');
const ok = ref('');

onMounted(async () => {
    await store.load();
    await notifications.markAllRead();
});

function openListingPreview(serviceId) {
    if (!serviceId) return;
    previewListingId.value = Number(serviceId);
    previewOpen.value = true;
}

function openQuote(id) {
    quoteOpen.value = id;
    quoteForm.value = { amount: '', estimated_days: '', notes: '' };
    err.value = ''; ok.value = '';
}
async function sendQuote(reqId) {
    busy.value = reqId;
    err.value = ''; ok.value = '';
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
    } finally { busy.value = null; }
}
async function setStatus(id, status) {
    busy.value = id;
    err.value = '';
    try {
        await store.setStatus(id, status);
    } catch (e) {
        err.value = e.message;
    } finally { busy.value = null; }
}

function openEvidence(id) {
    evidenceOpenId.value = id;
    evidenceFiles.value = [];
    evidenceCaption.value = '';
    err.value = ''; ok.value = '';
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
    err.value = ''; ok.value = '';
    try {
        await store.uploadEvidence(id, evidenceFiles.value, evidenceCaption.value || null);
        evidenceFiles.value = [];
        evidenceCaption.value = '';
        ok.value = 'Evidencia subida.';
    } catch (e) {
        err.value = e.message;
    } finally { busy.value = null; }
}
async function deleteEvidence(reqId, evId) {
    busy.value = evId;
    err.value = '';
    try {
        await store.deleteEvidence(reqId, evId);
    } catch (e) {
        err.value = e.message;
    } finally { busy.value = null; }
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
    } finally { busy.value = null; }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Contactos recibidos</h1>
            <p class="text-slate-600 mt-1">
                {{ escrow ? 'Responde por mensaje, cotiza y gestiona pagos en custodia.' : 'Responde por mensaje a quien te contactó y cierra cuando termines.' }}
            </p>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>
        <p v-else-if="!store.items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no tienes solicitudes recibidas.
        </p>
        <div v-else class="space-y-4">
            <article v-for="r in store.items" :key="r.id" class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
                <div class="flex flex-wrap justify-between items-start gap-3 mb-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">#{{ r.id }} · {{ r.service?.category?.name || '—' }}</p>
                        <h3 class="text-lg font-bold text-slate-900">
                            <button
                                v-if="r.service?.id"
                                type="button"
                                class="text-left hover:text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer font-bold"
                                @click="openListingPreview(r.service.id)"
                            >{{ r.service.title }}</button>
                            <span v-else>—</span>
                        </h3>
                        <p class="text-sm text-slate-600 mt-0.5">Cliente: <strong>{{ r.client?.name }}</strong></p>
                    </div>
                    <StatusPill :status="r.status" />
                </div>
                <RequestConversation
                    class="mb-4"
                    :request-id="r.id"
                    :closed="requestClosed(r)"
                    @sent="store.load"
                />

                <div v-if="escrow && r.latest_quote" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-3">
                    <div class="flex justify-between items-start gap-3 flex-wrap">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tu última cotización</p>
                            <p class="text-xl font-black text-[#003874]"><Money :amount="r.latest_quote.amount" /></p>
                        </div>
                        <StatusPill :status="r.latest_quote.status" />
                    </div>
                </div>

                <div v-if="escrow && r.active_payment" class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 mb-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Pago del cliente</p>
                    <p class="text-sm text-slate-700">
                        Bruto <strong><Money :amount="r.active_payment.amount" /></strong>
                        · Comisión Busca PE ({{ r.active_payment.commission_rate }}%)
                        <strong><Money :amount="r.active_payment.commission_amount" /></strong>
                        · Te corresponde <strong class="text-emerald-700"><Money :amount="r.active_payment.net_amount" /></strong>
                    </p>
                    <StatusPill :status="r.active_payment.status" class="mt-2" />
                    <a
                        v-if="r.active_payment.proof_image_url"
                        :href="r.active_payment.proof_image_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-block mt-2 text-xs text-[#003874] underline"
                    >Ver captura del cliente</a>
                </div>

                <form v-if="escrow && quoteOpen === r.id" @submit.prevent="sendQuote(r.id)" class="rounded-xl border border-slate-200 p-4 mb-3 space-y-3">
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
                        <AppButton variant="primary" type="submit" :loading="busy === r.id">Enviar cotización</AppButton>
                    </div>
                </form>

                <!-- Evidencia: galería actual + uploader -->
                <div v-if="escrow && (r.evidence?.length || 0) > 0" class="rounded-xl border border-slate-100 bg-white p-4 mb-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Evidencia subida</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        <div v-for="ev in r.evidence" :key="ev.id" class="relative">
                            <a :href="ev.url" target="_blank" rel="noopener">
                                <img :src="ev.url" :alt="ev.caption || 'Evidencia'"
                                    class="w-full h-24 object-cover rounded-md border border-slate-200" />
                            </a>
                            <button
                                v-if="!['entregado','confirmado','cerrado'].includes(r.status)"
                                type="button"
                                class="absolute top-1 right-1 rounded-full bg-white/90 border border-rose-300 text-rose-700 text-xs px-2 py-0.5"
                                :disabled="busy === ev.id"
                                @click="deleteEvidence(r.id, ev.id)"
                            >Quitar</button>
                        </div>
                    </div>
                </div>

                <form v-if="escrow && evidenceOpenId === r.id" @submit.prevent="uploadEvidence(r.id)" class="rounded-xl border border-slate-200 p-4 mb-3 space-y-3">
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
                        <AppButton variant="primary" type="submit" :loading="busy === r.id">Subir fotos</AppButton>
                    </div>
                </form>

                <!-- Acciones -->
                <div class="flex flex-wrap gap-2">
                    <template v-if="!escrow">
                        <AppButton v-if="r.status === 'nuevo'" size="sm" variant="primary" :loading="busy === r.id" @click="setStatus(r.id, 'visto')">Marcar visto</AppButton>
                        <AppButton v-if="['nuevo','visto'].includes(r.status)" size="sm" variant="ghost" :loading="busy === r.id" @click="setStatus(r.id, 'cerrado')">Cerrar</AppButton>
                    </template>
                    <template v-else>
                        <AppButton v-if="['nuevo','contactado','cotizado'].includes(r.status)" size="sm" variant="primary" @click="openQuote(r.id)">
                            {{ r.latest_quote ? 'Re-cotizar' : 'Cotizar' }}
                        </AppButton>
                        <AppButton v-if="r.status === 'nuevo'" size="sm" variant="ghost" :loading="busy === r.id" @click="setStatus(r.id, 'contactado')">Marcar contactado</AppButton>
                        <AppButton v-if="r.status === 'en_custodia'" size="sm" variant="primary" :loading="busy === r.id" @click="setStatus(r.id, 'en_progreso')">Iniciar trabajo</AppButton>
                        <AppButton
                            v-if="['en_custodia','en_progreso'].includes(r.status)"
                            size="sm"
                            variant="ghost"
                            @click="openEvidence(r.id)"
                        >
                            {{ (r.evidence?.length || 0) > 0 ? 'Agregar más evidencia' : 'Subir evidencia' }}
                        </AppButton>
                        <AppButton
                            v-if="['en_custodia','en_progreso'].includes(r.status) && (r.evidence?.length || 0) > 0"
                            size="sm"
                            variant="primary"
                            :loading="busy === r.id"
                            @click="markDelivered(r.id)"
                        >
                            Marcar como entregado
                        </AppButton>
                        <span v-if="r.status === 'entregado'" class="text-xs text-emerald-700 font-semibold">
                            Esperando confirmación del cliente.
                        </span>
                    </template>
                    <a v-if="r.client?.phone" :href="`tel:${String(r.client.phone).replace(/\\D/g,'')}`" class="rounded-lg border border-slate-200 hover:border-[#003874]/40 font-bold px-4 py-2 text-sm text-slate-800 no-underline">Llamar cliente</a>
                </div>
            </article>
        </div>

        <ListingPreviewModal
            :open="previewOpen"
            :listing-id="previewListingId"
            @close="previewOpen = false"
        />
    </div>
</template>
