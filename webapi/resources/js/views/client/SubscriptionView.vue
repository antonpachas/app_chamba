<script setup>
import { computed, onMounted, ref } from 'vue';
import { useSubscriptionStore } from '@/stores/subscription';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';

const store = useSubscriptionStore();

const showPay = ref(false);
const formError = ref('');
const formSuccess = ref('');
const submitting = ref(false);
const proofFile = ref(null);
const proofPreview = ref('');
const form = ref({ payment_method: 'yape', payment_reference: '', notes: '' });

const premiumPlan = computed(() => store.plans.find((p) => p.code === 'client_premium'));

function onProofChange(e) {
    const f = e.target.files?.[0];
    if (!f) { proofFile.value = null; proofPreview.value = ''; return; }
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
        formError.value = 'Solo se aceptan JPG, PNG o WEBP.';
        e.target.value = '';
        return;
    }
    if (f.size > 5 * 1024 * 1024) {
        formError.value = 'La imagen excede 5 MB.';
        e.target.value = '';
        return;
    }
    formError.value = '';
    proofFile.value = f;
    proofPreview.value = URL.createObjectURL(f);
}

onMounted(async () => {
    await Promise.all([store.loadPlans('cliente'), store.loadMine()]);
});

async function submit() {
    formError.value = '';
    formSuccess.value = '';
    submitting.value = true;
    try {
        await store.pay({ plan_code: 'client_premium', ...form.value, proof: proofFile.value });
        formSuccess.value = 'Pago registrado. Lo revisaremos y activaremos tu Premium en unas horas.';
        showPay.value = false;
        form.value = { payment_method: 'yape', payment_reference: '', notes: '' };
        proofFile.value = null;
        proofPreview.value = '';
    } catch (e) {
        formError.value = e?.message || 'No se pudo registrar el pago.';
    } finally {
        submitting.value = false;
    }
}

