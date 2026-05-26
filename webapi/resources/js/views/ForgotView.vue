<script setup>
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const loading = ref(false);
const error = ref('');

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.forgotPassword(email.value.trim());
        router.replace({
            name: 'login',
            query: {
                flash: 'Si ese correo existe en Chamba, recibirás un enlace para crear una nueva contraseña.',
            },
        });
    } catch (e) {
        error.value = e.message || 'No se pudo enviar el enlace.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[420px] rounded-xl bg-white shadow-lg border border-slate-200/60 overflow-hidden">
            <div class="px-8 pt-10 pb-6">
                <h1 class="text-center font-['Playfair_Display',Georgia,serif] text-[1.85rem] font-semibold text-slate-900 mb-2">
                    Recuperar contraseña
                </h1>
                <p class="text-sm text-slate-600 text-center mb-6">
                    Te enviaremos un enlace al correo de tu cuenta para crear una nueva contraseña.
                </p>
                <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>
                <form class="space-y-5" @submit.prevent="submit">
                    <AppInput
                        v-model="email"
                        label="Correo electrónico"
                        type="email"
                        placeholder="tu@correo.com"
                        autocomplete="email"
                        required
                    />
                    <AppButton variant="primary" type="submit" :loading="loading" block>
                        Enviar enlace
                    </AppButton>
                </form>
            </div>
            <div class="bg-slate-50 border-t border-slate-200 px-8 py-4 text-center">
                <RouterLink :to="{ name: 'login' }" class="text-sm font-medium text-slate-600 hover:text-[#003874] hover:underline no-underline">
                    Volver al inicio de sesión
                </RouterLink>
            </div>
        </div>
    </div>
</template>
