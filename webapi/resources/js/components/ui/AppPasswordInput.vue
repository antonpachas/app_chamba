<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: '' },
    required: { type: Boolean, default: false },
    minlength: { type: [Number, String], default: undefined },
    name: { type: String, default: '' },
    error: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const visible = ref(false);
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-2 block text-sm font-bold text-slate-700">{{ label }}</span>
        <div class="relative">
            <input
                :value="modelValue"
                :type="visible ? 'text' : 'password'"
                :name="name"
                :placeholder="placeholder"
                :required="required"
                :minlength="minlength"
                :autocomplete="autocomplete"
                class="ui-input pr-11 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-70"
                :class="error ? 'border-rose-300 ring-rose-100 focus:border-rose-500 focus:ring-rose-200' : ''"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:text-[#003874] hover:bg-slate-100 transition"
                :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                tabindex="-1"
                @click="visible = !visible"
            >
                <span class="material-symbols-outlined text-[20px]">{{ visible ? 'visibility_off' : 'visibility' }}</span>
            </button>
        </div>
        <span v-if="error" class="mt-1.5 block text-xs font-medium text-rose-700">{{ error }}</span>
    </label>
</template>