function fmtDate(d) {
    return d ? new Date(d).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 rounded-3xl bg-grad-warm text-white p-6 md:p-10 relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-xs font-bold uppercase tracking-widest text-white/80">Premium</p>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mt-1">Busca PE Premium para clientes</h1>
                <p class="text-white/90 mt-1">Más contactos al mes, sin anuncios y prioridad al contactar negocios.</p>
            </div>
            <div class="pointer-events-none absolute -bottom-20 -right-20 w-72 h-72 bg-[#7c3aed]/30 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -top-20 -left-20 w-72 h-72 bg-[#0ea5e9]/30 rounded-full blur-3xl"></div>
        </header>

        <section v-if="store.subscription" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="rounded-2xl bg-grad-card-violet border border-violet-200 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-violet-900">Plan actual</p>
                <p class="text-2xl font-black text-violet-950 mt-2">{{ store.subscription.plan?.name || '—' }}</p>
                <p class="text-xs text-violet-900/70 mt-1 capitalize">Estado: {{ store.subscription.status }}</p>
            </div>
            <div class="rounded-2xl bg-grad-card-blue border border-blue-200 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">
                    {{ store.subscription.in_trial ? 'Termina trial' : 'Próximo cobro' }}
                </p>
                <p class="text-2xl font-black text-[#003874] mt-2">
                    {{ fmtDate(store.subscription.trial_ends_at || store.subscription.current_period_end) }}
                </p>
            </div>
        </section>

        <div v-if="formSuccess" class="rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 mb-6 text-sm">
            {{ formSuccess }}
        </div>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <article class="rounded-3xl bg-white border border-slate-200 p-6">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Plan Free</p>
                <p class="text-3xl font-black text-slate-800 mt-1">S/ 0<span class="text-base font-medium text-slate-500">/mes</span></p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="material-symbols-outlined text-emerald-600 text-[20px]">check</span> Buscar y solicitar servicios</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-emerald-600 text-[20px]">check</span> Marcar favoritos</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-slate-400 text-[20px]">close</span> Límite de contactos al mes</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-slate-400 text-[20px]">close</span> Soporte estándar</li>
                </ul>
            </article>

            <article class="rounded-3xl bg-grad-warm text-white p-6 relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/15 rounded-full blur-2xl"></div>
                <p class="text-xs font-bold uppercase tracking-widest text-white/85">Premium · Te cuidamos</p>
                <p class="text-3xl font-black mt-1">
                    <Money :amount="premiumPlan?.price || 9" /><span class="text-base font-medium text-white/80">/mes</span>
                </p>
                <ul class="mt-5 space-y-2 text-sm text-white/95">
                    <li class="flex gap-2"><span class="material-symbols-outlined text-[#fef3c7] text-[20px]">check</span> Insignia "Cliente Verificado" (te responden más rápido)</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-[#fef3c7] text-[20px]">check</span> Soporte prioritario 24/7</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-[#fef3c7] text-[20px]">check</span> Contactos ilimitados y experiencia sin publicidad</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-[#fef3c7] text-[20px]">check</span> Acceso anticipado a negocios top</li>
                    <li class="flex gap-2"><span class="material-symbols-outlined text-[#fef3c7] text-[20px]">check</span> Sin publicidad en la app</li>
                </ul>

                <button
                    v-if="!store.isPro"
                    @click="showPay = !showPay"
                    class="mt-6 w-full bg-white text-orange-700 font-bold rounded-full py-3 hover:brightness-105"
                >
                    Hacerme Premium
                </button>
                <div v-else class="mt-6 rounded-full bg-white/20 border border-white/30 backdrop-blur text-center font-bold py-2.5">
                    Ya eres Premium
                </div>
            </article>
        </section>

        <section v-if="showPay" class="rounded-3xl bg-white border border-slate-200 p-6 mb-8">
            <h2 class="text-xl font-bold text-[#0b1c30] mb-1">Activa tu Premium</h2>
            <p class="text-sm text-slate-600 mb-4">Yapea o transfiere y carga la referencia. Activamos tu cuenta al confirmar.</p>

            <div class="rounded-2xl bg-orange-50 border border-orange-200 p-4 mb-4 text-sm text-orange-900">
                <p class="font-bold mb-1">Datos para pagar:</p>
                <p>Yape/Plin: <strong>{{ store.platform.yape }}</strong></p>
                <p>{{ store.platform.bank_name }}: <strong>{{ store.platform.bank_account }}</strong> · {{ store.platform.bank_holder }}</p>
                <p class="mt-1">Monto: <strong><Money :amount="premiumPlan?.price || 9" /></strong></p>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Método</span>
                    <select v-model="form.payment_method" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-[#003874] focus:ring-0">
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Código / referencia</span>
                    <input v-model="form.payment_reference" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-[#003874] focus:ring-0" placeholder="Ej: ABC123 o N° de operación" />
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Notas (opcional)</span>
                    <textarea v-model="form.notes" rows="2" maxlength="500" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:border-[#003874] focus:ring-0"></textarea>
                </label>

                <div class="md:col-span-2 rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-orange-900 mb-1">Captura del Yape/Plin (opcional pero recomendado)</p>
                    <p class="text-xs text-slate-600 mb-2">Sube una foto del comprobante para acelerar la validación. JPG/PNG/WEBP · máx. 5 MB.</p>
                    <div class="flex items-center gap-3">
                        <input type="file" accept="image/jpeg,image/png,image/webp" @change="onProofChange" class="block text-sm" />
                        <img v-if="proofPreview" :src="proofPreview" alt="" class="w-20 h-20 rounded-lg object-cover ring-1 ring-slate-200" />
                    </div>
                </div>

                <p v-if="formError" class="md:col-span-2 text-sm text-rose-700">{{ formError }}</p>
                <div class="md:col-span-2 flex gap-2 justify-end">
                    <button type="button" @click="showPay = false" class="rounded-full px-5 py-2.5 font-bold text-slate-700 hover:bg-slate-100">Cancelar</button>
                    <button :disabled="submitting" type="submit" class="btn-grad-warm rounded-full px-6 py-2.5 font-bold disabled:opacity-60">
                        {{ submitting ? 'Enviando...' : 'Registrar pago' }}
                    </button>
                </div>
            </form>
        </section>

        <section v-if="store.payments.length" class="rounded-3xl bg-white border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[#0b1c30]">Historial de pagos</h2>
                    <p class="text-xs text-slate-500">El monto guardado corresponde al precio que pagaste, sin importar futuros cambios.</p>
                </div>
                <span class="text-xs px-3 py-1 rounded-full bg-chamba-50 text-chamba-700 font-bold">{{ store.payments.length }} registros</span>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[800px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Plan</th>
                        <th class="text-right px-4 py-3">Monto</th>
                        <th class="text-left px-4 py-3">Periodo</th>
                        <th class="text-left px-4 py-3">Método</th>
                        <th class="text-left px-4 py-3">Referencia</th>
                        <th class="text-left px-4 py-3">Comprobante</th>
                        <th class="text-left px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in store.payments" :key="p.id" class="border-t border-slate-100 hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">{{ fmtDate(p.created_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-[#0b1c30]">{{ p.plan?.name || '—' }}</div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400">{{ p.plan?.tier || '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold whitespace-nowrap"><Money :amount="p.amount" /></td>
                        <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                            <template v-if="p.period_start && p.period_end">
                                {{ fmtDate(p.period_start) }} → {{ fmtDate(p.period_end) }}
                            </template>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ p.payment_method }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ p.payment_reference || '—' }}</td>
                        <td class="px-4 py-3">
                            <a v-if="p.proof_image_url" :href="p.proof_image_url" target="_blank" rel="noopener" class="inline-block">
                                <img :src="p.proof_image_url" alt="comprobante" class="w-12 h-12 object-cover rounded ring-1 ring-slate-200 hover:ring-orange-500 transition" />
                            </a>
                            <span v-else class="text-slate-400 text-xs">—</span>
                        </td>
                        <td class="px-4 py-3"><StatusPill :status="p.status" /></td>
                    </tr>
                </tbody>
            </table>
            </div>
        </section>
    </div>
</template>
