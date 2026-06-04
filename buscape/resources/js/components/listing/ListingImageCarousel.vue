<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    alt: { type: String, default: '' },
    showArrows: { type: Boolean, default: true },
    edgeToEdge: { type: Boolean, default: false },
    /** Habilita clic para abrir lightbox fullscreen. */
    lightbox: { type: Boolean, default: false },
});

const scrollEl = ref(null);
const activeIndex = ref(0);

/* ── Lightbox ───────────────────────────── */
const lbOpen = ref(false);
const lbIdx = ref(0);

function openLightbox(i) {
    if (!props.lightbox) return;
    lbIdx.value = i;
    lbOpen.value = true;
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    lbOpen.value = false;
    document.body.style.overflow = '';
}
function lbPrev() {
    lbIdx.value = (lbIdx.value - 1 + slides.value.length) % slides.value.length;
}
function lbNext() {
    lbIdx.value = (lbIdx.value + 1) % slides.value.length;
}
function handleKey(e) {
    if (!lbOpen.value) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') lbPrev();
    if (e.key === 'ArrowRight') lbNext();
}
onMounted(() => window.addEventListener('keydown', handleKey));
onUnmounted(() => {
    window.removeEventListener('keydown', handleKey);
    document.body.style.overflow = '';
});

/* ── Carrusel ───────────────────────────── */
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
    const i = Math.max(0, Math.min(index, slides.value.length - 1));
    el.scrollTo({ left: (el.clientWidth || 0) * i, behavior: 'smooth' });
    activeIndex.value = i;
}
function prev() { if (hasMultiple.value) goTo((activeIndex.value - 1 + slides.value.length) % slides.value.length); }
function next() { if (hasMultiple.value) goTo((activeIndex.value + 1) % slides.value.length); }
</script>

<template>
    <div
        class="listing-carousel group/carousel relative w-full h-full"
        :class="{ 'listing-carousel--edge': edgeToEdge }"
    >
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
                class="w-full min-w-full h-full shrink-0 snap-center object-cover"
                :class="[edgeToEdge ? 'listing-carousel__img--edge' : '', lightbox ? 'cursor-zoom-in' : '']"
                loading="lazy"
                @click="openLightbox(i)"
            />
        </div>

        <template v-if="showNav">
            <button type="button" class="listing-carousel__nav listing-carousel__nav--prev" aria-label="Foto anterior" @click.stop="prev">
                <span class="material-symbols-outlined text-[22px]">chevron_left</span>
            </button>
            <button type="button" class="listing-carousel__nav listing-carousel__nav--next" aria-label="Foto siguiente" @click.stop="next">
                <span class="material-symbols-outlined text-[22px]">chevron_right</span>
            </button>
        </template>

        <div v-if="hasMultiple" class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-10 pointer-events-none">
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

        <!-- Hint de zoom (solo en lightbox) -->
        <span
            v-if="lightbox && slides.length"
            class="absolute bottom-3 right-3 z-10 flex items-center gap-1 text-[10px] font-medium text-white bg-black/45 backdrop-blur px-2 py-0.5 rounded-full pointer-events-none"
        >
            <span class="material-symbols-outlined text-[13px]">zoom_in</span>
            Ver más grande
        </span>
    </div>

    <!-- ── Lightbox fullscreen ────────────────────────────── -->
    <Teleport to="body">
        <Transition name="lb-fade">
            <div
                v-if="lbOpen"
                class="lb-overlay"
                role="dialog"
                aria-modal="true"
                @click.self="closeLightbox"
            >
                <!-- Imagen principal -->
                <div class="lb-img-wrap">
                    <img
                        :src="slides[lbIdx]"
                        :alt="`${alt} — foto ${lbIdx + 1}`"
                        class="lb-img"
                    />
                </div>

                <!-- Botón cerrar -->
                <button type="button" class="lb-close" aria-label="Cerrar" @click="closeLightbox">
                    <span class="material-symbols-outlined text-[26px]">close</span>
                </button>

                <!-- Flechas -->
                <button v-if="slides.length > 1" type="button" class="lb-nav lb-nav--prev" aria-label="Foto anterior" @click="lbPrev">
                    <span class="material-symbols-outlined text-[30px]">chevron_left</span>
                </button>
                <button v-if="slides.length > 1" type="button" class="lb-nav lb-nav--next" aria-label="Foto siguiente" @click="lbNext">
                    <span class="material-symbols-outlined text-[30px]">chevron_right</span>
                </button>

                <!-- Contador y miniaturas -->
                <div class="lb-footer">
                    <p class="lb-counter">{{ lbIdx + 1 }} / {{ slides.length }}</p>
                    <div v-if="slides.length > 1" class="lb-thumbs">
                        <button
                            v-for="(src, i) in slides"
                            :key="i"
                            type="button"
                            class="lb-thumb"
                            :class="i === lbIdx ? 'lb-thumb--active' : ''"
                            @click="lbIdx = i"
                        >
                            <img :src="src" :alt="`Miniatura ${i + 1}`" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.scrollbar-none { scrollbar-width: none; -ms-overflow-style: none; }
