<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '@/services/api';
import { useProviderLocationsStore } from '@/stores/providerLocations';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import BusinessHoursEditor from '@/components/provider/BusinessHoursEditor.vue';

const store = useProviderLocationsStore();
const editing = ref(null); // null | 'new' | {id...}
const form = reactive({
    label: '',
    address_text: '',
    department_id: null,
    province_id: null,
    district_id: null,
    ubigeo: '',
    latitude: '',
    longitude: '',
    is_primary: false,
    use_profile_hours: true,
    business_hours: null,
});
const departments = ref([]);
const provinces = ref([]);
const districts = ref([]);
const err = ref('');
const ok = ref('');
const busy = ref(false);

onMounted(async () => {
    await store.load();
    try {
        const r = await api.get('/geo/departments');
        departments.value = r.data || [];
    } catch { /* noop */ }
});

async function onDeptChange() {
    provinces.value = [];
    districts.value = [];
    form.province_id = null;
    form.district_id = null;
    if (!form.department_id) return;
    try {
        const r = await api.get('/geo/provinces', { params: { department_id: form.department_id } });
        provinces.value = r.data || [];
    } catch { /* noop */ }
}
async function onProvChange() {
    districts.value = [];
    form.district_id = null;
    form.ubigeo = '';
    if (!form.province_id) return;
    try {
        const r = await api.get('/geo/districts', { params: { province_id: form.province_id } });
        districts.value = r.data || [];
    } catch { /* noop */ }
}

function onDistrictChange() {
    const d = districts.value.find((row) => Number(row.id) === Number(form.district_id));
    form.ubigeo = d?.ubigeo ? String(d.ubigeo) : '';
    if (d?.latitude != null && form.latitude === '') form.latitude = d.latitude;
    if (d?.longitude != null && form.longitude === '') form.longitude = d.longitude;
}

function startNew() {
    editing.value = 'new';
    form.label = '';
    form.address_text = '';
    form.department_id = null;
    form.province_id = null;
    form.district_id = null;
    form.ubigeo = '';
    form.latitude = '';
    form.longitude = '';
    form.is_primary = false;
    form.use_profile_hours = true;
    form.business_hours = null;
    err.value = ''; ok.value = '';
}

async function startEdit(loc) {
    editing.value = loc;
    form.label = loc.label;
    form.address_text = loc.address_text || '';
    form.department_id = loc.department_id || null;
    form.province_id = loc.province_id || null;
    form.district_id = loc.district_id || null;
    form.ubigeo = loc.ubigeo || '';
    form.latitude = loc.latitude ?? '';
    form.longitude = loc.longitude ?? '';
    form.is_primary = !!loc.is_primary;
    form.use_profile_hours = !loc.business_hours;
    form.business_hours = loc.business_hours || null;
    err.value = ''; ok.value = '';
    if (form.department_id) {
        const r = await api.get('/geo/provinces', { params: { department_id: form.department_id } });
        provinces.value = r.data || [];
    }
    if (form.province_id) {
        const r = await api.get('/geo/districts', { params: { province_id: form.province_id } });
        districts.value = r.data || [];
    }
}

async function submit() {
    if (!form.label || !form.district_id) {
        err.value = 'Completa el nombre y el distrito.';
        return;
    }
    busy.value = true;
    err.value = ''; ok.value = '';
    try {
        const payload = {
            label: form.label,
            address_text: form.address_text || null,
            district_id: form.district_id,
            department_id: form.department_id || null,
            province_id: form.province_id || null,
            ubigeo: form.ubigeo || null,
            latitude: form.latitude === '' ? null : Number(form.latitude),
            longitude: form.longitude === '' ? null : Number(form.longitude),
            is_primary: form.is_primary,
            business_hours: form.use_profile_hours ? null : form.business_hours,
        };
        if (editing.value === 'new') {
            await store.create(payload);
            ok.value = 'Sede creada.';
        } else {
            await store.update(editing.value.id, payload);
            ok.value = 'Sede actualizada.';
        }
        editing.value = null;
    } catch (e) {
        err.value = e.message || 'No se pudo guardar.';
    } finally {
        busy.value = false;
    }
}

