<script setup>
import { usePageMeta } from '@/utils/usePageMeta';
const { setMeta } = usePageMeta();
setMeta({
    title: 'Política de Privacidad',
    description: 'Política de Privacidad de BuscaPE. Conoce cómo recopilamos, usamos y protegemos tus datos personales.',
});
</script>

<template>
    <div class="chamba-container max-w-4xl mx-auto px-4 md:px-6 py-10 md:py-14">
        <h1 class="text-3xl font-extrabold text-slate-900 mb-2">Política de Privacidad</h1>
        <p class="text-sm text-slate-500 mb-10">Última actualización: junio de 2026</p>

        <div class="prose prose-slate max-w-none space-y-8 text-[15px] leading-relaxed">

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">1. Responsable del tratamiento</h2>
                <p><strong>BuscaPE</strong> es responsable del tratamiento de los datos personales que usted nos proporciona al utilizar nuestra Plataforma. Operamos de conformidad con la <strong>Ley N° 29733 – Ley de Protección de Datos Personales</strong> del Perú y su Reglamento, aprobado por Decreto Supremo N° 003-2013-JUS.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">2. Datos que recopilamos</h2>
                <p>Recopilamos los siguientes tipos de datos personales:</p>

                <h3 class="text-base font-semibold text-slate-700 mt-4 mb-2">2.1 Datos que usted nos proporciona directamente</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Registro:</strong> nombre completo, correo electrónico, contraseña, teléfono y rol (Cliente o Proveedor).</li>
                    <li><strong>Perfil de negocio (Proveedores):</strong> nombre del negocio, descripción, dirección, fotografías, categoría, precio, horarios y datos de contacto (WhatsApp, teléfono).</li>
                    <li><strong>Mensajes:</strong> el contenido de las solicitudes y mensajes enviados a través de la Plataforma.</li>
                    <li><strong>Soporte:</strong> información proporcionada en tickets de atención al cliente.</li>
                    <li><strong>Foto de perfil (avatar):</strong> imagen que el usuario sube voluntariamente.</li>
                </ul>

                <h3 class="text-base font-semibold text-slate-700 mt-4 mb-2">2.2 Datos que recopilamos automáticamente</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Datos de uso:</strong> páginas visitadas, búsquedas realizadas, categorías consultadas, interacciones con anuncios.</li>
                    <li><strong>Datos técnicos:</strong> dirección IP, tipo de navegador, sistema operativo, proveedor de internet y fecha/hora de acceso.</li>
                    <li><strong>Ubicación geográfica:</strong> cuando usted autoriza el acceso a su ubicación para la función "Cerca de mí" (dato aproximado, no almacenado de forma permanente).</li>
                    <li><strong>Cookies y tecnologías similares:</strong> ver sección 7.</li>
                </ul>

                <h3 class="text-base font-semibold text-slate-700 mt-4 mb-2">2.3 Datos de terceros (Google)</h3>
                <p>Si se registra o inicia sesión mediante <strong>Google OAuth</strong>, recibimos de Google su nombre, dirección de correo electrónico y foto de perfil, con base en los permisos que usted otorgue. No accedemos a contraseñas ni a otra información de su cuenta de Google.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">3. Finalidades del tratamiento</h2>
                <p>Utilizamos sus datos para:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>Crear y gestionar su cuenta de usuario.</li>
                    <li>Mostrar y gestionar anuncios de negocios en la Plataforma.</li>
                    <li>Facilitar el contacto y mensajería entre Clientes y Proveedores.</li>
                    <li>Enviar correos electrónicos transaccionales (bienvenida, recuperación de contraseña, notificaciones de solicitudes).</li>
                    <li>Personalizar los resultados de búsqueda según ubicación.</li>
                    <li>Mejorar la experiencia de usuario y el funcionamiento de la Plataforma.</li>
                    <li>Cumplir con obligaciones legales y resolver disputas.</li>
                    <li>Moderar contenido y prevenir fraudes.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">4. Base legal del tratamiento</h2>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Ejecución del contrato:</strong> el tratamiento es necesario para prestar los servicios de la Plataforma conforme a nuestros Términos y Condiciones.</li>
                    <li><strong>Consentimiento:</strong> para el envío de comunicaciones opcionales y el tratamiento de datos de ubicación.</li>
                    <li><strong>Interés legítimo:</strong> para mejorar la seguridad, prevenir fraudes y mejorar el servicio.</li>
                    <li><strong>Cumplimiento legal:</strong> cuando la ley peruana lo exija.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">5. Compartición de datos con terceros</h2>
                <p>BuscaPE <strong>no vende ni alquila</strong> sus datos personales a terceros. Podemos compartir información en los siguientes casos:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li><strong>Proveedores de servicios tecnológicos:</strong> empresas que nos ayudan a operar la Plataforma (hosting, correo electrónico, procesamiento de imágenes), bajo acuerdos de confidencialidad.</li>
                    <li><strong>Google LLC:</strong> a través de Google OAuth para autenticación y Google AdSense para publicidad. Google tiene su propia política de privacidad en <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-[#003874] hover:underline">policies.google.com/privacy</a>.</li>
                    <li><strong>Autoridades competentes:</strong> cuando lo exija una orden judicial o disposición legal vigente en el Perú.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">6. Contenido público de los anuncios</h2>
                <p>Los datos que los Proveedores incluyan en sus anuncios (nombre del negocio, descripción, imágenes, ubicación, teléfono de contacto, WhatsApp) son <strong>visibles públicamente</strong> para todos los visitantes de la Plataforma, incluidos motores de búsqueda. El Proveedor acepta esta visibilidad al publicar su anuncio.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">7. Cookies y tecnologías de seguimiento</h2>
                <p>Utilizamos cookies y tecnologías similares para:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li><strong>Cookies esenciales:</strong> necesarias para el funcionamiento de la Plataforma (sesión, autenticación).</li>
                    <li><strong>Cookies de análisis:</strong> para entender cómo se usa la Plataforma y mejorarla.</li>
                    <li><strong>Cookies de publicidad (Google AdSense):</strong> Google usa cookies para mostrar anuncios relevantes. Puede gestionar sus preferencias en <a href="https://adssettings.google.com" target="_blank" rel="noopener" class="text-[#003874] hover:underline">adssettings.google.com</a>.</li>
                </ul>
                <p class="mt-2">Puede configurar su navegador para rechazar cookies, aunque esto puede afectar el funcionamiento de algunas funciones de la Plataforma.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">8. Conservación de datos</h2>
                <p>Conservamos sus datos personales mientras su cuenta esté activa o sea necesario para prestar el servicio. Al eliminar su cuenta, eliminaremos sus datos personales en un plazo máximo de <strong>30 días hábiles</strong>, salvo aquellos que debamos conservar por obligaciones legales.</p>
                <p class="mt-2">Los mensajes y solicitudes pueden conservarse durante un período adicional por razones de seguridad y resolución de disputas.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">9. Sus derechos (Ley N° 29733)</h2>
                <p>Como titular de datos personales, usted tiene los siguientes derechos:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li><strong>Acceso:</strong> conocer qué datos tenemos sobre usted.</li>
                    <li><strong>Rectificación:</strong> corregir datos inexactos o incompletos.</li>
                    <li><strong>Cancelación / Supresión:</strong> solicitar la eliminación de sus datos.</li>
                    <li><strong>Oposición:</strong> oponerse al tratamiento de sus datos en determinados casos.</li>
                </ul>
                <p class="mt-2">Para ejercer cualquiera de estos derechos, puede actualizar su información directamente en la Plataforma (sección "Mi cuenta") o contactarnos a través de <RouterLink to="/soporte" class="text-[#003874] font-semibold hover:underline">Soporte</RouterLink>.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">10. Seguridad de los datos</h2>
                <p>Implementamos medidas técnicas y organizativas para proteger sus datos personales contra accesos no autorizados, pérdida o destrucción. Entre ellas: cifrado de contraseñas, conexiones HTTPS, control de acceso por roles y copias de seguridad periódicas.</p>
                <p class="mt-2">Sin embargo, ningún sistema es completamente seguro. Si detectamos una brecha de seguridad que afecte sus datos, le notificaremos conforme lo exige la ley peruana.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">11. Menores de edad</h2>
                <p>La Plataforma no está dirigida a menores de 18 años. No recopilamos conscientemente datos de menores. Si tiene conocimiento de que un menor ha proporcionado datos personales sin consentimiento parental, contáctenos para proceder a su eliminación.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">12. Cambios a esta Política</h2>
                <p>Podemos actualizar esta Política de Privacidad periódicamente. Le notificaremos los cambios significativos mediante un aviso visible en la Plataforma o por correo electrónico. El uso continuado del servicio tras la notificación implica la aceptación de los cambios.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-3">13. Contacto</h2>
                <p>Para cualquier consulta, solicitud o reclamo relacionado con el tratamiento de sus datos personales, puede contactarnos a través de la sección de <RouterLink to="/soporte" class="text-[#003874] font-semibold hover:underline">Soporte</RouterLink> de la Plataforma.</p>
                <p class="mt-2">También puede presentar una queja ante la <strong>Autoridad Nacional de Protección de Datos Personales</strong> del Ministerio de Justicia y Derechos Humanos del Perú.</p>
            </section>

        </div>
    </div>
</template>
