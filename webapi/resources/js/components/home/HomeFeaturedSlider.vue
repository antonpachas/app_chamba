<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import { listingDetailTo } from '@/utils/listingRef';
import Money from '@/components/common/Money.vue';

const items = ref([]);
const loading = ref(true);
const scrollEl = ref(null);
const activeIndex = ref(0);
let autoplayTimer = null;

const hasItems = computed(() => items.value.length > 0);
const hasMultiple = computed(() => items.value.length > 1);

async function load() {
    loading.value = true;
    try {
        const r = await api.get('/listings/home-featured');
        items.value = r.data || [];
    } catch {
        items.value = [];
    } finally {
        loading.value = false;
    }
}

function onScroll() {
    const el = scrollEl.value;
    if (!el || !hasMultiple.value) return;
    const w = el.clientWidth || 1;
    activeIndex.value = Math.max(0, Math.min(Math.round(el.scrollLeft / w), items.value.length - 1));
}

function goTo(index) {
    const el = scrollEl.value;
    if (!el) return;
    const w = el.clientWidth || 0;
    el.scrollTo({ left: w * index, behavior: 'smooth' });
    activeIndex.value = index;
}

function nextSlide() {
    if (!hasMultiple.value) return;
    goTo((activeIndex.value + 1) % items.value.length);
}

function startAutoplay() {
    stopAutoplay();
    if (!hasMultiple.value) return;
    autoplayTimer = window.setInterval(nextSlide, 5500);
}

function stopAutoplay() {
    if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
}

onMounted(async () => {
    await load();
    startAutoplay();
});

onUnmounted(stopAutoplay);
</script>

<template>
    <section v-if="loading || hasItems" class="home-featured" aria-label="Anuncios destacados">
        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-semibold text-slate-900">Destacados del directorio</h2>
                <span v-if="hasItems" class="text-xs text-slate-500">{{ items.length }} anuncio{{ items.length === 1 ? '' : 's' }}</span>
            </div>

            <div v-if="loading" class="home-featured__skeleton rounded-2xl" />

            <div
                v-else
                class="relative rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-sm"
                @mouseenter="stopAutoplay"
                @mouseleave="startAutoplay"
            >
                <div
                    ref="scrollEl"
                    class="flex overflow-x-auto snap-x snap-mandatory scroll-smooth touch-pan-x home-featured__track"
                    @scroll.passive="onScroll"
                >
                    <RouterLink
                        v-for="item in items"
                        :key="item.service_id"
                        :to="listingDetailTo(item)"
                        class="home-featured__slide snap-center shrink-0 group no-underline text-inherit"
                    >
                        <div class="home-featured__slide-inner">
                            <div class="home-featured__media">
                                <img
                                    v-if="item.cover_image_url || item.images?.[0]"
                                    :src="item.cover_image_url || item.images?.[0]"
                                    :alt="item.title"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
                                    loading="lazy"
                                />
                                <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-300">
                                    <span class="material-symbols-outlined text-5xl">storefront</span>
                                </div>
                                <span class="home-featured__badge">Destacado</span>
                            </div>
                            <div class="home-featured__copy">
                                <p v-if="item.category_name" class="text-xs font-semibold uppercase tracking-wide text-[#003874]/80 mb-1">
                                    {{ item.category_name }}
                                </p>
                                <h3 class="text-lg md:text-xl font-bold text-slate-900 line-clamp-2 group-hover:text-[#003874] transition-colors">
                                    {{ item.title }}
                                </h3>
                                <p class="text-sm text-slate-600 mt-1 line-clamp-2">{{ item.provider_name }}</p>
                                <p v-if="item.district_name" class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">location_on</span>
                                    {{ item.district_name }}
                                </p>
                                <p v-if="item.base_price != null" class="text-base font-bold text-[#003874] mt-3">
                                    <Money :amount="item.base_price" />
                                </p>
                            </div>
                        </div>
                    </RouterLink>
                </div>

                <template v-if="hasMultiple">
                    <button
                        type="button"
                        class="home-featured__nav home-featured__nav--prev"
                        aria-label="Anterior"
                        @click="goTo((activeIndex - 1 + items.length) % items.length)"
                    >
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <button
                        type="button"
                        class="home-featured__nav home-featured__nav--next"
                        aria-label="Siguiente"
                        @click="nextSlide"
                    >
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                    <div class="home-featured__dots">
                        <button
                            v-for="(_, i) in items"
                            :key="i"
                            type="button"
                            class="home-featured__dot"
                            :class="i === activeIndex ? 'home-featured__dot--active' : ''"
                            :aria-label="`Ir al slide ${i + 1}`"
                            @click="goTo(i)"
                        />
                    </div>
                </template>
            </div>
        </div>
    </section>
</template>
