<script setup>
import { ref } from 'vue';
import StarRatingInput from '@/components/common/StarRatingInput.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const props = defineProps({
    serviceRequestId: { type: Number, default: null },
    providerServiceId: { type: Number, default: null },
    providerName: { type: String, default: '' },
    embedded: { type: Boolean, default: false },
});

const emit = defineEmits(['submitted']);

const rating = ref(0);
const comment = ref('');
const saving = ref(false);
const err = ref('');
const ok = ref('');

async function submit() {
    if (rating.value < 1) {
        err.value = 'Elige una calificación de 1 a 5 estrellas.';
        return;
    }
    saving.value = true;
    err.value = '';
    ok.value = '';
    try {
        const { api } = await import('@/services/api');
        const body = {
            rating: rating.value,
            comment: comment.value.trim() || null,
        };
        if (props.serviceRequestId) {
            body.service_request_id = props.serviceRequestId;
        } else if (props.providerServiceId) {
            body.provider_service_id = props.providerServiceId;
        } else {
            throw new Error('Falta el anuncio o la solicitud para valorar.');
        }
        await api.post('/client/reviews', body, { auth: true });
        ok.value = '¡Gracias! Tu valoración fue registrada.';
        emit('submitted');
    } catch (e) {
        err.value = e.message || 'No se pudo enviar la valoración.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div
        :class="
            embedded
                ? 'listing-detail__review-form'
                : 'rounded-xl border border-amber-200 bg-amber-50/50 p-4 space-y-3'
        "
    >
        <p class="text-sm font-bold text-[#0b1c30]">
            Valorar{{ providerName ? ` a ${providerName}` : ' este negocio' }}
        </p>
        <p class="text-xs text-slate-600">Tu opinión ayuda a otros usuarios de Busca PE.</p>
        <StarRatingInput v-model="rating" size="lg" />
        <label class="block">
            <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Comentario (opcional)</span>
            <textarea
                v-model="comment"
                rows="2"
                maxlength="500"
                placeholder="Cuéntanos tu experiencia…"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-[#003874]"
            ></textarea>
        </label>
        <AppAlert v-if="err" type="error">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success">{{ ok }}</AppAlert>
        <AppButton v-if="!ok" variant="primary" size="sm" :loading="saving" :disabled="rating < 1" @click="submit">
            Enviar valoración
        </AppButton>
    </div>
</template>
