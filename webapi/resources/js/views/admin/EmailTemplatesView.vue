<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const loading = ref(false);
const saving = ref(false);
const testing = ref(false);
const err = ref('');
const ok = ref('');
const testOk = ref('');
const testErr = ref('');

const activeTab = ref('smtp');
const testEmail = ref('');
const testEvent = ref('registro_cliente');

const smtp = reactive({});
const templates = reactive({});
const globalEnabled = ref(false);

const eventLabels = {
    registro_cliente: 'Registro de cliente',
    registro_proveedor: 'Registro de proveedor/negocio',
    recuperar_password: 'Recuperar contraseña',
    nuevo_contacto: 'Nuevo contacto (proveedor)',
};

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/email-templates', { auth: true });
        Object.assign(smtp, r.data.smtp);
        Object.assign(templates, r.data.templates);
        globalEnabled.value = r.data.global_enabled;
    } catch (e) {
        err.value = e.message || 'Error al cargar configuración.';
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value = true;
    err.value = '';
    ok.value = '';
    try {
        const settings = [];
        // SMTP settings
        for (const key of Object.keys(smtp)) {
            settings.push({ key, value: smtp[key].value });
        }
        // Templates
        for (const event of Object.keys(templates)) {
            settings.push({ key: templates[event].subject.key, value: templates[event].subject.value });
            settings.push({ key: templates[event].body.key, value: templates[event].body.value });
        }
        // Global enabled
        settings.push({ key: 'notifications.email_enabled', value: globalEnabled.value ? '1' : '0' });

        await api.put('/admin/settings', { settings }, { auth: true });
        ok.value = 'Configuración guardada correctamente.';
    } catch (e) {
        err.value = e.message || 'Error al guardar.';
    } finally {
        saving.value = false;
    }
}

async function sendTest() {
    if (!testEmail.value) return;
    testing.value = true;
    testOk.value = '';
    testErr.value = '';
    try {
        const r = await api.post('/admin/email-templates/test', {
            to: testEmail.value,
            event: testEvent.value,
        }, { auth: true });
        testOk.value = r.message || 'Correo enviado.';
    } catch (e) {
        testErr.value = e.message || 'Error al enviar correo de prueba.';
    } finally {
        testing.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <!-- Encabezado -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[#0b1c30]">Correos y Plantillas</h1>
                <p class="text-sm text-slate-500 mt-0.5">Configura el servidor SMTP y las plantillas de cada evento del sistema.</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <span class="text-sm text-slate-700 font-medium">Activar envío de correos</span>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="globalEnabled"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[#003874]/40"
                        :class="globalEnabled ? 'bg-[#003874]' : 'bg-slate-200'"
                        @click="globalEnabled = !globalEnabled"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                            :class="globalEnabled ? 'translate-x-6' : 'translate-x-1'"
                        />
                    </button>
                </label>
                <AppButton variant="primary" :loading="saving" @click="save">
                    Guardar todo
                </AppButton>
            </div>
        </div>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div v-if="loading" class="py-20 text-center text-slate-400">Cargando…</div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar de navegación -->
            <nav class="lg:col-span-1">
                <ul class="space-y-1">
                    <li>
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                            :class="activeTab === 'smtp' ? 'bg-[#003874] text-white' : 'text-slate-700 hover:bg-slate-100'"
                            @click="activeTab = 'smtp'"
                        >
                            <span class="material-symbols-outlined text-[18px]">dns</span>
                            Servidor SMTP
                        </button>
                    </li>
                    <li
                        v-for="(label, event) in eventLabels"
                        :key="event"
                    >
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                            :class="activeTab === event ? 'bg-[#003874] text-white' : 'text-slate-700 hover:bg-slate-100'"
                            @click="activeTab = event"
                        >
                            <span class="material-symbols-outlined text-[18px]">mail</span>
                            {{ label }}
                        </button>
                    </li>
                    <li class="pt-2 border-t border-slate-200 mt-2">
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                            :class="activeTab === 'test' ? 'bg-[#003874] text-white' : 'text-slate-700 hover:bg-slate-100'"
                            @click="activeTab = 'test'"
                        >
                            <span class="material-symbols-outlined text-[18px]">send</span>
                            Enviar prueba
                        </button>
                    </li>
                </ul>
            </nav>

            <!-- Contenido -->
            <div class="lg:col-span-3">

                <!-- SMTP -->
                <div v-if="activeTab === 'smtp'" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="font-bold text-lg text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-[#003874]">dns</span>
                        Configuración SMTP
                    </h2>
                    <p class="text-sm text-slate-500 -mt-2 mb-2">
                        Ingresa los datos de tu servidor de correo saliente. Soporta Gmail, Mailgun, SendGrid, etc.
                    </p>

                    <template v-for="(field, key) in smtp" :key="key">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">
                                {{ field.label }}
                            </label>
                            <p v-if="field.description" class="text-xs text-slate-400 mb-1">{{ field.description }}</p>
                            <input
                                v-model="smtp[key].value"
                                :type="key === 'mail.password' ? 'password' : 'text'"
                                :placeholder="key === 'mail.port' ? '587' : ''"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#003874]/30 focus:border-[#003874]/50"
                            />
                        </div>
                    </template>
                </div>

                <!-- Plantillas de eventos -->
                <div
                    v-for="(label, event) in eventLabels"
                    v-show="activeTab === event"
                    :key="event"
                    class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5"
                >
                    <h2 class="font-bold text-lg text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-[#003874]">mail_outline</span>
                        {{ label }}
                    </h2>

                    <div v-if="templates[event]">
                        <!-- Asunto -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Asunto del correo</label>
                            <p class="text-xs text-slate-400 mb-1.5">{{ templates[event].subject.description }}</p>
                            <input
                                v-model="templates[event].subject.value"
                                type="text"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#003874]/30"
                                placeholder="Asunto del correo…"
                            />
                        </div>

                        <!-- Cuerpo HTML -->
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Cuerpo HTML</label>
                            <p class="text-xs text-slate-400 mb-1.5">{{ templates[event].body.description }}</p>
                            <textarea
                                v-model="templates[event].body.value"
                                rows="12"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#003874]/30 resize-y"
                                placeholder="<p>Contenido HTML del correo…</p>"
                            />
                        </div>

                        <!-- Vista previa básica -->
                        <div class="mt-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Vista previa</p>
                            <div
                                class="rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-auto max-h-64"
                                v-html="templates[event].body.value || '<p class=\'text-slate-400\'>Sin contenido aún…</p>'"
                            />
                        </div>
                    </div>
                </div>

                <!-- Enviar correo de prueba -->
                <div v-if="activeTab === 'test'" class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                    <h2 class="font-bold text-lg text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-[#003874]">send</span>
                        Enviar correo de prueba
                    </h2>
                    <p class="text-sm text-slate-500">Verifica que el SMTP esté correctamente configurado enviando un correo de prueba.</p>

                    <AppAlert v-if="testOk" type="success">{{ testOk }}</AppAlert>
                    <AppAlert v-if="testErr" type="error">{{ testErr }}</AppAlert>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Correo destino</label>
                        <input
                            v-model="testEmail"
                            type="email"
                            placeholder="destinatario@ejemplo.com"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#003874]/30"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Plantilla a enviar</label>
                        <select
                            v-model="testEvent"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#003874]/30"
                        >
                            <option v-for="(label, event) in eventLabels" :key="event" :value="event">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <AppButton
                        variant="primary"
                        :loading="testing"
                        :disabled="!testEmail"
                        @click="sendTest"
                    >
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        Enviar correo de prueba
                    </AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
