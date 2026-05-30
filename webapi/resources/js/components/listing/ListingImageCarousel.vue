<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    alt: { type: String, default: '' },
    /** Flechas prev/next al pasar el cursor (varias fotos). */
    showArrows: { type: Boolean, default: true },
});

const scrollEl = ref(null);
const activeIndex = ref(0);

const slides = computed(() => {
    const list = Array.isArray(props.images) ? props.images.filter(Boolean) : [];
    return list.length ? list : [];
});

const hasMultiple = computed(() => slides.value.length > 1);
const showNav = computed(() => props.showArrows && hasMultiple.value);

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
    const i = Math.max(0, Math.min(index, slides.value.length - 1));
    el.scrollTo({ left: w * i, behavior: 'smooth' });
    activeIndex.value = i;
}

function prev() {
    if (!hasMultiple.value) return;
    goTo((activeIndex.value - 1 + slides.value.length) % slides.value.length);
}

function next() {
    if (!hasMultiple.value) return;
    goTo((activeIndex.value + 1) % slides.value.length);
}
</script>

<template>
    <div class="listing-carousel group/carousel relative w-full h-full">
        <div
            ref="scrollEl"
            class="flex h-full w-full overflow-x-auto snap-x snap-mandatory scroll-smooth touch-pan-x"
            :class="hasMultiple ? 'scrollbar-none' : ''"
            @scroll.passive="onScroll"
            @click.stop
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

        <template v-if="showNav">
            <button
                type="button"
                class="listing-carousel__nav listing-carousel__nav--prev"
                aria-label="Foto anterior"
                @click.stop="prev"
            >
                <span class="material-symbols-outlined text-[22px]">chevron_left</span>
            </button>
            <button
                type="button"
                class="listing-carousel__nav listing-carousel__nav--next"
                aria-label="Foto siguiente"
                @click.stop="next"
            >
                <span class="material-symbols-outlined text-[22px]">chevron_right</span>
            </button>
        </template>

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

.listing-carousel__nav {
    position: absolute;
    top: 50%;
    z-index: 15;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border: none;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.95);
    color: #003874;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
    cursor: pointer;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, background 0.15s ease, transform 0.15s ease;
}
.group\/carousel:hover .listing-carousel__nav,
.group\/carousel:focus-within .listing-carousel__nav {
    opacity: 1;
    pointer-events: auto;
}
.listing-carousel__nav:hover {
    background: #fff;
    transform: translateY(-50%) scale(1.05);
}
.listing-carousel__nav--prev {
    left: 0.5rem;
}
.listing-carousel__nav--next {
    right: 0.5rem;
}
@media (max-width: 639px) {
    .listing-carousel__nav {
        width: 2rem;
        height: 2rem;
        opacity: 1;
        pointer-events: auto;
        background: rgba(255, 255, 255, 0.88);
    }
}
</style>
