<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppPasswordInput from '@/components/ui/AppPasswordInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import { asset } from '@/utils/asset';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const role = ref('cliente');
const fullName = ref('');
const email = ref('');
const phone = ref('');
const razonSocial = ref('');
const ruc = ref('');
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
    if (role.value === 'proveedor' && !razonSocial.value.trim()) {
        error.value = 'La razón social es obligatoria para registrar un negocio.';
        return;
    }
    if (ruc.value.trim() && !/^\d{11}$/.test(ruc.value.trim())) {
        error.value = 'El RUC debe tener 11 dígitos numéricos.';
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
        if (role.value === 'proveedor') {
            sessionStorage.setItem(
                'chamba_pending_business_legal',
                JSON.stringify({
                    razon_social: razonSocial.value.trim(),
                    ruc: ruc.value.trim() || null,
                }),
            );
        }
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
    <div class="min-h-screen auth-shell-bg flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[520px] ui-card overflow-hidden">
            <div class="px-8 pt-10 pb-2">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl overflow-hidden shadow-lg shadow-[#003874]/25 ring-1 ring-slate-200">
                        <img :src="asset('img/chamba-icon.png')" alt="" class="h-14 w-14" />
                    </div>
                    <span class="text-2xl font-black tracking-tight text-grad-brand">Busca PE</span>
                </div>
                <h1 class="text-center font-display text-3xl font-semibold text-chamba-ink mb-6">
                    Crear cuenta
                </h1>
                <p class="text-center text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                    Tipo de cuenta
                </p>
                <div class="mb-6 grid grid-cols-2 gap-1.5 rounded-xl border border-slate-200 bg-slate-50 p-1">
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
                        Negocio
                    </button>
                </div>
                <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>
                <form class="space-y-5" @submit.prevent="submit">
                    <AppInput v-model="fullName" label="Nombre completo" required autocomplete="name" />
                    <AppInput v-model="email" label="Correo electrónico" type="email" required autocomplete="email" />
                    <AppInput v-model="phone" label="Teléfono (opcional)" type="tel" autocomplete="tel" />
                    <template v-if="role === 'proveedor'">
                        <AppInput v-model="razonSocial" label="Razón social" required placeholder="Empresa SAC" />
                        <AppInput
                            v-model="ruc"
                            label="RUC (opcional)"
                            inputmode="numeric"
                            maxlength="11"
                            placeholder="20123456789"
                        />
                    </template>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <AppPasswordInput v-model="password" label="Contraseña" :minlength="8" required autocomplete="new-password" />
                        <AppPasswordInput v-model="passwordConfirm" label="Confirmar contraseña" :minlength="8" required autocomplete="new-password" />
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
