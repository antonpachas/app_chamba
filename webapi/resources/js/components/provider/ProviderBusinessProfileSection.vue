<script setup>
import { onMounted, ref, watch } from 'vue';
import { useProviderProfileStore } from '@/stores/providerProfile';
import { useGeoStore } from '@/stores/geo';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import BusinessHoursEditor from '@/components/provider/BusinessHoursEditor.vue';

defineProps({
    /** Sin cabecera/card externa — para uso dentro de tabs en Mi cuenta */
    embedded: { type: Boolean, default: false },
});

const store = useProviderProfileStore();
const geo = useGeoStore();

const form = ref({
    business_name: '',
    razon_social: '',
    ruc: '',
    description: '',
    whatsapp: '',
    contact_phone: '',
    address_text: '',
    district_id: null,
    business_hours: null,
});
const department_id = ref(null);
const province_id = ref(null);
const saving = ref(false);
const coverBusy = ref(false);
const coverInput = ref(null);
const errMsg = ref('');
const okMsg = ref('');
const loaded = ref(false);

async function preselectGeoFromProfile() {
    if (!store.profile?.district) return;
    if (store.profile.district.province?.department?.id) {
        department_id.value = store.profile.district.province.department.id;
        await geo.setDepartment(department_id.value);
    }
    if (store.profile.district.province?.id) {
        province_id.value = store.profile.district.province.id;
        await geo.setProvince(province_id.value);
    }
    form.value.district_id = store.profile.district.id;
}

async function loadSection() {
    await Promise.all([store.loadProfile(), geo.ensureDepartments()]);
    try {
        const pending = sessionStorage.getItem('chamba_pending_business_legal');
        if (pending) {
            const p = JSON.parse(pending);
            if (p?.razon_social) form.value.razon_social = p.razon_social;
            if (p?.ruc) form.value.ruc = p.ruc;
            sessionStorage.removeItem('chamba_pending_business_legal');
        }
    } catch {
        /* ignore */
    }
    if (store.profile) {
        form.value = {
            business_name: store.profile.business_name || '',
            razon_social: store.profile.razon_social || '',
            ruc: store.profile.ruc || '',
            description: store.profile.description || '',
            whatsapp: store.profile.whatsapp || '',
            contact_phone: store.profile.contact_phone || '',
            address_text: store.profile.address_text || '',
            district_id: store.profile.district?.id || null,
            business_hours: store.profile.business_hours || null,
        };
        await preselectGeoFromProfile();
    }
    loaded.value = true;
}

onMounted(() => {
    void loadSection();
});

watch(department_id, async (v) => {
    await geo.setDepartment(v || null);
    province_id.value = null;
    form.value.district_id = null;
});
watch(province_id, async (v) => {
    await geo.setProvince(v || null);
    form.value.district_id = null;
});

async function onCoverPick(e) {
    const f = e.target.files?.[0];
    if (e.target) e.target.value = '';
    if (!f || !store.profile) return;
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
        errMsg.value = 'Solo JPG, PNG o WEBP para la portada.';
        return;
    }
    coverBusy.value = true;
    errMsg.value = '';
    try {
        await store.uploadCover(f);
        okMsg.value = 'Imagen de fondo actualizada.';
    } catch (ex) {
        errMsg.value = ex.message || 'No se pudo subir la portada.';
    } finally {
        coverBusy.value = false;
    }
}

async function removeCover() {
    if (!store.profile?.cover_url) return;
    coverBusy.value = true;
    try {
        await store.removeCover();
        okMsg.value = 'Portada eliminada.';
    } catch (ex) {
        errMsg.value = ex.message || 'No se pudo quitar la portada.';
    } finally {
        coverBusy.value = false;
    }
}

