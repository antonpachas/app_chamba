<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { useLoginModalStore } from '@/stores/loginModal';
import LoginForm from '@/components/auth/LoginForm.vue';

const loginModal = useLoginModalStore();

function onKeydown(e) {
    if (e.key === 'Escape' && loginModal.open) {
        loginModal.hideLogin();
    }
}

watch(
    () => loginModal.open,
    (isOpen) => {
        if (typeof document === 'undefined') return;
        document.body.style.overflow = isOpen ? 'hidden' : '';
    },
);

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="login-modal">
            <div
                v-if="loginModal.open"
                class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="login-modal-title"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px] border-0 cursor-pointer"
                    aria-label="Cerrar"
                    @click="loginModal.hideLogin()"
                />
                <div
                    class="relative w-full sm:max-w-md max-h-[92vh] overflow-y-auto bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl border border-slate-200 p-6 sm:p-8"
                    @click.stop
                >
                    <LoginForm modal />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.login-modal-enter-active,
.login-modal-leave-active {
    transition: opacity 0.2s ease;
}
.login-modal-enter-active .relative,
.login-modal-leave-active .relative {
    transition: transform 0.22s ease, opacity 0.22s ease;
}
.login-modal-enter-from,
.login-modal-leave-to {
    opacity: 0;
}
.login-modal-enter-from .relative,
.login-modal-leave-to .relative {
    transform: translateY(12px);
    opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
    .login-modal-enter-active,
    .login-modal-leave-active,
    .login-modal-enter-active .relative,
    .login-modal-leave-active .relative {
        transition: none;
    }
    .login-modal-enter-from .relative,
    .login-modal-leave-to .relative {
        transform: none;
    }
}
</style>
