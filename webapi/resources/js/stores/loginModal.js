import { ref } from 'vue';
import { defineStore } from 'pinia';

/** Modal de inicio de sesión reutilizable en toda la app */
export const useLoginModalStore = defineStore('loginModal', () => {
    const open = ref(false);
    /** Ruta a la que redirigir tras login exitoso (opcional) */
    const redirectNext = ref(null);

    function showLogin(next = null) {
        redirectNext.value = typeof next === 'string' && next.trim() ? next.trim() : null;
        open.value = true;
    }

    function hideLogin() {
        open.value = false;
        redirectNext.value = null;
    }

    return { open, redirectNext, showLogin, hideLogin };
});
