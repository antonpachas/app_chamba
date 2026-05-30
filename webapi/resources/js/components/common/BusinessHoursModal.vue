<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import BusinessHoursDisplay from '@/components/common/BusinessHoursDisplay.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    schedule: { type: Object, default: null },
    providerName: { type: String, default: '' },
});

const emit = defineEmits(['close']);

function onKeydown(e) {
    if (e.key === 'Escape' && props.open) {
        emit('close');
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (typeof document === 'undefined') return;
        document.body.style.overflow = isOpen ? 'hidden' : '';
    },
);

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    if (typeof document !== 'undefined') document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="login-modal">
            <div
                v-if="open"
                class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="hours-modal-title"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px] border-0 cursor-pointer"
                    aria-label="Cerrar"
                    @click="emit('close')"
                />
                <div
                    class="relative w-full sm:max-w-md max-h-[85vh] flex flex-col bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl border border-slate-200"
                    @click.stop
                >
                    <div class="shrink-0 px-5 pt-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-3">
                        <div>
                            <h2 id="hours-modal-title" class="text-lg font-bold text-slate-900">
                                Horario de atención
                            </h2>
                            <p v-if="providerName" class="text-sm text-slate-500 mt-0.5">{{ providerName }}</p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 w-9 h-9 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center justify-center cursor-pointer bg-white"
                            aria-label="Cerrar"
                            @click="emit('close')"
                        >
                            <span class="material-symbols-outlined text-[22px]">close</span>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto">
                        <BusinessHoursDisplay :schedule="schedule" full-week embedded />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
