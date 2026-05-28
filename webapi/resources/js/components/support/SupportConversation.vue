<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const props = defineProps({
    ticketId: { type: Number, required: true },
    messages: { type: Array, default: () => [] },
    canReply: { type: Boolean, default: true },
    adminMode: { type: Boolean, default: false },
    chatMaxClass: { type: String, default: 'max-h-80' },
});

const emit = defineEmits(['sent', 'refresh']);

const auth = useAuthStore();
const thread = ref([]);
const canPost = ref(true);
const loading = ref(false);
const sending = ref(false);
const err = ref('');
const draft = ref('');
const listEl = ref(null);

const apiBase = computed(() => (props.adminMode ? '/admin/support-tickets' : '/support-tickets'));

function isMine(msg) {
    if (props.adminMode) {
        return !!msg.is_staff;
    }
    return !msg.is_staff;
}

function formatTime(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString('es-PE', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
}

function syncThread(msgs) {
    thread.value = Array.isArray(msgs) ? [...msgs] : [];
    canPost.value = props.canReply;
}

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get(`${apiBase.value}/${props.ticketId}`, { auth: true });
        syncThread(r.data?.messages || []);
        canPost.value = r.data?.can_reply !== false && props.canReply;
        emit('refresh', r.data);
    } catch (e) {
        err.value = e.message || 'No se pudo cargar el chat.';
        thread.value = [];
    } finally {
        loading.value = false;
        await nextTick();
        scrollToBottom();
    }
}

function scrollToBottom() {
    if (listEl.value) {
        listEl.value.scrollTop = listEl.value.scrollHeight;
    }
}

async function send() {
    const body = draft.value.trim();
    if (!body || !canPost.value) return;
    sending.value = true;
    err.value = '';
    try {
        const r = await api.post(`${apiBase.value}/${props.ticketId}/messages`, { body }, { auth: true });
        if (r.ticket?.messages) {
            syncThread(r.ticket.messages);
            canPost.value = r.ticket.can_reply !== false;
            emit('refresh', r.ticket);
        } else {
            await load();
        }
        draft.value = '';
        emit('sent');
        await nextTick();
        scrollToBottom();
    } catch (e) {
        err.value = e.message || 'No se pudo enviar el mensaje.';
    } finally {
        sending.value = false;
    }
}

onMounted(() => {
    if (props.messages?.length) {
        syncThread(props.messages);
        nextTick(scrollToBottom);
    } else {
        load();
    }
});

watch(() => props.ticketId, load);
watch(
    () => props.messages,
    (msgs) => {
        if (msgs?.length) syncThread(msgs);
    },
    { deep: true },
);
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 overflow-hidden flex flex-col min-h-[240px]">
        <div class="px-4 py-2 border-b border-slate-200 bg-white shrink-0">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Chat con soporte</p>
        </div>

        <AppAlert v-if="err" type="error" class="m-3 shrink-0">{{ err }}</AppAlert>

        <div
            ref="listEl"
            class="flex-1 overflow-y-auto px-3 py-3 space-y-3 min-h-[140px]"
            :class="chatMaxClass"
        >
            <p v-if="loading" class="text-sm text-slate-500 text-center py-4">Cargando mensajes…</p>
            <p v-else-if="!thread.length" class="text-sm text-slate-500 text-center py-4">
                Sin mensajes aún.
            </p>
            <div
                v-for="msg in thread"
                :key="msg.id"
                class="flex"
                :class="isMine(msg) ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[85%] rounded-2xl px-4 py-2.5 shadow-sm"
                    :class="
                        isMine(msg)
                            ? 'bg-[#003874] text-white rounded-br-md'
                            : 'bg-white border border-slate-200 text-slate-800 rounded-bl-md'
                    "
                >
                    <p class="text-[10px] font-bold uppercase tracking-wide opacity-80 mb-0.5">
                        {{ isMine(msg) ? (adminMode ? 'Tú (soporte)' : 'Tú') : msg.author_name }}
                    </p>
                    <p class="text-sm whitespace-pre-wrap break-words">{{ msg.body }}</p>
                    <p class="text-[10px] mt-1 opacity-70">{{ formatTime(msg.created_at) }}</p>
                </div>
            </div>
        </div>

        <form v-if="canPost" class="border-t border-slate-200 bg-white p-3 space-y-2 shrink-0" @submit.prevent="send">
            <textarea
                v-model="draft"
                rows="2"
                maxlength="2000"
                :placeholder="adminMode ? 'Respuesta de soporte…' : 'Escribe tu mensaje…'"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15 resize-none"
            ></textarea>
            <div class="flex justify-end">
                <AppButton variant="primary" size="sm" type="submit" :loading="sending" :disabled="!draft.trim()">
                    Enviar
                </AppButton>
            </div>
        </form>
        <p v-else class="text-xs text-slate-500 px-4 py-3 border-t border-slate-200 bg-white shrink-0">
            Este caso está cerrado. Solo puedes leer el historial.
        </p>
    </div>
</template>
