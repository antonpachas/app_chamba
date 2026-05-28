<script setup>
import { computed } from 'vue';
import {
    buildListingWhatsAppUrl,
    buildTelUrl,
    listingContactPhoneDisplay,
    hasListingContact,
} from '@/utils/whatsapp';

const props = defineProps({
    service: { type: Object, required: true },
    compact: { type: Boolean, default: false },
    showPhone: { type: Boolean, default: true },
});

const waUrl = computed(() => buildListingWhatsAppUrl(props.service));
const telUrl = computed(() => buildTelUrl(props.service));
const phoneLabel = computed(() => listingContactPhoneDisplay(props.service));
const hasContact = computed(() => hasListingContact(props.service));
</script>

<template>
    <div v-if="hasContact" :class="compact ? 'space-y-2' : 'space-y-3'">
        <p
            v-if="showPhone && phoneLabel"
            class="flex items-center gap-2 text-slate-700"
            :class="compact ? 'text-xs' : 'text-sm'"
        >
            <span class="material-symbols-outlined text-[#003874]" :class="compact ? 'text-base' : 'text-lg'">call</span>
            <span class="font-semibold">{{ phoneLabel }}</span>
        </p>
        <div class="flex flex-wrap gap-2" @click.stop>
            <a
                v-if="waUrl"
                :href="waUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md no-underline transition-colors"
                :class="compact ? 'px-3 py-2 text-xs flex-1 min-w-[120px]' : 'px-5 py-3 text-sm flex-1'"
            >
                <span class="material-symbols-outlined text-[18px]">chat</span>
                WhatsApp
            </a>
            <a
                v-if="telUrl"
                :href="telUrl"
                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 hover:border-[#003874]/40 font-bold text-slate-800 no-underline transition-colors"
                :class="compact ? 'px-3 py-2 text-xs' : 'px-5 py-3 text-sm'"
            >
                <span class="material-symbols-outlined text-[18px]">call</span>
                Llamar
            </a>
        </div>
        <p v-if="!compact" class="text-[11px] text-slate-500 leading-snug">
            Al usar WhatsApp se abrirá un mensaje indicando que viste este anuncio en Busca PE.
        </p>
    </div>
</template>
