<script setup>
defineProps({
    type: { type: String, default: 'info' },
});

const styles = {
    info: 'bg-chamba-50 border-chamba-200 text-chamba-800',
    success: 'bg-emerald-50 border-emerald-200 text-emerald-900',
    error: 'bg-rose-50 border-rose-200 text-rose-900',
    warning: 'bg-amber-50 border-amber-200 text-amber-950',
};
</script>

<template>
    <div
        class="rounded-xl border px-4 py-3 text-sm font-medium leading-relaxed flex gap-3 items-start"
        :class="styles[type] || styles.info"
        role="alert"
    >
        <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5 opacity-80">
            {{ type === 'error' ? 'error' : type === 'success' ? 'check_circle' : type === 'warning' ? 'warning' : 'info' }}
        </span>
        <div class="min-w-0 flex-1"><slot /></div>
    </div>
</template>
