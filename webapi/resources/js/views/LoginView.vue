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
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[420px] rounded-xl bg-white shadow-lg border border-slate-200/60 overflow-hidden">
            <div class="px-8 pt-10 pb-2">
                <div class="flex items-center justify-center gap-3 mb-8">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl overflow-hidden shadow-lg shadow-[#003874]/25 ring-1 ring-slate-200">
                        <img :src="asset('img/chamba-icon.png')" alt="" class="h-14 w-14" />
                    </div>
                    <span class="text-2xl font-black tracking-tight text-grad-brand select-none">Busca PE</span>
                </div>
                <h1
                    class="text-center font-['Playfair_Display',Georgia,serif] text-[1.85rem] font-semibold text-slate-900 leading-tight tracking-tight mb-2"
                >
                    Bienvenido
                </h1>
                <p class="text-center text-sm text-slate-600 mb-8">
                    Ingresa con tu correo y contraseña. Tu cuenta sabe si eres cliente o proveedor.
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
                            placeholder="Contraseña"
                            autocomplete="current-password"
                            required
                            :minlength="8"
                        />
                        <RouterLink
                            :to="{ name: 'forgot' }"
                            class="mt-3 inline-block text-sm font-medium text-[#003874]/90 hover:text-[#003874] hover:underline"
                        >
                            ¿Olvidaste tu contraseña?
                        </RouterLink>
                    </div>
                    <AppButton variant="primary" type="submit" :loading="loading" block>
                        Iniciar sesión
                    </AppButton>
                </form>
            </div>
            <div class="bg-slate-50 border-t border-slate-200 px-8 py-5 text-center space-y-3">
                <RouterLink
                    :to="{ name: 'register' }"
                    class="text-[15px] font-medium text-slate-600 hover:text-[#003874] hover:underline no-underline"
                >
                    Crear una cuenta
                </RouterLink>
                <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-slate-500 pt-1">
                    <RouterLink :to="{ name: 'home' }" class="font-medium text-slate-600 hover:text-[#003874] hover:underline no-underline">
                        Volver al inicio
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
