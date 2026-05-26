<script setup>
import { onMounted, ref, computed } from 'vue';
import { useWalletStore } from '@/stores/wallet';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';

const store = useWalletStore();

const bankForm = ref({ bank_name: '', bank_account_number: '', bank_account_holder: '', yape_phone: '' });
const wForm = ref({ amount: '', payout_method: 'yape' });
const err = ref('');
const ok = ref('');
const savingBank = ref(false);
const requesting = ref(false);

const balance = computed(() => Number(store.wallet?.balance ?? 0));

onMounted(async () => {
    await store.load();
    if (store.wallet) {
        bankForm.value = {
            bank_name: store.wallet.bank_name || '',
            bank_account_number: store.wallet.bank_account_number || '',
            bank_account_holder: store.wallet.bank_account_holder || '',
            yape_phone: store.wallet.yape_phone || '',
        };
    }
});

async function saveBank() {
    err.value = ''; ok.value = '';
    savingBank.value = true;
    try {
        await store.updateBank(bankForm.value);
        ok.value = 'Datos de cobro guardados.';
    } catch (e) { err.value = e.message; }
    finally { savingBank.value = false; }
}

async function requestWithdrawal() {
    err.value = ''; ok.value = '';
    if (Number(wForm.value.amount) <= 0) { err.value = 'Ingresa un monto válido.'; return; }
    if (Number(wForm.value.amount) > balance.value) { err.value = 'Monto mayor al saldo disponible.'; return; }
    requesting.value = true;
    try {
        await store.requestWithdrawal({
            amount: Number(wForm.value.amount),
            payout_method: wForm.value.payout_method,
        });
        ok.value = 'Retiro solicitado. Lo procesaremos pronto.';
        wForm.value.amount = '';
    } catch (e) { err.value = e.message; }
    finally { requesting.value = false; }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis ingresos</h1>
            <p class="text-slate-600 mt-1">Administra tu saldo y solicita retiros.</p>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Saldo disponible</p>
                <p class="text-3xl font-black text-emerald-700 mt-2"><Money :amount="store.wallet?.balance" /></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Total ganado</p>
                <p class="text-2xl font-black text-[#003874] mt-2"><Money :amount="store.wallet?.total_earned" /></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Total retirado</p>
                <p class="text-2xl font-black text-slate-800 mt-2"><Money :amount="store.wallet?.total_withdrawn" /></p>
            </div>
        </div>

        <section class="grid lg:grid-cols-2 gap-6 mb-8">
            <form @submit.prevent="saveBank" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Datos de cobro</h2>
                <p class="text-sm text-slate-600">Necesarios para enviarte tu dinero.</p>
                <AppInput v-model="bankForm.yape_phone" label="Yape / Plin (teléfono)" placeholder="999999999" />
                <AppInput v-model="bankForm.bank_name" label="Banco" placeholder="BCP" />
                <AppInput v-model="bankForm.bank_account_number" label="N° de cuenta (CCI)" placeholder="00200012345678901234" />
                <AppInput v-model="bankForm.bank_account_holder" label="Titular" />
                <AppButton variant="primary" type="submit" :loading="savingBank">Guardar</AppButton>
            </form>

            <form @submit.prevent="requestWithdrawal" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Solicitar retiro</h2>
                <p class="text-sm text-slate-600">Tu saldo disponible: <strong><Money :amount="store.wallet?.balance" /></strong>.</p>
                <AppInput v-model="wForm.amount" label="Monto a retirar (S/)" type="number" required />
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Método</span>
                    <select v-model="wForm.payout_method" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874]">
                        <option value="yape">Yape/Plin</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="plin">Plin</option>
                    </select>
                </label>
                <AppButton variant="secondary" type="submit" :loading="requesting" :disabled="balance <= 0">
                    Solicitar retiro
                </AppButton>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 mb-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Pagos recientes</h2>
            <p v-if="!store.recentPayments.length" class="text-slate-500">Sin movimientos.</p>
            <table v-else class="w-full text-sm">
                <thead class="text-xs font-bold uppercase text-slate-500">
                    <tr>
                        <th class="text-left pb-2">Servicio</th>
                        <th class="text-right pb-2">Bruto</th>
                        <th class="text-right pb-2">Comisión</th>
                        <th class="text-right pb-2">Neto</th>
                        <th class="text-left pb-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in store.recentPayments" :key="p.id" class="border-t border-slate-100">
                        <td class="py-2">{{ p.service_title }}</td>
                        <td class="py-2 text-right"><Money :amount="p.amount" /></td>
                        <td class="py-2 text-right text-red-700">- <Money :amount="p.commission_amount" /></td>
                        <td class="py-2 text-right font-bold text-emerald-700"><Money :amount="p.net_amount" /></td>
                        <td class="py-2"><StatusPill :status="p.status" /></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Mis retiros</h2>
            <p v-if="!store.withdrawals.length" class="text-slate-500">Sin retiros.</p>
            <table v-else class="w-full text-sm">
                <thead class="text-xs font-bold uppercase text-slate-500">
                    <tr>
                        <th class="text-left pb-2">Fecha</th>
                        <th class="text-right pb-2">Monto</th>
                        <th class="text-left pb-2">Método</th>
                        <th class="text-left pb-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="w in store.withdrawals" :key="w.id" class="border-t border-slate-100">
                        <td class="py-2 text-slate-500 text-xs">{{ new Date(w.created_at).toLocaleString('es-PE') }}</td>
                        <td class="py-2 text-right font-bold"><Money :amount="w.amount" /></td>
                        <td class="py-2 capitalize">{{ w.payout_method }}</td>
                        <td class="py-2"><StatusPill :status="w.status" /></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
