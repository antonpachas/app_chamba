<script setup>
import { RouterLink } from 'vue-router';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="bg-[#dce9ff]/60 border-t border-slate-200 pt-14 pb-32 md:pb-14 mt-4">
        <div class="max-w-7xl mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-4 gap-10">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <img src="/img/chamba-icon.png" alt="" class="w-9 h-9 rounded-lg shadow-md shadow-[#003874]/15 ring-1 ring-slate-200" />
                    <span class="text-2xl font-black tracking-tighter text-grad-brand">Chamba</span>
                </div>
                <p class="text-slate-600 text-sm leading-relaxed">
                    Talentos locales para tu hogar. Encuentra, compara y paga seguro con custodia.
                </p>
            </div>
            <div>
                <h4 class="font-bold text-[#0b1c30] mb-5 uppercase text-xs tracking-widest">Plataforma</h4>
                <ul class="space-y-3 text-sm text-slate-600">
                    <li>
                        <RouterLink :to="{ name: 'search' }" class="hover:text-[#003874]">Buscar servicios</RouterLink>
                    </li>
                    <li>
                        <RouterLink :to="{ name: 'home', hash: '#como-funciona' }" class="hover:text-[#003874]">
                            Cómo funciona
                        </RouterLink>
                    </li>
                    <li>
                        <RouterLink :to="{ name: 'login', query: { cuenta: 'proveedor' } }" class="hover:text-[#003874]">
                            Soy proveedor
                        </RouterLink>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-[#0b1c30] mb-5 uppercase text-xs tracking-widest">Soporte</h4>
                <ul class="space-y-3 text-sm text-slate-600">
                    <li>
                        <RouterLink :to="{ name: 'home' }" class="hover:text-[#003874]">Centro de ayuda</RouterLink>
                    </li>
                    <li><span class="text-slate-400">Términos y privacidad (próximamente)</span></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-[#0b1c30] mb-5 uppercase text-xs tracking-widest">Social</h4>
                <div class="flex gap-3 text-xs font-semibold">
                    <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200">FB</span>
                    <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-slate-200">IG</span>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 md:px-8 mt-12 pt-8 border-t border-slate-200/80 flex flex-col md:flex-row justify-between gap-4 text-xs text-slate-500">
            <p>© {{ year }} Chamba</p>
            <span>Web app</span>
        </div>
    </footer>
</template>
