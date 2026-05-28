<script setup>
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { asset } from '@/utils/asset';
import { scrollToHash } from '@/utils/scroll';

const year = new Date().getFullYear();
const route = useRoute();
const router = useRouter();

function goHowItWorks(event) {
    event.preventDefault();
    if (route.name !== 'home') {
        router.push({ name: 'home', hash: '#como-funciona' }).then(() => scrollToHash('#como-funciona'));
    } else {
        router.replace({ hash: '#como-funciona' });
        scrollToHash('#como-funciona');
    }
}
</script>

<template>
    <footer class="bg-grad-footer text-white mt-auto pt-14 pb-28 md:pb-12 border-t border-chamba-800">
        <div class="chamba-container grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-2.5 mb-4">
                    <img :src="asset('img/chamba-icon.png')" alt="" class="w-10 h-10 rounded-xl ring-2 ring-white/20 shadow-lg" />
                    <span class="text-2xl font-black tracking-tight">Busca PE</span>
                </div>
                <p class="text-white/75 text-sm leading-relaxed max-w-xs">
                    Directorio de negocios en Perú. Encuentra servicios cerca de ti o publica tu anuncio.
                </p>
            </div>
            <div>
                <h4 class="font-bold mb-4 uppercase text-[11px] tracking-[0.2em] text-white/60">Explorar</h4>
                <ul class="space-y-2.5 text-sm text-white/85">
                    <li>
                        <RouterLink :to="{ name: 'search' }" class="hover:text-white transition no-underline">Buscar anuncios</RouterLink>
                    </li>
                    <li>
                        <a href="#como-funciona" class="hover:text-white transition cursor-pointer no-underline" @click="goHowItWorks">Cómo funciona</a>
                    </li>
                    <li>
                        <RouterLink :to="{ name: 'register' }" class="hover:text-white transition no-underline">Publicar negocio</RouterLink>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4 uppercase text-[11px] tracking-[0.2em] text-white/60">Cuenta</h4>
                <ul class="space-y-2.5 text-sm text-white/85">
                    <li>
                        <RouterLink :to="{ name: 'login' }" class="hover:text-white transition no-underline">Iniciar sesión</RouterLink>
                    </li>
                    <li>
                        <RouterLink :to="{ name: 'register' }" class="hover:text-white transition no-underline">Crear cuenta</RouterLink>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4 uppercase text-[11px] tracking-[0.2em] text-white/60">Legal</h4>
                <p class="text-sm text-white/60 leading-relaxed">Términos y privacidad — próximamente.</p>
            </div>
        </div>
        <div class="chamba-container mt-12 pt-8 border-t border-white/15 flex flex-col md:flex-row justify-between gap-3 text-xs text-white/50">
            <p>© {{ year }} Busca PE · Perú</p>
            <p>Hecho para conectar negocios y clientes</p>
        </div>
    </footer>
</template>