async function save() {
    errMsg.value = '';
    okMsg.value = '';
    if (!form.value.district_id) {
        errMsg.value = 'Selecciona departamento, provincia y distrito.';
        return;
    }
    if (!form.value.razon_social?.trim()) {
        errMsg.value = 'La razón social es obligatoria.';
        return;
    }
    if (form.value.ruc && !/^\d{11}$/.test(form.value.ruc.trim())) {
        errMsg.value = 'El RUC debe tener 11 dígitos numéricos.';
        return;
    }
    saving.value = true;
    try {
        await store.saveProfile(form.value);
        okMsg.value = 'Perfil del negocio guardado.';
    } catch (e) {
        errMsg.value = e.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <section
        id="perfil-negocio"
        class="scroll-mt-24 overflow-hidden"
        :class="embedded ? 'rounded-3xl border border-slate-200/90 bg-white shadow-sm' : 'rounded-3xl border border-slate-200 bg-white shadow-sm'"
    >
        <header
            class="px-6 md:px-8 py-6 border-b border-slate-100"
            :class="embedded ? 'bg-gradient-to-r from-[#003874]/5 via-sky-50/50 to-white' : 'bg-slate-50/80'"
        >
            <h2 class="text-lg font-bold text-[#0b1c30] flex items-center gap-2">
                <span class="material-symbols-outlined text-[#003874]">storefront</span>
                Perfil del negocio
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">Lo que ven los clientes en tu ficha pública y en tus anuncios.</p>
        </header>

        <div class="p-6 md:p-8 space-y-6">
            <AppAlert v-if="errMsg" type="error">{{ errMsg }}</AppAlert>
            <AppAlert v-if="okMsg" type="success">{{ okMsg }}</AppAlert>
            <p v-if="!loaded" class="text-slate-500 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined animate-spin text-[#003874]">progress_activity</span>
                Cargando perfil del negocio…
            </p>

            <template v-else-if="store.profile">
                <!-- Portada -->
                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                    <div
                        class="relative h-36 md:h-44 bg-grad-hero bg-cover bg-center"
                        :style="store.profile.cover_url ? { backgroundImage: `url(${store.profile.cover_url})` } : undefined"
                    >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent" />
                        <div class="absolute bottom-4 left-4 right-4 flex flex-wrap items-end justify-between gap-3">
                            <div class="text-white">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-white/70">Portada pública</p>
                                <p class="text-lg font-black truncate">{{ form.business_name || 'Tu negocio' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-full bg-white text-[#003874] text-xs font-bold px-4 py-2 disabled:opacity-60 shadow"
                                    :disabled="coverBusy"
                                    @click="coverInput?.click()"
                                >
                                    {{ store.profile.cover_url ? 'Cambiar' : 'Subir foto' }}
                                </button>
                                <button
                                    v-if="store.profile.cover_url"
                                    type="button"
                                    class="rounded-full bg-white/20 backdrop-blur text-white text-xs font-bold px-4 py-2 border border-white/30"
                                    :disabled="coverBusy"
                                    @click="removeCover"
                                >
                                    Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                    <input ref="coverInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onCoverPick" />
                </div>

                <form class="space-y-8" @submit.prevent="save">
                    <!-- Identidad -->
                    <section class="rounded-2xl border border-slate-100 bg-slate-50/40 p-5 md:p-6 space-y-5">
                        <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">corporate_fare</span>
                            Identidad del negocio
                        </h3>
                        <AppInput v-model="form.business_name" label="Nombre comercial" placeholder="Servicios eléctricos JC" />
                        <p class="text-xs text-slate-500 -mt-3">Aparece en anuncios si no indicas un nombre de local distinto.</p>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <AppInput v-model="form.razon_social" label="Razón social" required placeholder="Empresa SAC" />
                            <AppInput
                                v-model="form.ruc"
                                label="RUC (opcional)"
                                inputmode="numeric"
                                maxlength="11"
                                placeholder="20123456789"
                            />
                        </div>
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Descripción</span>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                maxlength="2000"
                                placeholder="Cuéntale a los clientes qué ofrece tu negocio…"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"
                            ></textarea>
                        </label>
                    </section>

                    <!-- Contacto -->
                    <section class="rounded-2xl border border-slate-100 p-5 md:p-6 space-y-5">
                        <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">call</span>
                            Contacto
                        </h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <AppInput v-model="form.whatsapp" label="WhatsApp" placeholder="51999999999" />
                            <AppInput v-model="form.contact_phone" label="Teléfono" placeholder="999999999" />
                        </div>
                    </section>

                    <!-- Ubicación -->
                    <section class="rounded-2xl border border-slate-100 p-5 md:p-6 space-y-5">
                        <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">location_on</span>
                            Ubicación
                        </h3>
                        <AppInput v-model="form.address_text" label="Dirección de referencia (opcional)" />
                        <div class="grid sm:grid-cols-3 gap-4">
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-slate-700">Departamento</span>
                                <select v-model="department_id" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874]">
                                    <option :value="null">Seleccionar</option>
                                    <option v-for="d in geo.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-slate-700">Provincia</span>
                                <select
                                    v-model="province_id"
                                    :disabled="!department_id"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874] disabled:opacity-50"
                                >
                                    <option :value="null">Seleccionar</option>
                                    <option v-for="p in geo.provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold text-slate-700">Distrito</span>
                                <select
                                    v-model="form.district_id"
                                    :disabled="!province_id"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874] disabled:opacity-50"
                                >
                                    <option :value="null">Seleccionar</option>
                                    <option v-for="d in geo.districts" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </label>
                        </div>
                    </section>

                    <!-- Horarios -->
                    <section class="rounded-2xl border border-slate-100 p-5 md:p-6">
                        <h3 class="text-sm font-bold text-[#003874] uppercase tracking-wide flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-[18px]">schedule</span>
                            Horario de atención
                        </h3>
                        <BusinessHoursEditor v-model="form.business_hours" />
                    </section>

                    <AppButton variant="primary" type="submit" :loading="saving">
                        Guardar perfil del negocio
                    </AppButton>
                </form>
            </template>

            <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-300">store</span>
                <p class="text-sm text-slate-600 mt-3 max-w-sm mx-auto">
                    Completa tu perfil de negocio para publicar anuncios y aparecer en búsquedas.
                </p>
            </div>
        </div>
    </section>
</template>
