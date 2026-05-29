<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const open = defineModel('open', { type: Boolean, default: false });

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const name = ref('');
const note = ref('');
const busy = ref(false);
const err = ref('');
const ok = ref('');

function close() {
    open.value = false;
    err.value = '';
    ok.value = '';
}

function goLogin() {
    close();
    router.push({
        name: 'login',
        query: { next: route.fullPath, cuenta: 'cliente' },
    });
}

async function submit() {
    if (!auth.isAuthenticated) {
        goLogin();
        return;
    }

    err.value = '';
    ok.value = '';
    const n = name.value.trim();
    if (n.length < 2) {
        err.value = 'Escribe el nombre de la categoría sugerida.';
        return;
    }
    busy.value = true;
    try {
        await api.post(
            '/category-suggestions',
            {
                name: n,
                note: note.value.trim() || undefined,
            },
            { auth: true },
        );
        ok.value = '¡Gracias! Revisaremos tu sugerencia y te avisaremos cuando esté disponible.';
        name.value = '';
        note.value = '';
        setTimeout(close, 2200);
    } catch (e) {
        if (e.status === 401) {
            goLogin();
            return;
        }
        err.value = e.message || 'No se pudo enviar.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/55 backdrop-blur-[2px]"
            @click.self="close"
        >
            <div
                v-if="!auth.isAuthenticated"
                class="w-full max-w-md rounded-3xl bg-white shadow-2xl p-8 text-center"
                role="dialog"
                aria-modal="true"
            >
                <span class="material-symbols-outlined text-5xl text-[#003874] mb-4">login</span>
                <h2 class="text-xl font-bold text-[#0b1c30] mb-2">Inicia sesión</h2>
                <p class="text-sm text-slate-600 mb-6">
                    Solo usuarios registrados pueden sugerir una categoría nueva.
                </p>
                <div class="flex flex-col gap-2">
                    <AppButton variant="primary" block @click="goLogin">Iniciar sesión</AppButton>
                    <AppButton variant="outline" block @click="close">Cerrar</AppButton>
                </div>
            </div>

            <div
                v-else
                class="w-full max-w-lg rounded-3xl bg-white shadow-2xl overflow-hidden"
                role="dialog"
                aria-modal="true"
                aria-labelledby="suggest-category-title"
            >
                <div class="bg-grad-brand px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <span
                            class="w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center shrink-0"
                            aria-hidden="true"
                        >
                            <span class="material-symbols-outlined text-[28px]">category</span>
                        </span>
                        <div>
                            <h2 id="suggest-category-title" class="text-xl font-bold tracking-tight">
                                Sugerir categoría
                            </h2>
                            <p class="text-sm text-white/85 mt-0.5">
                                Ayúdanos a ampliar el directorio
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-6 md:p-8">
                    <p class="text-sm text-slate-600 mb-5">
                        Escribe el nombre del rubro que echas en falta (ej. «Veterinarias», «Coworking»).
                        Opcionalmente cuéntanos por qué lo necesitas.
                    </p>
                    <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
                    <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>
                    <form class="space-y-5" @submit.prevent="submit">
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-800">Nombre de la categoría</span>
                            <input
                                v-model="name"
                                type="text"
                                maxlength="120"
                                required
                                autofocus
                                placeholder="Ej. Veterinarias, Taller de motos…"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-base outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                            />
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-800">Comentario (opcional)</span>
                            <textarea
                                v-model="note"
                                rows="3"
                                maxlength="500"
                                placeholder="Ej. Hay muchos negocios de este tipo en mi zona…"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-base resize-y min-h-[5rem] outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                            />
                        </label>
                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end pt-2">
                            <AppButton type="button" variant="outline" class="sm:min-w-[7rem]" @click="close">
                                Cancelar
                            </AppButton>
                            <AppButton type="submit" variant="primary" class="sm:min-w-[9rem]" :loading="busy">
                                Enviar sugerencia
                            </AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>
