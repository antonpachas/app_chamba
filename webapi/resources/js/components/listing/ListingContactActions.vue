<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
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

const auth = useAuthStore();
const route = useRoute();

const showLoginCta = computed(() => {
    if (auth.isAuthenticated) {
        return false;
    }
    return !!props.service?.contact_requires_login || !!props.service?.guest_preview;
});

const hasContact = computed(() => !showLoginCta.value && hasListingContact(props.service));

const waUrl = computed(() => buildListingWhatsAppUrl(props.service));
const telUrl = computed(() => buildTelUrl(props.service));
const phoneLabel = computed(() => listingContactPhoneDisplay(props.service));
</script>

<template>
    <div v-if="showLoginCta" :class="compact ? 'space-y-2' : 'space-y-3'" @click.stop>
        <p class="text-slate-600 leading-snug" :class="compact ? 'text-xs' : 'text-sm'">
            <span class="material-symbols-outlined text-[#003874] align-middle text-base mr-1">lock</span>
            Inicia sesión para ver el teléfono y escribir por WhatsApp con un mensaje sobre este anuncio en Busca PE.
        </p>
        <div class="flex flex-wrap gap-2">
            <RouterLink
                :to="{ name: 'login', query: { next: route.fullPath } }"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#003874] hover:bg-[#08458b] text-white font-bold no-underline transition-colors"
                :class="compact ? 'px-3 py-2 text-xs flex-1' : 'px-5 py-3 text-sm flex-1'"
            >
                Iniciar sesión
            </RouterLink>
            <RouterLink
                :to="{ name: 'register', query: { next: route.fullPath } }"
                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-[#003874]/30 text-[#003874] font-bold no-underline hover:bg-slate-50"
                :class="compact ? 'px-3 py-2 text-xs' : 'px-5 py-3 text-sm'"
            >
                Crear cuenta
            </RouterLink>
        </div>
    </div>
    <div v-else-if="hasContact" :class="compact ? 'space-y-2' : 'space-y-3'">
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
