<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const emit = defineEmits(['open']);

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

function goLogin() {
    router.push({
        name: 'login',
        query: { next: route.fullPath, cuenta: 'cliente' },
    });
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
    <div
        class="mt-5 rounded-2xl border-2 border-[#003874]/20 bg-gradient-to-br from-sky-50 via-white to-orange-50/40 p-5 md:p-6 shadow-sm"
        role="complementary"
        aria-label="Sugerir una categoría nueva"
    >
        <div class="flex flex-col md:flex-row md:items-center gap-4 md:gap-6">
            <div
                class="mx-auto md:mx-0 w-14 h-14 rounded-2xl bg-[#003874] text-white flex items-center justify-center shrink-0 shadow-lg shadow-[#003874]/25"
                aria-hidden="true"
            >
                <span class="material-symbols-outlined text-[32px]">category</span>
            </div>
            <div class="flex-1 text-center md:text-left min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#0ea5e9] mb-1">
                    ¿No encuentras lo que buscas?
                </p>
                <h3 class="text-lg md:text-xl font-bold text-[#0b1c30] leading-snug">
                    Sugiere una categoría para Busca PE
                </h3>
                <p class="text-sm text-slate-600 mt-1.5 max-w-xl">
                    Si tu rubro aún no está en la lista, cuéntanos el nombre y lo revisamos.
                    <template v-if="!auth.isAuthenticated">
                        <strong class="text-[#003874]"> Debes iniciar sesión</strong> para enviar una sugerencia.
                    </template>
                </p>
            </div>
            <button
                type="button"
                class="w-full md:w-auto shrink-0 inline-flex items-center justify-center gap-2 btn-grad-warm px-6 py-3.5 rounded-full font-bold text-base shadow-md shadow-orange-500/20 hover:shadow-lg transition active:scale-[0.98]"
                @click="onAction"
            >
                <span class="material-symbols-outlined text-[22px]">{{ auth.isAuthenticated ? 'edit_square' : 'login' }}</span>
                {{ auth.isAuthenticated ? 'Sugerir categoría' : 'Iniciar sesión para sugerir' }}
            </button>
        </div>
    </div>
</template>