async function remove(id) {
    if (!confirm('¿Eliminar esta sede?')) return;
    try {
        await store.remove(id);
        ok.value = 'Sede eliminada.';
    } catch (e) {
        err.value = e.message;
    }
}
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis sedes</h1>
            <p class="text-slate-600 mt-1">
                Atiendes en varios distritos? Agrega tus sedes y aparecerás en cada búsqueda local.
                Tu plan permite hasta <strong>{{ store.max }}</strong> sede(s).
            </p>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-slate-600">
                {{ store.activeCount }} de {{ store.max }} sedes usadas.
            </p>
            <AppButton
                v-if="store.canAddMore && editing === null"
                variant="primary"
                size="sm"
                @click="startNew"
            >
                Nueva sede
            </AppButton>
        </div>

        <form
            v-if="editing"
            @submit.prevent="submit"
            class="rounded-2xl border border-slate-200 bg-white p-5 mb-6 space-y-3"
        >
            <h2 class="text-lg font-bold text-slate-900">
                {{ editing === 'new' ? 'Nueva sede' : 'Editar sede' }}
            </h2>
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Nombre de la sede</span>
                <input v-model="form.label" required maxlength="100" placeholder="Ej. Sede Miraflores"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
            </label>
            <div class="grid sm:grid-cols-3 gap-3">
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Departamento</span>
                    <select v-model="form.department_id" @change="onDeptChange"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]">
                        <option :value="null">— Selecciona —</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Provincia</span>
                    <select v-model="form.province_id" @change="onProvChange" :disabled="!provinces.length"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874] disabled:bg-slate-50">
                        <option :value="null">— Selecciona —</option>
                        <option v-for="p in provinces" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Distrito <span class="text-rose-600">*</span></span>
                    <select v-model="form.district_id" required :disabled="!districts.length" @change="onDistrictChange"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874] disabled:bg-slate-50">
                        <option :value="null">— Selecciona —</option>
                        <option v-for="d in districts" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </label>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Latitud (opcional)</span>
                    <input v-model="form.latitude" type="number" step="any" placeholder="-12.046"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Longitud (opcional)</span>
                    <input v-model="form.longitude" type="number" step="any" placeholder="-77.043"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
                </label>
            </div>
            <p class="text-xs text-slate-500">El código ubigeo se asigna automáticamente al elegir el distrito.</p>
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-1 block">Dirección (opcional)</span>
                <input v-model="form.address_text" maxlength="255" placeholder="Av. ..., piso ..."
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-[#003874]" />
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="form.is_primary" />
                <span>Marcar como sede principal</span>
            </label>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 space-y-3">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" v-model="form.use_profile_hours" />
                    Usar el mismo horario de mi perfil de negocio
                </label>
                <BusinessHoursEditor v-if="!form.use_profile_hours" v-model="form.business_hours" />
            </div>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" type="button" @click="editing = null">Cancelar</AppButton>
                <AppButton variant="primary" type="submit" :loading="busy">Guardar</AppButton>
            </div>
        </form>

        <p v-if="store.loading" class="text-slate-500">Cargando…</p>
        <ul v-else-if="store.items.length" class="space-y-3">
            <li
                v-for="l in store.items"
                :key="l.id"
                class="rounded-2xl border border-slate-200 bg-white p-4 flex flex-wrap justify-between gap-3"
            >
                <div>
                    <p class="font-bold text-slate-900">
                        {{ l.label }}
                        <span v-if="l.is_primary" class="ml-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded px-2 py-0.5">Principal</span>
                        <span v-if="!l.is_active" class="ml-2 text-xs font-semibold text-slate-500 bg-slate-50 border border-slate-200 rounded px-2 py-0.5">Inactiva</span>
                    </p>
                    <p class="text-sm text-slate-600">
                        {{ [l.district_name, l.province_name, l.department_name].filter(Boolean).join(' · ') }}
                    </p>
                    <p v-if="l.address_text" class="text-xs text-slate-500 mt-0.5">{{ l.address_text }}</p>
                    <p v-if="l.hours_summary" class="text-xs text-slate-500 mt-1">{{ l.hours_summary }}</p>
                    <p v-else class="text-xs text-slate-400 mt-1">Horario del perfil</p>
                </div>
                <div class="flex items-center gap-2">
                    <AppButton variant="ghost" size="sm" @click="startEdit(l)">Editar</AppButton>
                    <AppButton variant="ghost" size="sm" @click="remove(l.id)">Eliminar</AppButton>
                </div>
            </li>
        </ul>
        <div v-else class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-600">
            Aún no agregaste sedes.
        </div>
    </div>
</template>
