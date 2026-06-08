<script setup>
import { usePageMeta } from '@/utils/usePageMeta';
const { setMeta } = usePageMeta();
setMeta({
    title: 'Términos y Condiciones',
    description: 'Términos y Condiciones de uso de BuscaPE, directorio de negocios y servicios en Perú.',
});
</script>

<template>
    <div class="chamba-container max-w-4xl mx-auto px-4 md:px-6 py-10 md:py-14">
        <h1 class="text-3xl font-extrabold text-slate-900 mb-2">Términos y Condiciones</h1>
        <p class="text-sm text-slate-500 mb-10">Última actualización: junio de 2026</p>

        <div class="prose prose-slate max-w-none space-y-8 text-[15px] leading-relaxed">

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">1. Aceptación de los términos</h2>
                <p>Al acceder y utilizar <strong>BuscaPE</strong> (en adelante, "la Plataforma"), usted acepta quedar vinculado por los presentes Términos y Condiciones. Si no está de acuerdo con alguna de las disposiciones aquí establecidas, le rogamos que se abstenga de utilizar la Plataforma.</p>
                <p class="mt-2">BuscaPE es un directorio digital que conecta a usuarios que buscan negocios y servicios locales en el Perú con proveedores que ofrecen dichos servicios.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">2. Definiciones</h2>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Plataforma:</strong> el sitio web BuscaPE y todos sus servicios asociados.</li>
                    <li><strong>Usuario:</strong> toda persona que acceda a la Plataforma, con o sin registro.</li>
                    <li><strong>Cliente:</strong> usuario registrado que busca y contacta negocios.</li>
                    <li><strong>Proveedor / Negocio:</strong> usuario registrado que publica anuncios de sus servicios o productos.</li>
                    <li><strong>Anuncio:</strong> ficha de presentación de un negocio o servicio publicada por un Proveedor.</li>
                    <li><strong>Solicitud:</strong> mensaje o pedido de contacto enviado por un Cliente a un Proveedor.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">3. Registro de cuenta</h2>
                <p>Para acceder a las funcionalidades completas de la Plataforma, el Usuario debe registrarse proporcionando información veraz, completa y actualizada. Cada persona puede tener una sola cuenta activa. Está prohibido crear cuentas falsas, duplicadas o en nombre de terceros sin autorización.</p>
                <p class="mt-2">El Usuario es responsable de mantener la confidencialidad de sus credenciales de acceso y de todas las actividades realizadas bajo su cuenta. Ante cualquier uso no autorizado, debe notificarnos de inmediato.</p>
                <p class="mt-2">Los menores de 18 años no pueden registrarse ni utilizar la Plataforma sin el consentimiento expreso de sus padres o tutores legales.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">4. Publicación de anuncios (Proveedores)</h2>
                <p>Los Proveedores pueden publicar anuncios de sus negocios y servicios sujeto a las siguientes condiciones:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>La información publicada debe ser veraz, precisa y actualizada.</li>
                    <li>Las imágenes deben ser propias o contar con los derechos de uso correspondientes.</li>
                    <li>Está prohibido publicar contenido engañoso, difamatorio, obsceno, ilegal o que infrinja derechos de terceros.</li>
                    <li>No se permite publicar anuncios de actividades ilegales, productos prohibidos o servicios que contravengan la normativa peruana vigente.</li>
                    <li>BuscaPE se reserva el derecho de rechazar, modificar o eliminar cualquier anuncio que incumpla estas condiciones, sin previo aviso ni responsabilidad.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">5. Uso del servicio de contacto y mensajería</h2>
                <p>El sistema de solicitudes y mensajería de BuscaPE tiene como único fin facilitar el contacto entre Clientes y Proveedores. Está expresamente prohibido:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>Enviar spam, mensajes masivos o publicidad no solicitada.</li>
                    <li>Realizar acoso, amenazas o comunicaciones de carácter ilícito.</li>
                    <li>Compartir información personal de terceros sin su consentimiento.</li>
                    <li>Usar el sistema para fines distintos a la coordinación del servicio.</li>
                </ul>
                <p class="mt-2">BuscaPE actúa como intermediario tecnológico y no es parte de los acuerdos comerciales entre Clientes y Proveedores. No garantiza la calidad, legalidad ni cumplimiento de los servicios ofrecidos.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">6. Conducta del Usuario</h2>
                <p>El Usuario se compromete a no:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>Interferir con el funcionamiento normal de la Plataforma.</li>
                    <li>Intentar acceder sin autorización a sistemas, datos o cuentas ajenas.</li>
                    <li>Usar scripts automáticos, bots o herramientas de scraping sin autorización escrita.</li>
                    <li>Reproducir, distribuir o explotar comercialmente el contenido de la Plataforma sin autorización.</li>
                    <li>Suplantar la identidad de otras personas o entidades.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">7. Propiedad intelectual</h2>
                <p>Todos los elementos de la Plataforma — incluyendo diseño, logotipo, código fuente, textos e interfaz — son propiedad de BuscaPE o de sus licenciantes y están protegidos por las leyes de propiedad intelectual del Perú y convenios internacionales.</p>
                <p class="mt-2">El Proveedor otorga a BuscaPE una licencia no exclusiva, gratuita y mundial para mostrar el contenido de sus anuncios (textos, imágenes) dentro de la Plataforma.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">8. Disponibilidad del servicio</h2>
                <p>BuscaPE no garantiza la disponibilidad continua e ininterrumpida de la Plataforma. Podemos realizar mantenimientos, actualizaciones o suspensiones temporales sin previo aviso. No seremos responsables por pérdidas o daños derivados de la interrupción del servicio.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">9. Limitación de responsabilidad</h2>
                <p>BuscaPE no se responsabiliza por:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>El incumplimiento de acuerdos entre Clientes y Proveedores.</li>
                    <li>La veracidad de la información publicada por los Proveedores.</li>
                    <li>Daños directos, indirectos, incidentales o consecuentes derivados del uso o imposibilidad de uso de la Plataforma.</li>
                    <li>Contenido publicado por terceros.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">10. Suspensión y cancelación de cuenta</h2>
                <p>BuscaPE puede suspender o cancelar la cuenta de cualquier Usuario que incumpla estos Términos, sin previo aviso ni responsabilidad. El Usuario también puede solicitar la eliminación de su cuenta en cualquier momento mediante los canales de soporte.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">11. Modificaciones</h2>
                <p>Nos reservamos el derecho de modificar estos Términos en cualquier momento. Las modificaciones entrarán en vigor desde su publicación en la Plataforma. El uso continuado del servicio tras la publicación implica la aceptación de los nuevos Términos.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">12. Ley aplicable y jurisdicción</h2>
                <p>Los presentes Términos se rigen por las leyes de la República del Perú. Para cualquier controversia, las partes se someten a la jurisdicción de los tribunales competentes de la ciudad de Lima, renunciando expresamente a cualquier otro fuero.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">13. Contacto</h2>
                <p>Para cualquier consulta sobre estos Términos, puedes contactarnos a través de la sección de <RouterLink to="/soporte" class="text-[#003874] font-semibold hover:underline">Soporte</RouterLink> de la Plataforma.</p>
            </section>

        </div>
    </div>
</template>
