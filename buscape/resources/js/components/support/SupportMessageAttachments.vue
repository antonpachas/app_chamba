<script setup>
/**
 * Muestra adjuntos de imagen en un mensaje de soporte.
 */
defineProps({
    attachments: { type: Array, default: () => [] },
    dark: { type: Boolean, default: false },
});
</script>

<template>
    <div v-if="attachments?.length" class="mt-2 flex flex-wrap gap-2">
        <a
            v-for="att in attachments"
            :key="att.id"
            :href="att.url"
            target="_blank"
            rel="noopener noreferrer"
            class="block rounded-lg overflow-hidden ring-1 shrink-0"
            :class="dark ? 'ring-white/30 hover:ring-white/60' : 'ring-slate-200 hover:ring-[#003874]/40'"
            @click.stop
        >
            <img :src="att.url" alt="Adjunto" class="w-20 h-20 object-cover" loading="lazy" />
        </a>
    </div>
</template>
