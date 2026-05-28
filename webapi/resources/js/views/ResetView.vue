<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const token = ref('');
const email = ref('');
const password = ref('');
const passwordConfirm = ref('');
const loading = ref(false);
const error = ref('');

onMounted(() => {
    if (typeof route.query.token === 'string') token.value = route.query.token;
    if (typeof route.query.email === 'string') email.value = route.query.email;
});

async function submit() {
    error.value = '';
    if (password.value !== passwordConfirm.value) {
        error.value = 'Las contraseñas no coinciden.';
        return;
    }
    loading.value = true;
    try {
        await auth.resetPassword({
            email: email.value,
            token: token.value,
            password: password.value,
            password_confirmation: passwordConfirm.value,
        });
        router.replace({ name: 'login', query: { flash: 'Contraseña actualizada. Ya puedes iniciar sesión.' } });
    } catch (e) {
        error.value = e.message || 'No se pudo restablecer la contraseña.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen auth-shell-bg flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[420px] ui-card overflow-hidden">
            <div class="px-8 pt-10 pb-6">
                <h1 class="text-center font-['Playfair_Display',Georgia,serif] text-[1.85rem] font-semibold text-slate-900 mb-2">
                    Nueva contraseña
                </h1>
                <p class="text-sm text-slate-600 text-center mb-6">Define una contraseña segura para tu cuenta.</p>
                <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>
                <form class="space-y-5" @submit.prevent="submit">
                    <AppInput v-model="email" label="Correo electrónico" type="email" required />
                    <AppInput v-model="password" label="Nueva contraseña" type="password" :minlength="8" required autocomplete="new-password" />
                    <AppInput v-model="passwordConfirm" label="Confirmar contraseña" type="password" :minlength="8" required autocomplete="new-password" />
                    <AppButton variant="primary" type="submit" :loading="loading" block>
                        Guardar contraseña
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
