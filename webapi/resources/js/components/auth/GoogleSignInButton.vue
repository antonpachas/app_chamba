<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    role: { type: String, default: 'cliente' },
    label: { type: String, default: 'Continuar con Google' },
});

const emit = defineEmits(['success', 'error']);

const auth = useAuthStore();
const loading = ref(false);

function openGooglePopup() {
    if (loading.value) return;
    loading.value = true;

    const base = window.CHAMBA_LANDING_URL || window.location.origin;
    const url  = `${base}/auth/google/redirect?role=${encodeURIComponent(props.role)}`;
    const opts = 'width=520,height=640,scrollbars=yes,resizable=yes,toolbar=no,menubar=no';
    const popup = window.open(url, 'busca_pe_google', opts);

    if (!popup) {
        loading.value = false;
        emit('error', 'El navegador bloqueó la ventana emergente. Permite las popups para este sitio.');
        return;
    }

    function onMessage(event) {
        if (event.origin !== window.location.origin) return;
        const data = event.data;
        if (!data || data.type !== 'google_auth_success' && data.type !== 'google_auth_error') return;

        window.removeEventListener('message', onMessage);
        loading.value = false;

        if (data.type === 'google_auth_error') {
            emit('error', data.message || 'Error al conectar con Google.');
            return;
        }

        auth.loginWithGoogle(data.token, data.user);
        emit('success', data.user);
    }

    window.addEventListener('message', onMessage);

    // Si el usuario cierra el popup manualmente
    const timer = setInterval(() => {
        if (popup.closed) {
            clearInterval(timer);
            window.removeEventListener('message', onMessage);
            loading.value = false;
        }
    }, 500);
}
</script>

<template>
    <button
        type="button"
        :disabled="loading"
        class="w-full flex items-center justify-center gap-3 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-medium text-sm hover:bg-slate-50 hover:border-slate-300 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#003874]/40 disabled:opacity-60 disabled:cursor-not-allowed"
        @click="openGooglePopup"
    >
        <span v-if="loading" class="w-5 h-5 border-2 border-slate-300 border-t-[#003874] rounded-full animate-spin shrink-0" />
        <svg v-else width="20" height="20" viewBox="0 0 48 48" class="shrink-0" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.14 0 5.95 1.08 8.17 2.85l6.08-6.08C34.46 3.14 29.52 1 24 1 14.82 1 6.99 6.4 3.48 14.19l7.1 5.52C12.31 13.64 17.71 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.52 24.5c0-1.64-.15-3.22-.42-4.75H24v9h12.68c-.55 2.97-2.22 5.49-4.72 7.17l7.34 5.7C43.46 37.3 46.52 31.4 46.52 24.5z"/>
            <path fill="#FBBC05" d="M10.58 28.29A14.57 14.57 0 0 1 9.5 24c0-1.49.26-2.93.72-4.29l-7.1-5.52A23.96 23.96 0 0 0 0 24c0 3.87.93 7.52 2.57 10.74l8.01-6.45z"/>
            <path fill="#34A853" d="M24 47c5.52 0 10.16-1.83 13.54-4.96l-7.34-5.7c-1.83 1.23-4.17 1.96-6.2 1.96-6.29 0-11.62-4.25-13.52-9.98l-8.01 6.45C6.99 41.6 14.82 47 24 47z"/>
        </svg>
        {{ loading ? 'Conectando…' : label }}
    </button>
</template>
