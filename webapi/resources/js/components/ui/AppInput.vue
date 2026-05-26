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
            class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15 disabled:opacity-60"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <span v-if="error" class="mt-1 block text-xs font-medium text-red-700">{{ error }}</span>
    </label>
</template>
