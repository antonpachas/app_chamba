<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import { asset } from '@/utils/asset';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');
const flash = ref('');

onMounted(() => {
    if (typeof route.query.flash === 'string') flash.value = route.query.flash;
});

function homeForRole(role) {
    if (role === 'admin') return { name: 'admin-dashboard' };
    if (role === 'proveedor') return { name: 'provider-dashboard' };
    return { name: 'home' };
}

async function submit() {
    error.value = '';
    flash.value = '';
    loading.value = true;
    try {
        const u = await auth.login({ email: email.value.trim(), password: password.value });
        const next = typeof route.query.next === 'string' ? route.query.next : '';
        if (next) {
            router.replace(next);
        } else {
            router.replace(homeForRole(u?.role));
        }
    } catch (e) {
        error.value = e.message || 'No se pudo iniciar sesión.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen auth-shell-bg flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[440px] ui-card overflow-hidden">
            <div class="px-8 pt-10 pb-2">
                <div class="flex items-center justify-center gap-3 mb-8">
                    <img :src="asset('img/chamba-icon.png')" alt="" class="h-14 w-14 rounded-2xl shadow-lg ring-1 ring-slate-200/80" />
                    <span class="text-2xl font-black tracking-tight text-grad-brand">Busca PE</span>
                </div>
                <h1 class="text-center font-display text-3xl font-semibold text-chamba-ink leading-tight mb-2">
                    Bienvenido
                </h1>
                <p class="text-center text-sm text-slate-600 mb-8">
                    Ingresa con tu correo. Tu cuenta define si eres cliente o proveedor.
                </p>
                <AppAlert v-if="flash" type="success" class="mb-4">{{ flash }}</AppAlert>
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
                    <div>
                        <AppInput
                            v-model="password"
                            label="Contraseña"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                            :minlength="8"
                        />
                        <RouterLink
                            :to="{ name: 'forgot' }"
                            class="mt-3 inline-block text-sm font-semibold text-chamba-700 hover:underline"
                        >
                            ¿Olvidaste tu contraseña?
                        </RouterLink>
                    </div>
                    <AppButton variant="primary" type="submit" :loading="loading" block>
                        Iniciar sesión
                    </AppButton>
                </form>
            </div>
            <div class="bg-slate-50/80 border-t border-slate-100 px-8 py-5 text-center space-y-3">
                <RouterLink
                    :to="{ name: 'register' }"
                    class="text-sm font-bold text-chamba-700 hover:underline no-underline"
                >
                    Crear una cuenta gratis
                </RouterLink>
                <div>
                    <RouterLink :to="{ name: 'home' }" class="text-xs font-medium text-slate-500 hover:text-chamba-700 no-underline">
                        ← Volver al inicio
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
