<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    alt: { type: String, default: '' },
});

const scrollEl = ref(null);
const activeIndex = ref(0);

const slides = computed(() => {
    const list = Array.isArray(props.images) ? props.images.filter(Boolean) : [];
    return list.length ? list : [];
});

const hasMultiple = computed(() => slides.value.length > 1);

function onScroll() {
    const el = scrollEl.value;
    if (!el || !hasMultiple.value) return;
    const w = el.clientWidth || 1;
    const idx = Math.round(el.scrollLeft / w);
    activeIndex.value = Math.max(0, Math.min(idx, slides.value.length - 1));
}

function goTo(index) {
    const el = scrollEl.value;
    if (!el) return;
    const w = el.clientWidth || 0;
    el.scrollTo({ left: w * index, behavior: 'smooth' });
    activeIndex.value = index;
}
</script>

<template>
    <div class="relative w-full h-full">
        <div
            ref="scrollEl"
            class="flex h-full w-full overflow-x-auto snap-x snap-mandatory scroll-smooth touch-pan-x"
            :class="hasMultiple ? 'scrollbar-none' : ''"
            @scroll.passive="onScroll"
        >
            <img
                v-for="(src, i) in slides"
                :key="`${src}-${i}`"
                :src="src"
                :alt="alt"
                class="w-full h-full shrink-0 snap-center object-cover"
                loading="lazy"
            />
        </div>
        <div
            v-if="hasMultiple"
            class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-10 pointer-events-none"
        >
            <button
                v-for="(_, i) in slides"
                :key="i"
                type="button"
                class="pointer-events-auto w-2 h-2 rounded-full transition-all"
                :class="i === activeIndex ? 'bg-white scale-110 shadow' : 'bg-white/50 hover:bg-white/80'"
                :aria-label="`Foto ${i + 1}`"
                @click.stop="goTo(i)"
            />
        </div>
        <p
            v-if="hasMultiple"
            class="absolute top-3 left-1/2 -translate-x-1/2 z-10 text-[10px] font-bold uppercase tracking-wide text-white bg-black/40 backdrop-blur px-2 py-0.5 rounded-full pointer-events-none"
        >
            {{ activeIndex + 1 }} / {{ slides.length }}
        </p>
    </div>
</template>

<style scoped>
.scrollbar-none {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
</style>
