<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import LoginForm from '@/components/auth/LoginForm.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const route = useRoute();
const flash = ref('');

onMounted(() => {
    if (typeof route.query.flash === 'string') flash.value = route.query.flash;
});
</script>

<template>
    <div class="min-h-screen auth-shell-bg flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[440px] ui-card overflow-hidden px-8 pt-10 pb-8">
            <AppAlert v-if="flash" type="success" class="mb-4">{{ flash }}</AppAlert>
            <LoginForm />
            <div class="mt-6 text-center">
                <RouterLink :to="{ name: 'home' }" class="text-xs font-medium text-slate-500 hover:text-chamba-700 no-underline">
                    ← Volver al inicio
                </RouterLink>
            </div>
        </div>
    </div>
</template>
