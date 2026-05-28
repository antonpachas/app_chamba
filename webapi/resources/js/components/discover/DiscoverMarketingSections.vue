<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
    <section
        id="como-funciona"
        class="scroll-mt-24 mt-20 bg-white rounded-3xl p-8 md:p-12 border border-slate-100 relative overflow-hidden shadow-sm"
    >
        <div class="relative z-10">
            <p class="text-center text-xs font-bold uppercase tracking-widest text-[#0ea5e9] mb-2">3 pasos</p>
            <h2 class="text-2xl md:text-3xl font-bold text-center text-[#0b1c30] mb-10">¿Cómo funciona Busca PE?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-2xl bg-grad-brand text-white flex items-center justify-center font-black text-xl mb-4 glow-blue">1</div>
                    <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Busca cerca de ti</h3>
                    <p class="text-slate-600 text-sm">Filtra por rubro y zona o usa «Cerca de mí» con tu GPS.</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-2xl bg-grad-violet text-white flex items-center justify-center font-black text-xl mb-4 shadow-lg shadow-[#7c3aed]/30">2</div>
                    <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Revisa el anuncio</h3>
                    <p class="text-slate-600 text-sm">Mira fotos, sedes, horarios y valoraciones antes de decidir.</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-2xl bg-grad-warm text-white flex items-center justify-center font-black text-xl mb-4 glow-coral">3</div>
                    <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Contacta directo</h3>
                    <p class="text-slate-600 text-sm">Coordina con el negocio por WhatsApp, teléfono o como prefieran.</p>
                </div>
            </div>
            <div class="mt-10 text-center">
                <a
                    href="#buscar"
                    class="inline-flex btn-grad-primary px-8 py-3 rounded-full font-bold no-underline active:scale-[0.99]"
                >
                    Ir al buscador
                </a>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-24 -right-24 w-72 h-72 bg-[#0ea5e9]/15 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute -top-24 -left-24 w-72 h-72 bg-[#ff7a2b]/15 rounded-full blur-3xl"></div>
    </section>

    <section class="mt-12 mb-4 rounded-3xl bg-grad-hero text-white px-6 py-10 md:px-12 md:py-12 text-center shadow-xl shadow-[#003874]/20">
        <p class="text-xs font-bold uppercase tracking-widest text-white/70 mb-2">Para negocios</p>
        <h2 class="text-2xl md:text-3xl font-black tracking-tight">Publica tu anuncio en Busca PE</h2>
        <p class="mt-3 text-white/90 max-w-lg mx-auto text-sm md:text-base">
            Llega a quien busca en tu zona. Planes con más visibilidad y cupo de anuncios activos.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <RouterLink
                :to="{ name: 'login' }"
                class="inline-flex btn-grad-warm px-8 py-3 rounded-full font-bold no-underline active:scale-[0.99]"
            >
                Publicar mi negocio
            </RouterLink>
            <RouterLink
                :to="{ name: 'register' }"
                class="inline-flex px-8 py-3 rounded-full font-bold border border-white/40 text-white hover:bg-white/10 no-underline transition"
            >
                Crear cuenta gratis
            </RouterLink>
        </div>
    </section>
</template>
