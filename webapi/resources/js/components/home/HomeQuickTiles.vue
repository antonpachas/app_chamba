<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useLoginModalStore } from '@/stores/loginModal';
import { scrollToHash } from '@/utils/scroll';

const auth = useAuthStore();
const loginModal = useLoginModalStore();
const route = useRoute();
const router = useRouter();

const tiles = computed(() => {
    const base = [
        {
            id: 'explore',
            hash: '#categorias',
            icon: 'travel_explore',
            title: 'Explorar categorías',
            subtitle: 'Encuentra por rubro',
            tone: 'blue',
        },
        {
            id: 'publish',
            to: auth.isProveedor
                ? { name: 'provider-listings' }
                : { name: 'register', query: { cuenta: 'proveedor' } },
            icon: 'storefront',
            title: 'Publicar negocio',
            subtitle: 'Crea tu ficha',
            tone: 'teal',
        },
    ];

    if (!auth.isAuthenticated) {
        base.push({
            id: 'login',
            action: 'login',
            icon: 'login',
            title: 'Inicia sesión',
            subtitle: 'Guarda favoritos',
            tone: 'violet',
        });
    } else if (auth.isCliente) {
        base.push({
            id: 'favorites',
            to: { name: 'client-favorites' },
            icon: 'favorite',
            title: 'Mis favoritos',
            subtitle: 'Anuncios guardados',
            tone: 'violet',
        });
    } else if (auth.isProveedor) {
        base.push({
            id: 'panel',
            to: { name: 'provider-dashboard' },
            icon: 'dashboard',
            title: 'Mi panel',
            subtitle: 'Gestiona anuncios',
            tone: 'violet',
        });
    }

    base.push(
        {
            id: 'how',
            hash: '#como-funciona',
            icon: 'help',
            title: 'Cómo funciona',
            subtitle: 'Guía rápida',
            tone: 'amber',
        },
        {
            id: 'suggest',
            hash: '#categorias',
            icon: 'category',
            title: 'Sugerir rubro',
            subtitle: '¿Falta una categoría?',
            tone: 'slate',
        },
    );

    return base;
});

function goHash(hash) {
    if (route.name !== 'home') {
        router.push({ name: 'home', hash }).then(() => scrollToHash(hash));
    } else {
        router.replace({ hash });
        scrollToHash(hash);
    }
}

function onTileClick(tile, event) {
    if (tile.action === 'login') {
        event.preventDefault();
        loginModal.showLogin();
        return;
    }
    if (tile.hash) {
        event.preventDefault();
        goHash(tile.hash);
    }
}
</script>

<template>
    <section class="home-tiles" aria-label="Accesos rápidos">
        <div class="chamba-container max-w-6xl mx-auto px-4 md:px-6">
            <div class="home-tiles__grid">
                <component
                    :is="tile.to ? RouterLink : 'button'"
                    v-for="tile in tiles"
                    :key="tile.id"
                    :to="tile.to"
                    type="button"
                    class="home-tile"
                    :class="`home-tile--${tile.tone}`"
                    @click="onTileClick(tile, $event)"
                >
                    <span class="home-tile__icon material-symbols-outlined" aria-hidden="true">{{ tile.icon }}</span>
                    <span class="home-tile__text">
                        <span class="home-tile__title">{{ tile.title }}</span>
                        <span class="home-tile__subtitle">{{ tile.subtitle }}</span>
                    </span>
                </component>
            </div>
        </div>
    </section>
</template>
