<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { scrollToHash } from '@/utils/scroll';
import { useAuthStore } from '@/stores/auth';
import { asset } from '@/utils/asset';
import AdminNavMenu from '@/components/layout/AdminNavMenu.vue';
import { buildAdminNav } from '@/utils/adminNav';
import { useUserNotificationsStore } from '@/stores/userNotifications';
import { useLoginModalStore } from '@/stores/loginModal';

const auth = useAuthStore();
const notifications = useUserNotificationsStore();
const loginModal = useLoginModalStore();
const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
const mobileNavOpen = ref(false);
const initials = computed(() => {
    const n = auth.user?.full_name?.trim();
    if (!n) return '?';
    const parts = n.split(/\s+/).filter(Boolean);
    const a = (parts[0]?.[0] || '').toUpperCase();
    const b = (parts.length > 1 ? parts[parts.length - 1]?.[0] : parts[0]?.[1] || '').toUpperCase();
    return (a + b).slice(0, 2) || '?';
});

const isAdmin = computed(() => auth.user?.role === 'admin');
const adminFlatLinks = computed(() => (isAdmin.value ? buildAdminNav().flatLinks : []));

const navLinks = computed(() => {
    if (isAdmin.value) {
        return [];
    }
    if (auth.isProveedor) {
        return [
            { name: 'provider-dashboard', label: 'Panel' },
            { name: 'provider-listings', label: 'Anuncios' },
            { name: 'provider-requests', label: 'Solicitudes' },
            { name: 'provider-subscription', label: 'Mi plan' },
            { name: 'support', label: 'Soporte' },
        ];
    }
    if (auth.isCliente) {
        return [
            { name: 'home', label: 'Explorar' },
            { name: 'client-favorites', label: 'Favoritos' },
            { name: 'client-requests', label: 'Solicitudes' },
            { name: 'support', label: 'Soporte' },
        ];
    }
    return [
        { name: 'home', label: 'Explorar' },
        { name: 'home', label: 'Cómo funciona', hash: '#como-funciona' },
    ];
});

watch(() => route.fullPath, () => {
    mobileNavOpen.value = false;
    menuOpen.value = false;
});

function isLinkActive(link) {
    if (link.hash) {
        return route.name === 'home' && route.hash === link.hash;
    }
    if (link.name === 'home' && !link.hash) {
        return route.name === 'home' && !route.hash;
    }
    return route.name === link.name;
}

function goNavLink(link, event) {
    if (!link.hash) return;
    event?.preventDefault();
    mobileNavOpen.value = false;
    if (route.name !== 'home') {
        router.push({ name: 'home', hash: link.hash }).then(() => scrollToHash(link.hash));
    } else {
        router.replace({ hash: link.hash });
        scrollToHash(link.hash);
    }
}

function openLogin() {
    mobileNavOpen.value = false;
    loginModal.showLogin(route.fullPath);
}

function navLinkClass(link) {
    return isLinkActive(link)
        ? 'text-slate-900 font-medium'
        : 'text-slate-600 hover:text-slate-900';
}
</script>

