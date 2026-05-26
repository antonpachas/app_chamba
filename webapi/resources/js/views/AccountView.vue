<script setup>
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { escrowEnabled } from '@/services/features';
import AppButton from '@/components/ui/AppButton.vue';
import AvatarUploader from '@/components/common/AvatarUploader.vue';

const router = useRouter();
const auth = useAuthStore();
const escrow = escrowEnabled();

async function doLogout() {
    await auth.logout();
    router.replace({ name: 'home' });
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-12 pb-32 md:pb-16">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mi cuenta</h1>
            <p class="text-slate-600 mt-1">Resumen de tu sesión.</p>
        </header>
        <div class="grid lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 rounded-3xl border border-slate-200 bg-white p-8 lg:p-10 shadow-sm">
                <AvatarUploader class="mb-6 pb-6 border-b border-slate-100" />
                <p class="text-xs font-bold text-[#003874] uppercase tracking-widest flex items-center gap-2">
                    {{ auth.roleLabel }}
                    <span v-if="auth.isPro" class="text-[10px] font-black uppercase rounded-full px-2 py-0.5 text-white bg-grad-warm shadow">PRO</span>
                    <span v-if="auth.inTrial" class="text-[10px] font-bold uppercase rounded-full px-2 py-0.5 bg-amber-100 text-amber-800">Trial · {{ auth.trialDaysLeft }}d</span>
                </p>
                <h2 class="text-3xl lg:text-4xl font-black text-[#0b1c30] mt-2 tracking-tight">
                    {{ auth.user?.full_name || '' }}
                </h2>
                <p class="text-slate-700 text-base mt-4">{{ auth.user?.email || '' }}</p>
                <p v-if="auth.user?.phone" class="text-slate-700 text-sm mt-1">{{ auth.user.phone }}</p>
                <p class="text-sm text-slate-600 mt-6 font-medium">
                    Estado:
                    <span class="text-[#0b1c30]">{{ auth.user?.status || '' }}</span>
                </p>
                <p v-if="auth.planName" class="text-sm text-slate-600 mt-1 font-medium">
                    Plan: <span class="text-[#0b1c30]">{{ auth.planName }}</span>
                </p>
            </div>
            <div class="lg:col-span-5 flex flex-col gap-3">
                <RouterLink v-if="auth.isCliente" :to="{ name: 'search' }" class="rounded-full btn-grad-primary text-center no-underline py-3.5 px-6">Buscar servicios</RouterLink>
                <RouterLink v-if="auth.isCliente" :to="{ name: 'client-requests' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis solicitudes</RouterLink>
                <RouterLink v-if="auth.isCliente" :to="{ name: 'client-favorites' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis favoritos</RouterLink>
                <RouterLink v-if="auth.isCliente" :to="{ name: 'client-subscription' }" class="rounded-full btn-grad-warm text-center no-underline py-3 px-6">{{ auth.isPro ? 'Mi membresía Premium' : 'Hazte Premium · S/ 9' }}</RouterLink>
                <RouterLink v-if="auth.isCliente && escrow" :to="{ name: 'client-wallet' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis pagos</RouterLink>

                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-dashboard' }" class="rounded-full btn-grad-primary text-center no-underline py-3.5 px-6">Ir a mi panel</RouterLink>
                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-services' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis servicios</RouterLink>
                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-subscription' }" class="rounded-full btn-grad-warm text-center no-underline py-3 px-6">{{ auth.isPro ? 'Mi plan Pro' : 'Pasarme a Pro · S/ 29' }}</RouterLink>
                <RouterLink v-if="auth.isProveedor && escrow" :to="{ name: 'provider-wallet' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mis ingresos</RouterLink>
                <RouterLink v-if="auth.isProveedor" :to="{ name: 'provider-profile' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Mi perfil</RouterLink>

                <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-dashboard' }" class="rounded-full btn-grad-primary text-center no-underline py-3.5 px-6">Panel admin</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-subscriptions' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Membresías</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin' && escrow" :to="{ name: 'admin-payments' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Pagos en custodia</RouterLink>
                <RouterLink v-if="auth.user?.role === 'admin' && escrow" :to="{ name: 'admin-withdrawals' }" class="rounded-full bg-white border-2 border-slate-200 hover:border-[#003874]/40 text-slate-800 font-bold py-3 px-6 text-center no-underline">Retiros</RouterLink>

                <AppButton variant="outline" block @click="doLogout">Cerrar sesión</AppButton>
            </div>
        </div>
    </div>
</template>
