<script setup>
import { onMounted, ref } from 'vue';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import SupportStatusPill from '@/components/support/SupportStatusPill.vue';
import SupportConversation from '@/components/support/SupportConversation.vue';

const tickets = ref([]);
const meta = ref({ open_count: 0 });
const loading = ref(false);
const err = ref('');
const ok = ref('');

const statusFilter = ref('all');
const roleFilter = ref('all');
const q = ref('');
const onlyUnread = ref(false);

const activeTicket = ref(null);
const activeId = ref(null);
const statusSaving = ref(false);

const statusOptions = [
    { value: 'abierto', label: 'Abierto' },
    { value: 'en_progreso', label: 'En progreso' },
    { value: 'esperando_usuario', label: 'Esperando usuario' },
    { value: 'pendiente_soporte', label: 'Pendiente soporte' },
    { value: 'resuelto', label: 'Resuelto' },
    { value: 'cerrado', label: 'Cerrado' },
];

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}

function roleLabel(role) {
    if (role === 'proveedor') return 'Proveedor';
    if (role === 'cliente') return 'Cliente';
    return role || '—';
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
    err.value = '';
    try {
        const r = await api.get(`/admin/support-tickets/${id}`, { auth: true });
        activeTicket.value = r.data || null;
        await loadTickets();
    } catch (e) {
        err.value = e.message;
        activeTicket.value = null;
    }
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
            :subtitle="`${meta.open_count ?? 0} caso(s) abiertos · atiende consultas de clientes y proveedores`"
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
                <option value="proveedor">Proveedores</option>
            </select>
            <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-slate-200 px-3 py-2.5">
                <input v-model="onlyUnread" type="checkbox" @change="loadTickets" />
                Sin leer
            </label>
            <AppButton variant="primary" @click="loadTickets">Buscar</AppButton>
        </div>

        <div class="grid lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm min-w-[480px]">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Caso</th>
                            <th class="text-left px-4 py-3">Usuario</th>
                            <th class="text-left px-4 py-3">Estado</th>
                            <th class="text-left px-4 py-3">Actualizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Cargando…</td>
                        </tr>
                        <tr v-else-if="!tickets.length">
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Sin casos.</td>
                        </tr>
                        <tr
                            v-for="t in tickets"
                            :key="t.id"
                            class="border-t border-slate-100 hover:bg-slate-50/60 cursor-pointer"
                            :class="activeId === t.id ? 'bg-chamba-50' : ''"
                            @click="openTicket(t.id)"
                        >
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-slate-500">#{{ t.id }}</span>
                                <p class="font-semibold text-slate-900 line-clamp-1">{{ t.subject }}</p>
                                <span
                                    v-if="t.unread_for_admin"
                                    class="inline-block mt-1 text-[10px] font-bold uppercase text-rose-600"
                                >Nuevo</span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <p class="font-semibold">{{ t.user?.full_name }}</p>
                                <p class="text-slate-500">{{ t.user?.email }}</p>
                                <p class="text-slate-400">{{ roleLabel(t.user?.role) }}</p>
                            </td>
                            <td class="px-4 py-3"><SupportStatusPill :status="t.status" /></td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ fmtDate(t.last_message_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <section class="lg:col-span-7">
                <div v-if="!activeTicket" class="rounded-2xl border border-dashed border-slate-200 py-16 text-center text-slate-500">
                    Selecciona un caso de la tabla.
                </div>
                <div v-else class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <div class="flex flex-wrap justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-slate-500">#{{ activeTicket.id }}</p>
                                <h2 class="text-xl font-black text-slate-900">{{ activeTicket.subject }}</h2>
                                <p class="text-sm text-slate-600 mt-1">
                                    {{ activeTicket.user?.full_name }} · {{ activeTicket.user?.email }} ·
                                    {{ roleLabel(activeTicket.user?.role) }}
                                </p>
                                <p v-if="activeTicket.user?.phone" class="text-sm text-slate-500">Tel: {{ activeTicket.user.phone }}</p>
                            </div>
                            <SupportStatusPill :status="activeTicket.status" />
                        </div>
                        <div class="flex flex-wrap gap-2 items-center pt-2 border-t border-slate-100">
                            <span class="text-xs font-bold uppercase text-slate-500">Cambiar estado:</span>
                            <AppButton
                                v-for="s in statusOptions"
                                :key="s.value"
                                variant="ghost"
                                size="sm"
                                :disabled="statusSaving || activeTicket.status === s.value"
                                @click="changeStatus(s.value)"
                            >
                                {{ s.label }}
                            </AppButton>
                        </div>
                    </div>
                    <SupportConversation
                        :ticket-id="activeTicket.id"
                        :messages="activeTicket.messages"
                        :can-reply="activeTicket.status !== 'cerrado'"
                        admin-mode
                        @refresh="onTicketRefresh"
                        @sent="loadTickets"
                    />
                </div>
            </section>
        </div>
    </div>
</template>