<template>
    <header class="sticky top-0 z-40 header-glass">
        <nav class="chamba-container h-16 flex items-center gap-3 justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    class="md:hidden flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                    aria-label="Menú"
                    @click="mobileNavOpen = !mobileNavOpen"
                >
                    <span class="material-symbols-outlined">{{ mobileNavOpen ? 'close' : 'menu' }}</span>
                </button>
                <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2.5 shrink-0 no-underline">
                    <img
                        :src="asset('img/chamba-icon.png')"
                        alt="Busca PE"
                        class="w-9 h-9 md:w-10 md:h-10 rounded-xl ring-1 ring-slate-200/80"
                    />
                    <span class="text-lg md:text-xl font-semibold tracking-tight text-slate-900">Busca PE</span>
                </RouterLink>
            </div>

            <AdminNavMenu v-if="isAdmin" />

            <div v-if="!isAdmin" class="hidden md:flex items-center gap-1 lg:gap-2">
                <template v-for="link in navLinks" :key="link.name + (link.hash || '')">
                    <a
                        v-if="link.hash"
                        :href="link.hash"
                        class="px-3 py-2 text-sm transition no-underline"
                        :class="navLinkClass(link)"
                        @click="goNavLink(link, $event)"
                    >
                        {{ link.label }}
                    </a>
                    <RouterLink
                        v-else
                        :to="{ name: link.name }"
                        class="px-3 py-2 text-sm transition no-underline relative"
                        :class="navLinkClass(link)"
                    >
                        {{ link.label }}
                        <span
                            v-if="
                                (link.name === 'provider-requests' || link.name === 'client-requests') &&
                                notifications.unreadCount > 0
                            "
                            class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-black flex items-center justify-center"
                        >{{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}</span>
                    </RouterLink>
                </template>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    v-if="!auth.isAuthenticated"
                    type="button"
                    class="rounded-lg bg-[#003874] text-white text-sm font-semibold px-4 py-2 hidden sm:inline-flex hover:bg-[#002e60] border-0 cursor-pointer"
                    @click="openLogin"
                >
                    Acceder
                </button>
                <button
                    v-if="!auth.isAuthenticated"
                    type="button"
                    class="sm:hidden flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 text-slate-700 bg-white"
                    aria-label="Acceder"
                    @click="openLogin"
                >
                    <span class="material-symbols-outlined text-[22px]">login</span>
                </button>
                <div v-else class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-full pl-1 pr-2 py-1 hover:bg-slate-100/80 transition border border-transparent hover:border-slate-200"
                        @click="menuOpen = !menuOpen"
                    >
                        <span class="relative">
                            <img
                                v-if="auth.avatarUrl"
                                :src="auth.avatarUrl"
                                alt="Avatar"
                                class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow-sm"
                            />
                            <span
                                v-else
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-grad-brand text-white font-bold text-sm shadow-sm"
                            >{{ initials }}</span>
                            <span
                                v-if="auth.isPro"
                                class="absolute -top-1 -right-1 text-[8px] font-black uppercase rounded-full px-1 py-0.5 text-white bg-grad-warm shadow"
                            >PRO</span>
                        </span>
                        <span class="hidden lg:flex flex-col items-start min-w-0 max-w-[160px]">
                            <span class="text-[10px] font-bold uppercase tracking-wide text-chamba-700">{{ auth.roleLabel }}</span>
                            <span class="text-sm font-semibold truncate text-slate-900 leading-tight">{{ auth.user?.full_name || '' }}</span>
                        </span>
                        <span class="material-symbols-outlined text-slate-400 text-[20px] hidden lg:inline">expand_more</span>
                    </button>
                    <div
                        v-if="menuOpen"
                        class="absolute right-0 mt-2 w-60 rounded-2xl border border-slate-200/90 bg-white shadow-xl shadow-slate-900/10 overflow-hidden z-50 py-1"
                        @click.stop
                    >
                        <div class="px-4 py-3 border-b border-slate-100 lg:hidden">
                            <p class="text-xs font-bold text-chamba-700 uppercase">{{ auth.roleLabel }}</p>
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ auth.user?.full_name }}</p>
                        </div>
                        <RouterLink
                            v-if="auth.user?.role === 'admin'"
                            :to="{ name: 'admin-dashboard' }"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 no-underline text-slate-800"
                            @click="menuOpen = false"
                        >
                            <span class="material-symbols-outlined text-[18px] text-slate-400">dashboard</span>
                            Panel admin
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'account' }"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 no-underline text-slate-800"
                            @click="menuOpen = false"
                        >
                            <span class="material-symbols-outlined text-[18px] text-slate-400">person</span>
                            Mi cuenta
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'support' }"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 no-underline text-slate-800"
                            @click="menuOpen = false"
                        >
                            <span class="material-symbols-outlined text-[18px] text-slate-400">support_agent</span>
                            Soporte / ayuda
                        </RouterLink>
                        <button
                            type="button"
                            class="w-full flex items-center gap-2 text-left px-4 py-2.5 text-sm hover:bg-rose-50 text-rose-700 font-semibold border-t border-slate-100 mt-1"
                            @click="async () => { menuOpen = false; await auth.logout(); $router.push({ name: 'home' }); }"
                        >
                            <span class="material-symbols-outlined text-[18px]">logout</span>
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Menú móvil -->
        <div
            v-if="mobileNavOpen"
            class="md:hidden border-t border-slate-200/80 bg-white/95 backdrop-blur-md px-4 py-3 max-h-[70vh] overflow-y-auto"
        >
            <div class="grid gap-1">
                <template v-if="isAdmin">
                    <RouterLink
                        v-for="link in adminFlatLinks"
                        :key="'m-admin-' + link.name"
                        :to="{ name: link.name }"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm no-underline hover:bg-slate-50"
                        :class="navLinkClass(link)"
                        @click="mobileNavOpen = false"
                    >
                        <span class="material-symbols-outlined text-[20px] opacity-70">chevron_right</span>
                        {{ link.label }}
                    </RouterLink>
                </template>
                <template v-for="link in navLinks" :key="'m-' + link.name + (link.hash || '')">
                    <a
                        v-if="link.hash"
                        :href="link.hash"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm no-underline hover:bg-slate-50"
                        :class="navLinkClass(link)"
                        @click="goNavLink(link, $event)"
                    >
                        <span class="material-symbols-outlined text-[20px] opacity-70">help</span>
                        {{ link.label }}
                    </a>
                    <RouterLink
                        v-else
                        :to="{ name: link.name }"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm no-underline hover:bg-slate-50"
                        :class="navLinkClass(link)"
                        @click="mobileNavOpen = false"
                    >
                        <span class="material-symbols-outlined text-[20px] opacity-70">chevron_right</span>
                        {{ link.label }}
                    </RouterLink>
                </template>
            </div>
        </div>
    </header>
</template>
