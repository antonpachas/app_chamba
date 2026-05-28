<script setup>
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import SupportStatusPill from '@/components/support/SupportStatusPill.vue';
import SupportTicketModal from '@/components/support/SupportTicketModal.vue';

const auth = useAuthStore();

const tickets = ref([]);
const meta = ref({});
const loading = ref(false);
const err = ref('');
const ok = ref('');

const statusFilter = ref('all');
const activeId = ref(null);
const activeTicket = ref(null);
const detailOpen = ref(false);
const detailLoading = ref(false);

const showNew = ref(false);
const creating = ref(false);
const form = ref({
    subject: '',
    category: 'otro',
    body: '',
});

const categories = [
    { value: 'cuenta', label: 'Mi cuenta' },
    { value: 'anuncios', label: 'Anuncios / publicación' },
    { value: 'pagos', label: 'Pagos / membresía' },
    { value: 'tecnico', label: 'Problema técnico' },
    { value: 'otro', label: 'Otro' },
];

const categoryLabels = Object.fromEntries(categories.map((c) => [c.value, c.label]));

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}

function categoryLabel(value) {
    return categoryLabels[value] || value || '—';
}

async function loadTickets() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/support-tickets', {
            auth: true,
            params: { status: statusFilter.value, per_page: 50 },
        });
        tickets.value = r.data || [];
        meta.value = r.meta || {};
        if (activeId.value && !tickets.value.find((t) => t.id === activeId.value)) {
            closeDetail();
        }
    } catch (e) {
        err.value = e.message;
        tickets.value = [];
    } finally {
        loading.value = false;
    }
}

async function openTicket(id) {
    activeId.value = id;
    detailOpen.value = true;
    detailLoading.value = true;
    showNew.value = false;
    activeTicket.value = null;
    err.value = '';
    try {
        const r = await api.get(`/support-tickets/${id}`, { auth: true });
        activeTicket.value = r.data || null;
        await loadTickets();
    } catch (e) {
        err.value = e.message;
        activeTicket.value = null;
    } finally {
        detailLoading.value = false;
    }
}

function closeDetail() {
    detailOpen.value = false;
    activeId.value = null;
    activeTicket.value = null;
}

function onTicketRefresh(ticket) {
    activeTicket.value = ticket;
    const idx = tickets.value.findIndex((t) => t.id === ticket.id);
    if (idx >= 0) {
        tickets.value[idx] = { ...tickets.value[idx], ...ticket };
    }
}

async function createTicket() {
    creating.value = true;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(
            '/support-tickets',
            {
                subject: form.value.subject.trim(),
                category: form.value.category,
                body: form.value.body.trim(),
            },
            { auth: true },
        );
        ok.value = r.message || 'Caso creado.';
        showNew.value = false;
        form.value = { subject: '', category: 'otro', body: '' };
        await loadTickets();
        if (r.data?.id) await openTicket(r.data.id);
    } catch (e) {
        err.value = e.message;
    } finally {
        creating.value = false;
    }
}

onMounted(async () => {
    if (!auth.isCliente && !auth.isProveedor) return;
    await loadTickets();
});
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Ayuda"
            title="Soporte"
            :subtitle="`Habla con el equipo de Busca PE. Rol: ${auth.roleLabel || ''}.`"
        />

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="flex flex-wrap gap-2 mb-6">
            <AppButton variant="primary" @click="showNew = true; closeDetail()">
                Nuevo caso
            </AppButton>
            <select
                v-model="statusFilter"
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm"
                @change="loadTickets"
            >
                <option value="all">Todos los estados</option>
                <option value="abierto">Abierto</option>
                <option value="en_progreso">En progreso</option>
                <option value="esperando_usuario">Esperando tu respuesta</option>
                <option value="pendiente_soporte">Pendiente soporte</option>
                <option value="resuelto">Resuelto</option>
                <option value="cerrado">Cerrado</option>
            </select>
            <AppButton variant="ghost" @click="loadTickets">Actualizar</AppButton>
        </div>

        <div v-if="showNew" class="rounded-2xl border border-slate-200 bg-white p-6 mb-8 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Abrir caso de soporte</h2>
            <form class="space-y-4 max-w-xl" @submit.prevent="createTicket">
                <label class="block text-sm">
                    <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Asunto</span>
                    <input
                        v-model="form.subject"
                        required
                        minlength="5"
                        maxlength="200"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
                        placeholder="Ej. No puedo publicar mi anuncio"
                    />
                </label>
                <label class="block text-sm">
                    <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Categoría</span>
                    <select v-model="form.category" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5">
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Mensaje inicial</span>
                    <textarea
                        v-model="form.body"
                        required
                        minlength="10"
                        rows="4"
                        maxlength="2000"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5 resize-y"
                        placeholder="Describe tu problema con el mayor detalle posible…"
                    ></textarea>
                </label>
                <div class="flex gap-2">
                    <AppButton variant="primary" type="submit" :loading="creating">Enviar caso</AppButton>
                    <AppButton variant="ghost" type="button" @click="showNew = false">Cancelar</AppButton>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Asunto</th>
                        <th class="text-left px-4 py-3">Categoría</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Actualizado</th>
                        <th class="text-right px-4 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">Cargando casos…</td>
                    </tr>
                    <tr v-else-if="!tickets.length">
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                            No tienes casos aún. Abre uno con «Nuevo caso».
                        </td>
                    </tr>
                    <tr
                        v-for="t in tickets"
                        :key="t.id"
                        class="border-t border-slate-100 hover:bg-slate-50/80 cursor-pointer transition"
                        :class="activeId === t.id && detailOpen ? 'bg-chamba-50' : ''"
                        @click="openTicket(t.id)"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-slate-500 whitespace-nowrap">
                            #{{ t.id }}
                            <span
                                v-if="t.unread_for_user"
                                class="ml-1 inline-block w-2 h-2 rounded-full bg-rose-500 align-middle"
                                title="Nueva respuesta"
                            ></span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900 max-w-[260px]">
                            <span class="line-clamp-2">{{ t.subject }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ categoryLabel(t.category) }}</td>
                        <td class="px-4 py-3"><SupportStatusPill :status="t.status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                            {{ fmtDate(t.last_message_at || t.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <AppButton variant="ghost" size="sm" @click.stop="openTicket(t.id)">Ver</AppButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-500 mt-3 text-center">
            Toca una fila o «Ver» para abrir el chat con soporte en un panel centrado.
        </p>

        <SupportTicketModal
            :open="detailOpen"
            :loading="detailLoading"
            :ticket="activeTicket"
            @close="closeDetail"
            @refresh="onTicketRefresh"
            @sent="loadTickets"
        />
    </div>
</template>
