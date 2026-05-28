<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const props = defineProps({
    requestId: { type: Number, required: true },
    closed: { type: Boolean, default: false },
});

const emit = defineEmits(['sent']);

const auth = useAuthStore();
const thread = ref([]);
const canPost = ref(true);
const loading = ref(true);
const sending = ref(false);
const err = ref('');
const draft = ref('');
const listEl = ref(null);

const myRole = computed(() => auth.user?.role);

function isMine(msg) {
    return msg.author_role === myRole.value;
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

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get(`/service-requests/${props.requestId}/messages`, { auth: true });
        thread.value = r.data || [];
        canPost.value = r.can_post !== false && !props.closed;
    } catch (e) {
        err.value = e.message || 'No se pudo cargar la conversación.';
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
        const r = await api.post(
            `/service-requests/${props.requestId}/messages`,
            { body },
            { auth: true },
        );
        thread.value = r.thread || thread.value;
        canPost.value = r.can_post !== false;
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

onMounted(load);
watch(() => props.requestId, load);
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-slate-50/80 overflow-hidden">
        <div class="px-4 py-2 border-b border-slate-200 bg-white">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Conversación en la solicitud</p>
        </div>

        <AppAlert v-if="err" type="error" class="m-3">{{ err }}</AppAlert>

        <div
            ref="listEl"
            class="max-h-64 overflow-y-auto px-3 py-3 space-y-3"
        >
            <p v-if="loading" class="text-sm text-slate-500 text-center py-4">Cargando mensajes…</p>
            <p v-else-if="!thread.length" class="text-sm text-slate-500 text-center py-4">
                Aún no hay mensajes. Escribe el primero abajo.
            </p>
            <div
                v-for="(msg, idx) in thread"
                :key="msg.id ?? `initial-${idx}`"
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
                        {{ isMine(msg) ? 'Tú' : msg.author_name }}
                        <span v-if="msg.is_initial" class="normal-case font-medium"> · mensaje inicial</span>
                    </p>
                    <p class="text-sm whitespace-pre-wrap break-words">{{ msg.body }}</p>
                    <p class="text-[10px] mt-1 opacity-70">{{ formatTime(msg.created_at) }}</p>
                </div>
            </div>
        </div>

        <form v-if="canPost" class="border-t border-slate-200 bg-white p-3 space-y-2" @submit.prevent="send">
            <textarea
                v-model="draft"
                rows="2"
                maxlength="2000"
                placeholder="Escribe tu mensaje…"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15 resize-none"
            ></textarea>
            <div class="flex justify-end">
                <AppButton variant="primary" size="sm" type="submit" :loading="sending" :disabled="!draft.trim()">
                    Enviar
                </AppButton>
            </div>
        </form>
        <p v-else class="text-xs text-slate-500 px-4 py-3 border-t border-slate-200 bg-white">
            Esta solicitud está cerrada. Solo puedes leer el historial.
        </p>
    </div>
</template>
