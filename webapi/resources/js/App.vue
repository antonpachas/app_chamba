<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppHeader from '@/components/layout/AppHeader.vue';
import AppFooter from '@/components/layout/AppFooter.vue';
import BottomNav from '@/components/layout/BottomNav.vue';

const route = useRoute();
const isPlain = computed(() => route.meta.layout === 'plain');
</script>

<template>
    <div class="chamba-app">
        <template v-if="isPlain">
            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </template>
        <template v-else>
            <div class="min-h-screen flex flex-col bg-[#f8f9ff] pb-[5.25rem] md:pb-0">
                <AppHeader />
                <main class="flex-1 w-full">
                    <router-view v-slot="{ Component }">
                        <transition name="fade" mode="out-in">
                            <component :is="Component" />
                        </transition>
                    </router-view>
                </main>
                <AppFooter />
                <BottomNav />
            </div>
        </template>
    </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 160ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
