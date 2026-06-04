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
    autoplayTimer = window.setInterval(nextSlide, 6000);
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
    <section v-if="loading || hasItems" class="home-banner-section" aria-label="Anuncios destacados">
        <!-- Cabecera de sección -->
        <div v-if="!loading && hasItems" class="home-banner-section__header">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined home-banner-section__header-icon" style="font-variation-settings:'FILL' 1">workspace_premium</span>
                <h2 class="home-banner-section__header-title">Destacados</h2>
            </div>
            <span class="home-banner-section__header-badge">Patrocinado</span>
        </div>

        <!-- Skeleton -->
        <div v-if="loading" class="home-banner__skeleton" />

        <!-- Slider -->
        <div
            v-else
            class="home-banner__frame"
            @mouseenter="stopAutoplay"
            @mouseleave="startAutoplay"
        >
            <div
                ref="scrollEl"
                class="home-banner__track"
                @scroll.passive="onScroll"
            >
                <RouterLink
                    v-for="item in items"
                    :key="item.service_id"
                    :to="listingDetailTo(item)"
                    class="home-banner__slide snap-center shrink-0 group no-underline text-inherit"
                >
                    <!-- Fondo con imagen -->
                    <div class="home-banner__slide-bg">
                        <img
                            v-if="item.cover_image_url || item.images?.[0]"
                            :src="item.cover_image_url || item.images?.[0]"
                            :alt="item.title"
                            class="home-banner__slide-img"
                            loading="lazy"
                        />
                        <div v-else class="home-banner__slide-fallback">
                            <span class="material-symbols-outlined text-6xl opacity-40">storefront</span>
                        </div>
                        <div class="home-banner__slide-overlay" />
                    </div>

                    <!-- Contenido -->
                    <div class="home-banner__slide-content chamba-container max-w-6xl mx-auto px-5 md:px-8">
                        <p v-if="item.category_name" class="home-banner__eyebrow">{{ item.category_name }}</p>
                        <h2 class="home-banner__title">{{ item.title }}</h2>

                        <div class="home-banner__meta">
                            <span class="home-banner__provider">
                                <span class="material-symbols-outlined home-banner__meta-icon">storefront</span>
                                {{ item.provider_name }}
                            </span>
                            <span
                                v-if="item.district_name || item.province_name"
                                class="home-banner__location"
                            >
                                <span class="material-symbols-outlined home-banner__meta-icon">location_on</span>
                                {{ [item.district_name, item.province_name].filter(Boolean).join(', ') }}
                            </span>
                        </div>

                        <div class="home-banner__bottom">
                            <div class="home-banner__price-rating">
                                <p v-if="item.base_price != null" class="home-banner__price">
                                    <Money :amount="item.base_price" />
                                </p>
                                <span
                                    v-if="item.avg_rating && Number(item.total_reviews) > 0"
                                    class="home-banner__rating"
                                >
                                    <span class="material-symbols-outlined home-banner__star" style="font-variation-settings:'FILL' 1">star</span>
                                    {{ parseFloat(item.avg_rating).toFixed(1) }}
                                    <span class="home-banner__rating-count">({{ item.total_reviews }})</span>
                                </span>
                            </div>
                            <span class="home-banner__cta">
                                Ver anuncio
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                </RouterLink>
            </div>

            <!-- Controles de navegación -->
            <template v-if="hasMultiple">
                <button
                    type="button"
                    class="home-banner__nav home-banner__nav--prev"
                    aria-label="Anterior"
                    @click="goTo((activeIndex - 1 + items.length) % items.length)"
                >
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <button
                    type="button"
                    class="home-banner__nav home-banner__nav--next"
                    aria-label="Siguiente"
                    @click="nextSlide"
                >
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>
                <div class="home-banner__dots">
                    <button
                        v-for="(_, i) in items"
                        :key="i"
                        type="button"
                        class="home-banner__dot"
                        :class="i === activeIndex ? 'home-banner__dot--active' : ''"
                        :aria-label="`Ir al banner ${i + 1}`"
                        @click="goTo(i)"
                    />
                </div>
            </template>
        </div>
    </section>
</template>
