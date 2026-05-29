<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useLoginModalStore } from '@/stores/loginModal';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppPasswordInput from '@/components/ui/AppPasswordInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import { asset } from '@/utils/asset';

const props = defineProps({
    /** Si true, cierra el modal al iniciar sesión con éxito */
    modal: { type: Boolean, default: false },
});

const emit = defineEmits(['success', 'close']);

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const loginModal = useLoginModalStore();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

watch(
    () => loginModal.open,
    (isOpen) => {
        if (props.modal && isOpen) {
            error.value = '';
            email.value = '';
            password.value = '';
        }
    },
);

function homeForRole(role) {
    if (role === 'admin') return { name: 'admin-dashboard' };
    if (role === 'proveedor') return { name: 'provider-dashboard' };
    return { name: 'home' };
}

function resolveNextPath() {
    if (props.modal && loginModal.redirectNext) {
        return loginModal.redirectNext;
    }
    if (!props.modal && typeof route.query.next === 'string' && route.query.next.trim()) {
        return route.query.next.trim();
    }
    return '';
}

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        const u = await auth.login({ email: email.value.trim(), password: password.value });
        auth.refreshGuestBrowseAfterAuth();

        const next = resolveNextPath();
        emit('success', u);

        if (props.modal) {
            loginModal.hideLogin();
        }

        if (next && next !== route.fullPath) {
            await router.push(next);
        } else if (!props.modal) {
            await router.replace(homeForRole(u?.role));
        } else if (u?.role === 'admin' || u?.role === 'proveedor') {
            await router.push(homeForRole(u?.role));
        }
    } catch (e) {
        error.value = e.message || 'No se pudo iniciar sesión.';
    } finally {
        loading.value = false;
    }
}

function onClose() {
    if (props.modal) {
        loginModal.hideLogin();
    }
    emit('close');
}
</script>

<template>
    <div>
        <div v-if="modal" class="flex items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-2.5 min-w-0">
                <img :src="asset('img/chamba-icon.png')" alt="" class="h-10 w-10 rounded-xl ring-1 ring-slate-200/80 shrink-0" />
                <div class="min-w-0">
                    <h2 id="login-modal-title" class="text-lg font-semibold text-slate-900 leading-tight">Iniciar sesión</h2>
                    <p class="text-xs text-slate-500 truncate">Accede a contacto, favoritos y más</p>
                </div>
            </div>
            <button
                type="button"
                class="shrink-0 w-9 h-9 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 flex items-center justify-center border-0 bg-transparent cursor-pointer"
                aria-label="Cerrar"
                @click="onClose"
            >
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <template v-else>
            <div class="flex items-center justify-center gap-3 mb-8">
                <img :src="asset('img/chamba-icon.png')" alt="" class="h-14 w-14 rounded-2xl shadow-lg ring-1 ring-slate-200/80" />
                <span class="text-2xl font-black tracking-tight text-grad-brand">Busca PE</span>
            </div>
            <h1 class="text-center font-display text-3xl font-semibold text-chamba-ink leading-tight mb-2">
                Bienvenido
            </h1>
            <p class="text-center text-sm text-slate-600 mb-8">
                Ingresa con tu correo. Tu cuenta define si eres cliente o negocio.
            </p>
        </template>

        <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>

        <form class="space-y-4" @submit.prevent="submit">
            <AppInput
                v-model="email"
                label="Correo electrónico"
                type="email"
                placeholder="tu@correo.com"
                autocomplete="email"
                required
            />
            <div>
                <AppPasswordInput
                    v-model="password"
                    label="Contraseña"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                    :minlength="8"
                />
                <RouterLink
                    v-if="modal"
                    :to="{ name: 'forgot' }"
                    class="mt-2 inline-block text-sm font-medium text-[#003874] hover:underline no-underline"
                    @click="onClose"
                >
                    ¿Olvidaste tu contraseña?
                </RouterLink>
                <RouterLink
                    v-else
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

        <p class="mt-5 pt-5 border-t border-slate-100 text-center text-sm text-slate-600">
            ¿No tienes cuenta?
            <RouterLink
                :to="{ name: 'register', query: modal && loginModal.redirectNext ? { next: loginModal.redirectNext } : route.query }"
                class="font-semibold text-[#003874] hover:underline no-underline"
                @click="onClose"
            >
                Crear cuenta
            </RouterLink>
        </p>
    </div>
</template>