.scrollbar-none::-webkit-scrollbar { display: none; }

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
    background: rgba(255,255,255,0.95);
    color: #003874;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18);
    cursor: pointer;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, background 0.15s ease, transform 0.15s ease;
}
.group\/carousel:hover .listing-carousel__nav,
.group\/carousel:focus-within .listing-carousel__nav { opacity: 1; pointer-events: auto; }
.listing-carousel__nav:hover { background: #fff; transform: translateY(-50%) scale(1.05); }
.listing-carousel__nav--prev { left: 0.5rem; }
.listing-carousel__nav--next { right: 0.5rem; }

@media (max-width: 639px) {
    .listing-carousel__nav { width: 2rem; height: 2rem; opacity: 1; pointer-events: auto; background: rgba(255,255,255,0.88); }
}

/* ── Lightbox ────────────────────────────────────────── */
.lb-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(5, 10, 20, 0.96);
    padding: 0;
}

.lb-img-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 0;
    padding: 3.5rem 4rem 0;
}

.lb-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 8px 48px rgba(0,0,0,0.6);
    user-select: none;
    pointer-events: none;
}

.lb-close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 9999px;
    border: 0;
    background: rgba(255,255,255,0.12);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
    z-index: 10;
}
.lb-close:hover { background: rgba(255,255,255,0.22); }

.lb-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 3rem;
    height: 3rem;
    border-radius: 9999px;
    border: 1.5px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.08);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s, transform 0.15s;
    z-index: 10;
}
.lb-nav:hover { background: rgba(255,255,255,0.18); }
.lb-nav--prev { left: 0.75rem; }
.lb-nav--next { right: 0.75rem; }
.lb-nav--prev:hover { transform: translateY(-50%) translateX(-2px); }
.lb-nav--next:hover { transform: translateY(-50%) translateX(2px); }

.lb-footer {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1rem 1rem;
}

.lb-counter {
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(255,255,255,0.5);
    margin: 0;
    letter-spacing: 0.06em;
}

.lb-thumbs {
    display: flex;
    gap: 0.375rem;
    overflow-x: auto;
    max-width: 100%;
    padding-bottom: 0.25rem;
    scrollbar-width: none;
}
.lb-thumbs::-webkit-scrollbar { display: none; }

.lb-thumb {
    width: 3.25rem;
    height: 2.5rem;
    border-radius: 0.375rem;
    overflow: hidden;
    border: 2px solid transparent;
    flex-shrink: 0;
    cursor: pointer;
    transition: border-color 0.15s, opacity 0.15s;
    opacity: 0.55;
    padding: 0;
}
.lb-thumb img { width: 100%; height: 100%; object-fit: cover; }
.lb-thumb:hover { opacity: 0.85; }
.lb-thumb--active { border-color: #fff; opacity: 1; }

/* Transition */
.lb-fade-enter-active, .lb-fade-leave-active { transition: opacity 0.2s ease; }
.lb-fade-enter-from, .lb-fade-leave-to { opacity: 0; }

@media (max-width: 639px) {
    .lb-img-wrap { padding: 3rem 0.75rem 0; }
    .lb-nav { width: 2.5rem; height: 2.5rem; }
    .lb-nav--prev { left: 0.25rem; }
    .lb-nav--next { right: 0.25rem; }
}
</style>
