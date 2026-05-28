<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import SupportStatusPill from '@/components/support/SupportStatusPill.vue';
import SupportConversation from '@/components/support/SupportConversation.vue';

const auth = useAuthStore();

const tickets = ref([]);
const meta = ref({});
const loading = ref(false);
const err = ref('');
const ok = ref('');

const statusFilter = ref('all');
const activeId = ref(null);
const activeTicket = ref(null);

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

const activeList = computed(() => tickets.value || []);

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
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
            activeId.value = null;
            activeTicket.value = null;
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
    showNew.value = false;
    err.value = '';
    try {
        const r = await api.get(`/support-tickets/${id}`, { auth: true });
        activeTicket.value = r.data || null;
        await loadTickets();
    } catch (e) {
        err.value = e.message;
        activeTicket.value = null;
    }
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
            <AppButton variant="primary" @click="showNew = true; activeId = null; activeTicket = null">
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

        <div class="grid lg:grid-cols-12 gap-6 items-start">
            <aside class="lg:col-span-4 space-y-2">
                <p v-if="loading" class="text-slate-500 text-sm py-8 text-center">Cargando casos…</p>
                <p v-else-if="!activeList.length" class="text-slate-500 text-sm py-8 text-center rounded-xl border border-dashed border-slate-200">
                    No tienes casos aún. Abre uno nuevo.
                </p>
                <button
                    v-for="t in activeList"
                    :key="t.id"
                    type="button"
                    class="w-full text-left rounded-xl border px-4 py-3 transition"
                    :class="activeId === t.id ? 'border-[#003874] bg-chamba-50' : 'border-slate-200 bg-white hover:border-slate-300'"
                    @click="openTicket(t.id)"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-bold text-sm text-slate-900 line-clamp-2">#{{ t.id }} · {{ t.subject }}</span>
                        <span
                            v-if="t.unread_for_user"
                            class="shrink-0 w-2 h-2 rounded-full bg-rose-500 mt-1"
                            title="Nueva respuesta"
                        ></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <SupportStatusPill :status="t.status" />
                        <span class="text-[10px] text-slate-500">{{ fmtDate(t.last_message_at || t.created_at) }}</span>
                    </div>
                </button>
            </aside>

            <section class="lg:col-span-8">
                <div v-if="!activeTicket" class="rounded-2xl border border-dashed border-slate-200 py-16 text-center text-slate-500">
                    Selecciona un caso o crea uno nuevo.
                </div>
                <div v-else class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-slate-500">Caso #{{ activeTicket.id }}</p>
                                <h2 class="text-xl font-black text-slate-900 mt-1">{{ activeTicket.subject }}</h2>
                                <p class="text-sm text-slate-600 mt-1 capitalize">{{ activeTicket.category }}</p>
                            </div>
                            <SupportStatusPill :status="activeTicket.status" />
                        </div>
                    </div>
                    <SupportConversation
                        :ticket-id="activeTicket.id"
                        :messages="activeTicket.messages"
                        :can-reply="activeTicket.can_reply"
                        @refresh="onTicketRefresh"
                        @sent="loadTickets"
                    />
                </div>
            </section>
        </div>
    </div>
</template>
