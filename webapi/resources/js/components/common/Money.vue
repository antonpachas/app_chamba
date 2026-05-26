<script setup>
const props = defineProps({
    amount: { type: [String, Number], default: null },
    currency: { type: String, default: 'PEN' },
});
const symbol = props.currency === 'USD' ? '$' : 'S/';
</script>

<template>
    <span class="tabular-nums">
        <template v-if="amount === null || amount === undefined || amount === ''">—</template>
        <template v-else>{{ symbol }}&nbsp;{{ Number(amount).toFixed(2) }}</template>
    </span>
</template>
