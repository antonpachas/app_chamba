<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';
import { resizeImageFile } from '@/services/imageResize';
import StatusPill from '@/components/common/StatusPill.vue';
import Money from '@/components/common/Money.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const items = ref([]);
const loading = ref(false);
const filter = ref('solicitado');
const err = ref('');
const ok = ref('');
const busy = ref(null);
const refForm = ref({});
const proofFiles = ref({});

async function load() {
    loading.value = true; err.value = '';
    try {
        const r = await api.get('/admin/withdrawals', { auth: true, params: { status: filter.value } });
        items.value = r.data || [];
    } catch (e) { err.value = e.message; items.value = []; }
    finally { loading.value = false; }
}
onMounted(load);

function onProofChange(id, ev) {
    proofFiles.value[id] = ev.target.files?.[0] || null;
}

async function pay(id) {
    busy.value = id; err.value = ''; ok.value = '';
    try {
        const fd = new FormData();
        if (refForm.value[id]) fd.append('payout_reference', refForm.value[id]);
        if (proofFiles.value[id]) {
            const ready = await resizeImageFile(proofFiles.value[id], { maxDimension: 1600 });
            fd.append('proof', ready);
        }
        await api.post(`/admin/withdrawals/${id}/pay`, fd, { auth: true });
        ok.value = 'Retiro marcado como pagado.';
        await load();
    } catch (e) { err.value = e.message; }
    finally { busy.value = null; }
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex justify-between items-end gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Retiros</h1>
                <p class="text-slate-600 mt-1">Procesa los retiros solicitados por proveedores.</p>
            </div>
            <select v-model="filter" @change="load()" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                <option value="solicitado">Solicitados</option>
                <option value="pagado">Pagados</option>
                <option value="rechazado">Rechazados</option>
                <option value="all">Todos</option>
            </select>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <p v-if="loading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!items.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">Sin retiros.</div>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Proveedor</th>
                        <th class="text-right px-4 py-3">Monto</th>
                        <th class="text-left px-4 py-3">Método</th>
                        <th class="text-left px-4 py-3">Datos de pago</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="w in items" :key="w.id" class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3 font-bold">#{{ w.id }}</td>
                        <td class="px-4 py-3">{{ w.provider?.name }}</td>
                        <td class="px-4 py-3 text-right font-bold"><Money :amount="w.amount" /></td>
                        <td class="px-4 py-3 capitalize">{{ w.payout_method }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div v-if="w.payout_method === 'transferencia'">
                                {{ w.provider?.bank_name }} · {{ w.provider?.bank_account_number }}<br>
                                <span class="text-slate-500">{{ w.provider?.bank_account_holder }}</span>
                            </div>
                            <div v-else>{{ w.provider?.yape_phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <StatusPill :status="w.status" />
                            <a v-if="w.proof_image_url" :href="w.proof_image_url" target="_blank" rel="noopener"
                                class="block mt-1 text-[10px] text-[#003874] underline">Ver comprobante</a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div v-if="w.status === 'solicitado'" class="flex flex-col gap-1 items-end">
                                <input v-model="refForm[w.id]" placeholder="Referencia" class="rounded-lg border border-slate-200 px-2 py-1 text-xs w-40" />
                                <input type="file" accept="image/jpeg,image/png,image/webp"
                                    @change="onProofChange(w.id, $event)"
                                    class="text-[10px] w-40" />
                                <AppButton size="sm" variant="primary" :loading="busy === w.id" @click="pay(w.id)">Marcar pagado</AppButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
