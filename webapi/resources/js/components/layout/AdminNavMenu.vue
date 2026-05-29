<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { buildAdminNav, isAdminGroupActive } from '@/utils/adminNav';

const route = useRoute();
const { groups, flatLinks } = buildAdminNav();
const openGroupId = ref(null);

watch(
    () => route.name,
    (name) => {
        openGroupId.value = null;
        const active = groups.find((g) => isAdminGroupActive(g, name));
        if (active && active.links.length > 1) {
            openGroupId.value = active.id;
        }
    },
    { immediate: true },
);

function toggleGroup(id) {
    openGroupId.value = openGroupId.value === id ? null : id;
}

function linkClass(name) {
    return route.name === name
        ? 'text-chamba-700 bg-chamba-50 border-chamba-200'
        : 'text-slate-600 border-transparent hover:text-chamba-700 hover:bg-slate-50';
}

const mobileLinks = computed(() => flatLinks);
</script>

<template>
    <!-- Desktop: grupos con dropdown -->
    <div class="hidden md:flex items-center gap-1 lg:gap-1.5">
        <template v-for="group in groups" :key="group.id">
            <RouterLink
                v-if="group.links.length === 1"
                :to="{ name: group.links[0].name }"
                class="px-2.5 lg:px-3 py-2 rounded-xl text-sm font-semibold transition no-underline border"
                :class="linkClass(group.links[0].name)"
            >
                {{ group.links[0].label }}
            </RouterLink>
            <div v-else class="relative">
                <button
                    type="button"
                    class="flex items-center gap-0.5 px-2.5 lg:px-3 py-2 rounded-xl text-sm font-semibold transition border"
                    :class="
                        isAdminGroupActive(group, route.name)
                            ? 'text-chamba-700 bg-chamba-50 border-chamba-200'
                            : 'text-slate-600 border-transparent hover:text-chamba-700 hover:bg-slate-50'
                    "
                    @click="toggleGroup(group.id)"
                >
                    {{ group.label }}
                    <span class="material-symbols-outlined text-[18px]">
                        {{ openGroupId === group.id ? 'expand_less' : 'expand_more' }}
                    </span>
                </button>
                <div
                    v-if="openGroupId === group.id"
                    class="absolute left-0 top-full mt-1 min-w-[11rem] rounded-xl border border-slate-200 bg-white shadow-lg py-1 z-50"
                >
                    <RouterLink
                        v-for="link in group.links"
                        :key="link.name"
                        :to="{ name: link.name }"
                        class="block px-4 py-2.5 text-sm font-medium no-underline hover:bg-slate-50"
                        :class="route.name === link.name ? 'text-chamba-700 bg-chamba-50/50' : 'text-slate-700'"
                        @click="openGroupId = null"
                    >
                        {{ link.label }}
                    </RouterLink>
                </div>
            </div>
        </template>
    </div>

    <!-- Móvil: lista plana (slot del padre) -->
    <slot name="mobile" :links="mobileLinks" :link-class="linkClass" />
</template>
