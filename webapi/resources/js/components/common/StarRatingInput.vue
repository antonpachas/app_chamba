<script setup>
const props = defineProps({
    modelValue: { type: Number, default: 0 },
    readonly: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['update:modelValue']);

const sizes = {
    sm: 'text-xl',
    md: 'text-2xl',
    lg: 'text-3xl',
};

function pick(n) {
    if (props.readonly) return;
    emit('update:modelValue', n);
}
</script>

<template>
    <div class="inline-flex items-center gap-0.5" role="group" aria-label="Calificación">
        <button
            v-for="n in 5"
            :key="n"
            type="button"
            class="leading-none transition-transform"
            :class="[
                sizes[size] || sizes.md,
                readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110',
            ]"
            :disabled="readonly"
            :aria-label="`${n} estrellas`"
            @click="pick(n)"
        >
            <span
                class="material-symbols-outlined"
                :class="n <= modelValue ? 'text-amber-500' : 'text-slate-300'"
                :style="n <= modelValue ? { fontVariationSettings: '\'FILL\' 1' } : undefined"
            >star</span>
        </button>
    </div>
</template>
