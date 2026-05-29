<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
    router.replace({ name: 'account', hash: '#perfil-negocio' });
});
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-16 text-center text-slate-500">
        Redirigiendo a Mi cuenta…
    </div>
</template>
