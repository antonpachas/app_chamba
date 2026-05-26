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

const role = ref('cliente');
const fullName = ref('');
const email = ref('');
const phone = ref('');
const password = ref('');
const passwordConfirm = ref('');
const loading = ref(false);
const error = ref('');

onMounted(() => {
    const c = route.query.cuenta;
    if (c === 'cliente' || c === 'proveedor') role.value = c;
});

function homeForRole(r) {
    if (r === 'proveedor') return { name: 'provider-dashboard' };
    return { name: 'home' };
}

async function submit() {
    error.value = '';
    if (password.value !== passwordConfirm.value) {
        error.value = 'Las contraseñas no coinciden.';
        return;
    }
    loading.value = true;
    try {
        const u = await auth.register({
            full_name: fullName.value.trim(),
            email: email.value.trim(),
            phone: phone.value.trim() || null,
            password: password.value,
            password_confirmation: passwordConfirm.value,
            role: role.value,
        });
        const next = typeof route.query.next === 'string' ? route.query.next : '';
        if (next) router.replace(next);
        else router.replace(homeForRole(u?.role || role.value));
    } catch (e) {
        error.value = e.message || 'No se pudo registrar.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[520px] rounded-xl bg-white shadow-lg border border-slate-200/60 overflow-hidden">
            <div class="px-8 pt-10 pb-2">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl overflow-hidden shadow-lg shadow-[#003874]/25 ring-1 ring-slate-200">
                        <img src="/img/chamba-icon.png" alt="" class="h-14 w-14" />
                    </div>
                    <span class="text-2xl font-black tracking-tight text-grad-brand">Chamba</span>
                </div>
                <h1 class="text-center font-['Playfair_Display',Georgia,serif] text-[1.85rem] font-semibold text-slate-900 mb-6">
                    Crear cuenta
                </h1>
                <p class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                    Tipo de cuenta
                </p>
                <div class="mb-6 grid grid-cols-2 gap-1.5 rounded-lg border border-slate-200 bg-slate-50 p-1">
                    <button
                        type="button"
                        class="rounded-md py-2.5 text-sm font-semibold transition"
                        :class="role === 'cliente' ? 'bg-white text-[#003874] shadow-sm ring-1 ring-slate-200/80' : 'text-slate-600 hover:text-slate-900'"
                        @click="role = 'cliente'"
                    >
                        Cliente
                    </button>
                    <button
                        type="button"
                        class="rounded-md py-2.5 text-sm font-semibold transition"
                        :class="role === 'proveedor' ? 'bg-white text-[#003874] shadow-sm ring-1 ring-slate-200/80' : 'text-slate-600 hover:text-slate-900'"
                        @click="role = 'proveedor'"
                    >
                        Proveedor
                    </button>
                </div>
                <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>
                <form class="space-y-5" @submit.prevent="submit">
                    <AppInput v-model="fullName" label="Nombre completo" required autocomplete="name" />
                    <AppInput v-model="email" label="Correo electrónico" type="email" required autocomplete="email" />
                    <AppInput v-model="phone" label="Teléfono (opcional)" type="tel" autocomplete="tel" />
                    <div class="grid sm:grid-cols-2 gap-4">
                        <AppInput v-model="password" label="Contraseña" type="password" :minlength="8" required autocomplete="new-password" />
                        <AppInput v-model="passwordConfirm" label="Confirmar contraseña" type="password" :minlength="8" required autocomplete="new-password" />
                    </div>
                    <AppButton variant="primary" type="submit" :loading="loading" block>
                        Crear mi cuenta
                    </AppButton>
                </form>
            </div>
            <div class="bg-slate-50 border-t border-slate-200 px-8 py-5 text-center">
                <RouterLink
                    :to="{ name: 'login', query: route.query }"
                    class="text-[15px] font-medium text-slate-600 hover:text-[#003874] hover:underline no-underline"
                >
                    ¿Ya tienes cuenta? Inicia sesión
                </RouterLink>
                <p class="mt-2 text-xs text-slate-500">
                    <RouterLink :to="{ name: 'home' }" class="hover:text-[#003874] hover:underline no-underline">Volver al inicio</RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>
