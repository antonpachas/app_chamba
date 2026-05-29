<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import SupportStatusPill from '@/components/support/SupportStatusPill.vue';
import SupportTicketModal from '@/components/support/SupportTicketModal.vue';

const tickets = ref([]);
const meta = ref({ open_count: 0 });
const loading = ref(false);
const err = ref('');
const ok = ref('');

const statusFilter = ref('all');
const roleFilter = ref('all');
const q = ref('');
const onlyUnread = ref(false);

const activeId = ref(null);
const activeTicket = ref(null);
const detailOpen = ref(false);
const detailLoading = ref(false);
const statusSaving = ref(false);

const statusOptions = [
    { value: 'abierto', label: 'Abierto' },
    { value: 'en_progreso', label: 'En progreso' },
    { value: 'esperando_usuario', label: 'Esperando usuario' },
    { value: 'pendiente_soporte', label: 'Pendiente soporte' },
    { value: 'resuelto', label: 'Resuelto' },
    { value: 'cerrado', label: 'Cerrado' },
];

const categoryLabels = {
    cuenta: 'Cuenta',
    anuncios: 'Anuncios',
    pagos: 'Pagos',
    tecnico: 'Técnico',
    otro: 'Otro',
};

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}

function roleLabel(role) {
    if (role === 'proveedor') return 'Negocio';
    if (role === 'cliente') return 'Cliente';
    return role || '—';
}

function categoryLabel(value) {
    return categoryLabels[value] || value || '—';
}

async function loadTickets() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/support-tickets', {
            auth: true,
            params: {
                status: statusFilter.value,
                role: roleFilter.value,
                q: q.value || undefined,
                only_unread: onlyUnread.value ? 1 : undefined,
                per_page: 50,
            },
        });
        tickets.value = r.data || [];
        meta.value = r.meta || {};
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
    activeTicket.value = null;
    err.value = '';
    try {
        const r = await api.get(`/admin/support-tickets/${id}`, { auth: true });
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
}

async function changeStatus(status) {
    if (!activeTicket.value?.id) return;
    statusSaving.value = true;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.patch(
            `/admin/support-tickets/${activeTicket.value.id}/status`,
            { status },
            { auth: true },
        );
        activeTicket.value = r.data || activeTicket.value;
        ok.value = 'Estado actualizado.';
        await loadTickets();
    } catch (e) {
        err.value = e.message;
    } finally {
        statusSaving.value = false;
    }
}

onMounted(loadTickets);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8 pb-24">
        <PageHeader
            eyebrow="Admin"
            title="Soporte"
            :subtitle="`${meta.open_count ?? 0} caso(s) abiertos · atiende consultas de clientes y negocios`"
        />

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="flex flex-wrap gap-2 mb-6">
            <input
                v-model="q"
                type="search"
                placeholder="Buscar #, asunto, nombre, email…"
                class="flex-1 min-w-[200px] rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                @keyup.enter="loadTickets"
            />
            <select v-model="statusFilter" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="loadTickets">
                <option value="all">Estado: todos</option>
                <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
            <select v-model="roleFilter" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="loadTickets">
                <option value="all">Rol: todos</option>
                <option value="cliente">Clientes</option>
                <option value="proveedor">Negocios</option>
            </select>
            <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-slate-200 px-3 py-2.5">
                <input v-model="onlyUnread" type="checkbox" @change="loadTickets" />
                Sin leer
            </label>
            <AppButton variant="primary" @click="loadTickets">Buscar</AppButton>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm min-w-[720px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">#</th>
                        <th class="text-left px-4 py-3">Asunto</th>
                        <th class="text-left px-4 py-3">Usuario</th>
                        <th class="text-left px-4 py-3">Rol</th>
                        <th class="text-left px-4 py-3">Categoría</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Actualizado</th>
                        <th class="text-right px-4 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">Cargando casos…</td>
                    </tr>
                    <tr v-else-if="!tickets.length">
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">Sin casos con estos filtros.</td>
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
                                v-if="t.unread_for_admin"
                                class="ml-1 inline-block w-2 h-2 rounded-full bg-rose-500 align-middle"
                                title="Sin leer"
                            ></span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900 max-w-[220px]">
                            <span class="line-clamp-2">{{ t.subject }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ t.user?.full_name }}</p>
                            <p class="text-xs text-slate-500 truncate max-w-[180px]">{{ t.user?.email }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ roleLabel(t.user?.role) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ categoryLabel(t.category) }}</td>
                        <td class="px-4 py-3"><SupportStatusPill :status="t.status" /></td>
                        <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ fmtDate(t.last_message_at) }}</td>
                        <td class="px-4 py-3 text-right">
                            <AppButton variant="ghost" size="sm" @click.stop="openTicket(t.id)">Ver</AppButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-500 mt-3 text-center">
            Toca una fila o «Ver» para abrir el caso en un panel centrado con el chat.
        </p>

        <SupportTicketModal
            :open="detailOpen"
            :loading="detailLoading"
            :ticket="activeTicket"
            admin-mode
            :status-options="statusOptions"
            :status-saving="statusSaving"
            @close="closeDetail"
            @change-status="changeStatus"
            @refresh="onTicketRefresh"
            @sent="loadTickets"
        />
    </div>
</template>
