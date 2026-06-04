<script setup>
import AppButton from '@/components/ui/AppButton.vue';
import SupportStatusPill from '@/components/support/SupportStatusPill.vue';
import SupportConversation from '@/components/support/SupportConversation.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    ticket: { type: Object, default: null },
    adminMode: { type: Boolean, default: false },
    statusOptions: { type: Array, default: () => [] },
    statusSaving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'change-status', 'refresh', 'sent']);

const categoryLabels = {
    cuenta: 'Mi cuenta',
    anuncios: 'Anuncios / publicación',
    pagos: 'Pagos / membresía',
    tecnico: 'Problema técnico',
    otro: 'Otro',
};

function roleLabel(role) {
    if (role === 'proveedor') return 'Negocio';
    if (role === 'cliente') return 'Cliente';
    return role || '—';
}

function categoryLabel(value) {
    return categoryLabels[value] || value || '—';
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}

function close() {
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="support-ticket-modal-title"
            @click.self="close"
        >
            <div
                class="bg-white w-full sm:max-w-2xl sm:rounded-2xl shadow-xl flex flex-col max-h-[92vh] sm:max-h-[90vh] rounded-t-2xl sm:rounded-2xl"
            >
                <header class="flex items-start justify-between gap-3 px-5 py-4 border-b border-slate-200 shrink-0">
                    <div class="min-w-0 flex-1">
                        <p v-if="ticket" class="text-xs font-bold uppercase text-slate-500">
                            Caso #{{ ticket.id }}
                        </p>
                        <h2
                            id="support-ticket-modal-title"
                            class="text-lg sm:text-xl font-black text-slate-900 truncate"
                        >
                            {{ loading ? 'Cargando…' : ticket?.subject || 'Detalle del caso' }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50"
                        aria-label="Cerrar"
                        @click="close"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    <p v-if="loading" class="text-sm text-slate-500 py-12 text-center">Cargando conversación…</p>

                    <template v-else-if="ticket">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-1 text-sm text-slate-600">
                                <p v-if="adminMode && ticket.user">
                                    <span class="font-bold text-slate-900">{{ ticket.user.full_name }}</span>
                                    · {{ ticket.user.email }} · {{ roleLabel(ticket.user.role) }}
                                </p>
                                <p v-if="adminMode && ticket.user?.phone">Tel: {{ ticket.user.phone }}</p>
                                <p v-if="!adminMode">
                                    Categoría:
                                    <span class="font-semibold text-slate-800">{{ categoryLabel(ticket.category) }}</span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    Creado {{ fmtDate(ticket.created_at) }}
                                    <span v-if="ticket.last_message_at">
                                        · Último mensaje {{ fmtDate(ticket.last_message_at) }}
                                    </span>
                                </p>
                            </div>
                            <SupportStatusPill :status="ticket.status" />
                        </div>

                        <div
                            v-if="adminMode && statusOptions.length"
                            class="rounded-xl border border-slate-100 bg-slate-50/80 p-3 space-y-2"
                        >
                            <p class="text-xs font-bold uppercase text-slate-500">Cambiar estado</p>
                            <div class="flex flex-wrap gap-2">
                                <AppButton
                                    v-for="s in statusOptions"
                                    :key="s.value"
                                    variant="ghost"
                                    size="sm"
                                    :disabled="statusSaving || ticket.status === s.value"
                                    @click="emit('change-status', s.value)"
                                >
                                    {{ s.label }}
                                </AppButton>
                            </div>
                        </div>

                        <SupportConversation
                            :ticket-id="ticket.id"
                            :messages="ticket.messages"
                            :can-reply="adminMode ? ticket.status !== 'cerrado' : ticket.can_reply"
                            :admin-mode="adminMode"
                            chat-max-class="max-h-[min(42vh,380px)]"
                            @refresh="(t) => emit('refresh', t)"
                            @sent="emit('sent')"
                        />
                    </template>

                    <p v-else class="text-sm text-slate-500 py-8 text-center">No se pudo cargar el caso.</p>
                </div>

                <footer class="px-5 py-3 border-t border-slate-100 shrink-0 sm:hidden">
                    <AppButton variant="ghost" class="w-full" @click="close">Cerrar</AppButton>
                </footer>
            </div>
        </div>
    </Teleport>
</template>
