<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
    <section
        id="como-funciona"
        class="scroll-mt-24 mt-20 bg-white rounded-3xl p-8 md:p-12 border border-slate-100 relative overflow-hidden shadow-sm"
    >
        <div class="relative z-10 max-w-4xl mx-auto">
            <p class="text-center text-xs font-bold uppercase tracking-widest text-[#0ea5e9] mb-2">
                Directorio local
            </p>
            <h2 class="text-2xl md:text-3xl font-bold text-center text-[#0b1c30] mb-3">
                ¿Cómo funciona Busca PE?
            </h2>
            <p class="text-center text-slate-600 text-sm md:text-base max-w-2xl mx-auto mb-10">
                Busca negocios cerca de ti, revisa cada anuncio con su ubicación y horario, y contacta directo al negocio.
            </p>

            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 text-center mb-6">
                Si buscas un negocio
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 mb-12">
                <article
                    class="flex flex-col items-center text-center rounded-2xl border border-slate-100 bg-slate-50/60 p-6 hover:border-[#003874]/20 hover:shadow-md transition"
                >
                    <div
                        class="w-14 h-14 rounded-2xl bg-grad-brand text-white flex items-center justify-center mb-4 glow-blue"
                        aria-hidden="true"
                    >
                        <span class="material-symbols-outlined text-[28px]">travel_explore</span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#003874] mb-1">Paso 1</span>
                    <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Busca en tu zona</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Escribe qué necesitas, elige una <strong>categoría</strong> o filtra por departamento y distrito.
                        Usa <strong>«Cerca de mí»</strong> o el <strong>mapa</strong> para ver negocios alrededor.
                    </p>
                </article>

                <article
                    class="flex flex-col items-center text-center rounded-2xl border border-slate-100 bg-slate-50/60 p-6 hover:border-[#7c3aed]/20 hover:shadow-md transition"
                >
                    <div
                        class="w-14 h-14 rounded-2xl bg-grad-violet text-white flex items-center justify-center mb-4 shadow-lg shadow-[#7c3aed]/25"
                        aria-hidden="true"
                    >
                        <span class="material-symbols-outlined text-[28px]">storefront</span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#7c3aed] mb-1">Paso 2</span>
                    <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Revisa el anuncio</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Cada negocio publica <strong>fichas</strong> con fotos, precio, <strong>dirección en el mapa</strong>,
                        horario de atención y valoraciones. Inicia sesión para guardar favoritos.
                    </p>
                </article>

                <article
                    class="flex flex-col items-center text-center rounded-2xl border border-slate-100 bg-slate-50/60 p-6 hover:border-[#9f4200]/20 hover:shadow-md transition"
                >
                    <div
                        class="w-14 h-14 rounded-2xl bg-grad-warm text-white flex items-center justify-center mb-4 glow-coral"
                        aria-hidden="true"
                    >
                        <span class="material-symbols-outlined text-[28px]">forum</span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#9f4200] mb-1">Paso 3</span>
                    <h3 class="text-lg font-bold text-[#0b1c30] mb-2">Contacta al negocio</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Abre el detalle y coordina por <strong>WhatsApp</strong>, <strong>teléfono</strong> o envía una
                        <strong>solicitud</strong> desde la plataforma. Sin intermediarios en el pago del servicio.
                    </p>
                </article>
            </div>

            <div
                class="rounded-2xl border border-dashed border-[#003874]/25 bg-[#003874]/[0.04] px-5 py-6 md:px-8 md:py-7"
            >
                <p class="text-xs font-bold uppercase tracking-widest text-[#003874] mb-4 text-center md:text-left">
                    Si tienes un negocio
                </p>
                <ul class="grid sm:grid-cols-3 gap-4 text-sm text-slate-700">
                    <li class="flex gap-3 items-start">
                        <span class="material-symbols-outlined text-[#003874] shrink-0 mt-0.5">badge</span>
                        <span>Crea tu perfil y publica <strong>fichas</strong> (presencia en el mapa o anuncios destacados).</span>
                    </li>
                    <li class="flex gap-3 items-start">
                        <span class="material-symbols-outlined text-[#003874] shrink-0 mt-0.5">pin_drop</span>
                        <span>Cada ficha lleva su <strong>ubicación y horario</strong>; no hace falta registrar sedes aparte.</span>
                    </li>
                    <li class="flex gap-3 items-start">
                        <span class="material-symbols-outlined text-[#003874] shrink-0 mt-0.5">trending_up</span>
                        <span>Aparece en búsquedas de tu zona y recibe contactos de clientes interesados.</span>
                    </li>
                </ul>
            </div>

            <div class="mt-10 text-center">
                <a
                    href="#buscar"
                    class="inline-flex items-center gap-2 btn-grad-primary px-8 py-3 rounded-full font-bold no-underline active:scale-[0.99]"
                >
                    <span class="material-symbols-outlined text-[20px]">search</span>
                    Ir al buscador
                </a>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-24 -right-24 w-72 h-72 bg-[#0ea5e9]/15 rounded-full blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-24 -left-24 w-72 h-72 bg-[#ff7a2b]/15 rounded-full blur-3xl" aria-hidden="true"></div>
    </section>

    <section class="mt-12 mb-4 rounded-3xl bg-grad-hero text-white px-6 py-10 md:px-12 md:py-12 text-center shadow-xl shadow-[#003874]/20">
        <p class="text-xs font-bold uppercase tracking-widest text-white/70 mb-2">Para negocios</p>
        <h2 class="text-2xl md:text-3xl font-black tracking-tight">Publica tu negocio en Busca PE</h2>
        <p class="mt-3 text-white/90 max-w-xl mx-auto text-sm md:text-base leading-relaxed">
            Publica fichas con fotos, dirección y horario. Plan Pro: más cupo de anuncios y fichas destacadas con mayor visibilidad.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <RouterLink
                :to="{ name: 'register', query: { cuenta: 'proveedor' } }"
                class="inline-flex btn-grad-warm px-8 py-3 rounded-full font-bold no-underline active:scale-[0.99]"
            >
                Publicar mi negocio
            </RouterLink>
            <RouterLink
                :to="{ name: 'login' }"
                class="inline-flex px-8 py-3 rounded-full font-bold border border-white/40 text-white hover:bg-white/10 no-underline transition"
            >
                Ya tengo cuenta
            </RouterLink>
        </div>
    </section>
</template>
