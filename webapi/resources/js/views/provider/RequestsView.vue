<script setup>
import { onMounted, ref } from 'vue';
import { useProviderRequestsStore } from '@/stores/providerRequests';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const store = useProviderRequestsStore();
const quoteOpen = ref(null);
const quoteForm = ref({ amount: '', estimated_days: '', notes: '' });
const busy = ref(null);
const err = ref('');
const ok = ref('');

onMounted(() => store.load());

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
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Solicitudes recibidas</h1>
            <p class="text-slate-600 mt-1">Cotiza, marca avances y cobra cuando termines el trabajo.</p>
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
                        <h3 class="text-lg font-bold text-slate-900">{{ r.service?.title }}</h3>
                        <p class="text-sm text-slate-600 mt-0.5">Cliente: <strong>{{ r.client?.name }}</strong></p>
                    </div>
                    <StatusPill :status="r.status" />
                </div>
                <p v-if="r.message" class="text-sm text-slate-700 bg-slate-50 rounded-lg p-3 mb-3 whitespace-pre-wrap">{{ r.message }}</p>

                <div v-if="r.latest_quote" class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 mb-3">
                    <div class="flex justify-between items-start gap-3 flex-wrap">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tu última cotización</p>
                            <p class="text-xl font-black text-[#003874]"><Money :amount="r.latest_quote.amount" /></p>
                        </div>
                        <StatusPill :status="r.latest_quote.status" />
                    </div>
                </div>

                <div v-if="r.active_payment" class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 mb-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Pago del cliente</p>
                    <p class="text-sm text-slate-700">
                        Bruto <strong><Money :amount="r.active_payment.amount" /></strong>
                        · Comisión Chamba ({{ r.active_payment.commission_rate }}%)
                        <strong><Money :amount="r.active_payment.commission_amount" /></strong>
                        · Te corresponde <strong class="text-emerald-700"><Money :amount="r.active_payment.net_amount" /></strong>
                    </p>
                    <StatusPill :status="r.active_payment.status" class="mt-2" />
                </div>

                <form v-if="quoteOpen === r.id" @submit.prevent="sendQuote(r.id)" class="rounded-xl border border-slate-200 p-4 mb-3 space-y-3">
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

                <div class="flex flex-wrap gap-2">
                    <AppButton v-if="['nuevo','contactado','cotizado'].includes(r.status)" size="sm" variant="primary" @click="openQuote(r.id)">
                        {{ r.latest_quote ? 'Re-cotizar' : 'Cotizar' }}
                    </AppButton>
                    <AppButton v-if="r.status === 'nuevo'" size="sm" variant="ghost" :loading="busy === r.id" @click="setStatus(r.id, 'contactado')">Marcar contactado</AppButton>
                    <AppButton v-if="r.status === 'en_custodia'" size="sm" variant="primary" :loading="busy === r.id" @click="setStatus(r.id, 'en_progreso')">Iniciar trabajo</AppButton>
                    <AppButton v-if="r.status === 'en_progreso'" size="sm" variant="primary" :loading="busy === r.id" @click="setStatus(r.id, 'terminado')">Marcar terminado</AppButton>
                    <a v-if="r.client?.phone" :href="`tel:${String(r.client.phone).replace(/\\D/g,'')}`" class="rounded-lg border border-slate-200 hover:border-[#003874]/40 font-bold px-4 py-2 text-sm text-slate-800 no-underline">Llamar cliente</a>
                </div>
            </article>
        </div>
    </div>
</template>
