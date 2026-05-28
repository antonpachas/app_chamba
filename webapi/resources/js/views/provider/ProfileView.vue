<script setup>
import { onMounted, ref, watch } from 'vue';
import { useProviderProfileStore } from '@/stores/providerProfile';
import { useGeoStore } from '@/stores/geo';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import BusinessHoursEditor from '@/components/provider/BusinessHoursEditor.vue';

const store = useProviderProfileStore();
const geo = useGeoStore();

const form = ref({
    business_name: '',
    description: '',
    whatsapp: '',
    contact_phone: '',
    address_text: '',
    district_id: null,
});
const department_id = ref(null);
const province_id = ref(null);
const saving = ref(false);
const errMsg = ref('');
const okMsg = ref('');

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

onMounted(async () => {
    await Promise.all([store.loadProfile(), geo.ensureDepartments()]);
    if (store.profile) {
        form.value = {
            business_name: store.profile.business_name || '',
            description: store.profile.description || '',
            whatsapp: store.profile.whatsapp || '',
            contact_phone: store.profile.contact_phone || '',
            address_text: store.profile.address_text || '',
            district_id: store.profile.district?.id || null,
            business_hours: store.profile.business_hours || null,
        };
        await preselectGeoFromProfile();
    }
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

async function save() {
    errMsg.value = '';
    okMsg.value = '';
    if (!form.value.district_id) {
        errMsg.value = 'Selecciona departamento, provincia y distrito.';
        return;
    }
    saving.value = true;
    try {
        await store.saveProfile(form.value);
        okMsg.value = 'Perfil guardado.';
    } catch (e) {
        errMsg.value = e.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mi perfil de proveedor</h1>
            <p class="text-slate-600 mt-1">Datos visibles para los clientes en tu ficha pública.</p>
        </header>

        <AppAlert v-if="errMsg" type="error" class="mb-4">{{ errMsg }}</AppAlert>
        <AppAlert v-if="okMsg" type="success" class="mb-4">{{ okMsg }}</AppAlert>

        <form @submit.prevent="save" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            <AppInput v-model="form.business_name" label="Nombre del negocio" placeholder="Servicios eléctricos JC" />
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Descripción</span>
                <textarea v-model="form.description" rows="4" maxlength="2000"
                    class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[#003874] focus:ring-2 focus:ring-[#003874]/15"></textarea>
            </label>
            <div class="grid sm:grid-cols-2 gap-4">
                <AppInput v-model="form.whatsapp" label="WhatsApp" placeholder="51999999999" />
                <AppInput v-model="form.contact_phone" label="Teléfono" placeholder="999999999" />
            </div>
            <AppInput v-model="form.address_text" label="Dirección de referencia (opcional)" />

            <BusinessHoursEditor v-model="form.business_hours" />

            <div class="grid sm:grid-cols-3 gap-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Departamento</span>
                    <select v-model="department_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874]">
                        <option :value="null">Seleccionar</option>
                        <option v-for="d in geo.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Provincia</span>
                    <select v-model="province_id" :disabled="!department_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874] disabled:opacity-50">
                        <option :value="null">Seleccionar</option>
                        <option v-for="p in geo.provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Distrito</span>
                    <select v-model="form.district_id" :disabled="!province_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874] disabled:opacity-50">
                        <option :value="null">Seleccionar</option>
                        <option v-for="d in geo.districts" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </label>
            </div>

            <div class="pt-2">
                <AppButton variant="primary" type="submit" :loading="saving">
                    {{ store.profile ? 'Guardar cambios' : 'Crear perfil' }}
                </AppButton>
            </div>
        </form>
    </div>
</template>
