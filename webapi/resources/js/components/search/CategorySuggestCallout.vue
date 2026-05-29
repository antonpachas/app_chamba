<script setup>
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useLoginModalStore } from '@/stores/loginModal';

defineProps({
    /** En index: una sola línea de texto en lugar del banner grande */
    inline: { type: Boolean, default: false },
});

const emit = defineEmits(['open']);

const route = useRoute();
const auth = useAuthStore();
const loginModal = useLoginModalStore();

function goLogin() {
    loginModal.showLogin(route.fullPath);
}

function onAction() {
    if (auth.isAuthenticated) {
        emit('open');
        return;
    }
    goLogin();
}
</script>

<template>
    <p v-if="inline && auth.isAuthenticated" class="text-sm text-slate-500">
        ¿No encuentras tu rubro?
        <button
            type="button"
            class="text-[#003874] font-medium hover:underline bg-transparent border-0 p-0 cursor-pointer"
            @click="onAction"
        >
            Sugerir una categoría
        </button>
    </p>
    <p v-else-if="inline && !auth.isAuthenticated" class="text-sm text-slate-500">
        ¿No encuentras tu rubro?
        <button
            type="button"
            class="text-[#003874] font-medium hover:underline bg-transparent border-0 p-0 cursor-pointer"
            @click="onAction"
        >
            Inicia sesión para sugerirla
        </button>
    </p>

    <div
        v-else
        class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
        role="complementary"
        aria-label="Sugerir categoría"
    >
        <p class="text-sm text-slate-600">
            ¿Falta una categoría en el directorio?
            <span v-if="!auth.isAuthenticated" class="text-slate-500">Necesitas iniciar sesión.</span>
        </p>
        <button
            type="button"
            class="shrink-0 text-sm font-medium text-[#003874] hover:underline bg-transparent border-0 p-0 cursor-pointer text-left sm:text-right"
            @click="onAction"
        >
            {{ auth.isAuthenticated ? 'Enviar sugerencia →' : 'Iniciar sesión →' }}
        </button>
    </div>
</template>
