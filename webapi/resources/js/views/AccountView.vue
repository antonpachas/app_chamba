<script setup>
import { onMounted, ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import AvatarUploader from '@/components/common/AvatarUploader.vue';

const router = useRouter();
const auth = useAuthStore();
const escrow = escrowEnabled();

const form = ref({
    full_name: '',
    email: '',
    phone: '',
    current_password: '',
    password: '',
    password_confirmation: '',
});
const saving = ref(false);
const errMsg = ref('');
const okMsg = ref('');
const showPasswordFields = ref(false);

function syncFormFromUser() {
    form.value.full_name = auth.user?.full_name || '';
    form.value.email = auth.user?.email || '';
    form.value.phone = auth.user?.phone || '';
    form.value.current_password = '';
    form.value.password = '';
    form.value.password_confirmation = '';
}

onMounted(() => {
    syncFormFromUser();
});

async function saveProfile() {
    errMsg.value = '';
    okMsg.value = '';
    saving.value = true;
    try {
        const payload = {
            full_name: form.value.full_name.trim(),
            email: form.value.email.trim(),
            phone: form.value.phone.trim() || null,
        };
        if (showPasswordFields.value && form.value.password) {
            payload.current_password = form.value.current_password;
            payload.password = form.value.password;
            payload.password_confirmation = form.value.password_confirmation;
        }
        await auth.updateProfile(payload);
        okMsg.value = 'Datos guardados correctamente.';
        showPasswordFields.value = false;
        syncFormFromUser();
    } catch (e) {
        errMsg.value = e.message || 'No se pudo guardar el perfil.';
    } finally {
        saving.value = false;
    }
}

async function doLogout() {
    await auth.logout();
    router.replace({ name: 'home' });
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-12 pb-32 md:pb-16">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mi cuenta</h1>
            <p class="text-slate-600 mt-1">Edita tus datos personales y foto de perfil.</p>
        </header>

        <AppAlert v-if="errMsg" type="error" class="mb-4">{{ errMsg }}</AppAlert>
        <AppAlert v-if="okMsg" type="success" class="mb-4">{{ okMsg }}</AppAlert>

        <div class="grid lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-8 lg:p-10 shadow-sm">
                    <AvatarUploader class="mb-6 pb-6 border-b border-slate-100" />
                    <p class="text-xs font-bold text-[#003874] uppercase tracking-widest flex items-center gap-2 mb-6">
                        {{ auth.roleLabel }}
                        <span v-if="auth.isPro" class="text-[10px] font-black uppercase rounded-full px-2 py-0.5 text-white bg-grad-warm shadow">PRO</span>
                        <span v-if="auth.inTrial" class="text-[10px] font-bold uppercase rounded-full px-2 py-0.5 bg-amber-100 text-amber-800">Trial · {{ auth.trialDaysLeft }}d</span>
                    </p>

                    <form class="space-y-5" @submit.prevent="saveProfile">
                        <AppInput v-model="form.full_name" label="Nombre completo" placeholder="Juan Pérez" autocomplete="name" />
                        <AppInput v-model="form.email" type="email" label="Correo electrónico" placeholder="correo@ejemplo.com" autocomplete="email" />
                        <AppInput v-model="form.phone" label="Teléfono (opcional)" placeholder="999 999 999" autocomplete="tel" />

                        <div class="pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                class="text-sm font-bold text-[#003874] hover:underline"
                                @click="showPasswordFields = !showPasswordFields"
                            >
                                {{ showPasswordFields ? 'Ocultar cambio de contraseña' : 'Cambiar contraseña' }}
                            </button>
                            <div v-if="showPasswordFields" class="mt-4 space-y-4">
                                <AppInput
                                    v-model="form.current_password"
                                    type="password"
                                    label="Contraseña actual"
                                    autocomplete="current-password"
                                />
                                <AppInput
                                    v-model="form.password"
                                    type="password"
                                    label="Nueva contraseña"
                                    autocomplete="new-password"
                                />
                                <AppInput
                                    v-model="form.password_confirmation"
                                    type="password"
                                    label="Confirmar nueva contraseña"
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-2">
                            <AppButton variant="primary" type="submit" :loading="saving">Guardar cambios</AppButton>
                        </div>
                    </form>

                    <p class="text-sm text-slate-600 mt-6 font-medium">
                        Estado:
                        <span class="text-[#0b1c30]">{{ auth.user?.status || '' }}</span>
                    </p>
                    <p v-if="auth.planName" class="text-sm text-slate-600 mt-1 font-medium">
                        Plan: <span class="text-[#0b1c30]">{{ auth.planName }}</span>
                    </p>
                </div>
            </div>

            <div class="lg:col-span-5 flex flex-col gap-3">
                <RouterLink v-if="auth.isCliente" :to="{ name: 'home' }" class="rounded-full btn-grad-primary text-center no-underline py-3.5 px-6">Explorar anuncios</RouterLink>
                <RouterLink v-if="auth.isCliente" :to="{ name: 'client-requests' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis solicitudes</RouterLink>
                <RouterLink v-if="auth.isCliente" :to="{ name: 'client-favorites' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis favoritos</RouterLink>
                <RouterLink v-if="auth.isCliente || auth.isProveedor" :to="{ name: 'support' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Soporte / ayuda</RouterLink>
                <RouterLink v-if="auth.isCliente" :to="{ name: 'client-subscription' }" class="rounded-full btn-grad-warm text-center no-underline py-3 px-6">{{ auth.isPro ? 'Mi membresía Premium' : 'Hazte Premium · S/ 9' }}</RouterLink>
                <RouterLink v-if="auth.isCliente && escrow" :to="{ name: 'client-wallet' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis pagos</RouterLink>

                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-dashboard' }" class="rounded-full btn-grad-primary text-center no-underline py-3.5 px-6">Ir a mi panel</RouterLink>
                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-listings' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis anuncios</RouterLink>
                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-subscription' }" class="rounded-full btn-grad-warm text-center no-underline py-3 px-6">{{ auth.isPro ? 'Mi plan Pro' : 'Pasarme a Pro · S/ 29' }}</RouterLink>
                <RouterLink v-if="auth.isProveedor && escrow" :to="{ name: 'provider-wallet' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis ingresos</RouterLink>
                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-profile' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Perfil del negocio</RouterLink>

                <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-dashboard' }" class="rounded-full btn-grad-primary text-center no-underline py-3.5 px-6">Panel admin</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-support' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Casos de soporte</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-ledger' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Kardex (ingresos / egresos)</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-subscriptions' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Membresías</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin' && escrow" :to="{ name: 'admin-payments' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Pagos en custodia</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin' && escrow" :to="{ name: 'admin-withdrawals' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Retiros</RouterLink>

                <AppButton variant="outline" block @click="doLogout">Cerrar sesión</AppButton>
            </div>
        </div>
    </div>
</template>
