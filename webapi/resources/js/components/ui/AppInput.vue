<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    autocomplete: { type: String, default: '' },
    required: { type: Boolean, default: false },
    minlength: { type: [Number, String], default: undefined },
    name: { type: String, default: '' },
    error: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block">
        <span v-if="label" class="mb-2 block text-sm font-bold text-slate-700">{{ label }}</span>
        <input
            :value="modelValue"
            :type="type"
            :name="name"
            :placeholder="placeholder"
            :required="required"
            :minlength="minlength"
            :autocomplete="autocomplete"
            class="ui-input disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-70"
            :class="error ? 'border-rose-300 ring-rose-100 focus:border-rose-500 focus:ring-rose-200' : ''"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <span v-if="error" class="mt-1.5 block text-xs font-medium text-rose-700">{{ error }}</span>
    </label>
</template>
