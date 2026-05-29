<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { api } from '@/services/api';

const model = defineModel({
    type: Object,
    required: true,
});

const departments = reactive({ list: [] });
const provinces = reactive({ list: [] });
const districts = reactive({ list: [] });
const hydrating = ref(false);

function num(v) {
    const n = Number(v);
    return Number.isFinite(n) && n > 0 ? n : null;
}

async function loadDepartments() {
    if (departments.list.length) return;
    try {
        const r = await api.get('/geo/departments');
        departments.list = r.data || [];
    } catch {
        departments.list = [];
    }
}

async function onDeptChange(keepChildren = false) {
    if (!keepChildren) {
        provinces.list = [];
        districts.list = [];
        model.value.province_id = null;
        model.value.district_id = null;
        model.value.ubigeo = '';
    }
    const deptId = num(model.value.department_id);
    if (!deptId) return;
    try {
        const r = await api.get('/geo/provinces', { params: { department_id: deptId } });
        provinces.list = r.data || [];
    } catch {
        provinces.list = [];
    }
}

async function onProvChange(keepDistrict = false) {
    if (!keepDistrict) {
        districts.list = [];
        model.value.district_id = null;
        model.value.ubigeo = '';
    }
    const provId = num(model.value.province_id);
    if (!provId) return;
    try {
        const r = await api.get('/geo/districts', { params: { province_id: provId } });
        districts.list = r.data || [];
    } catch {
        districts.list = [];
    }
}

function onDistrictChange() {
    const d = districts.list.find((row) => num(row.id) === num(model.value.district_id));
    model.value.ubigeo = d?.ubigeo ? String(d.ubigeo) : model.value.ubigeo || '';
    if (d?.latitude != null && (model.value.latitude === '' || model.value.latitude == null)) {
        model.value.latitude = d.latitude;
    }
    if (d?.longitude != null && (model.value.longitude === '' || model.value.longitude == null)) {
        model.value.longitude = d.longitude;
    }
}

async function hydrateFromDistrictId() {
    const distId = num(model.value.district_id);
    if (!distId) return;
    try {
        const r = await api.get(`/geo/districts/${distId}`);
        const d = r.data;
        if (!d) return;
        model.value.department_id = num(d.department_id);
        model.value.province_id = num(d.province_id);
        model.value.district_id = distId;
        if (d.ubigeo) model.value.ubigeo = String(d.ubigeo);
    } catch {
        /* noop */
    }
}

async function hydrateCascade() {
    hydrating.value = true;
    await loadDepartments();

    const distId = num(model.value.district_id);
    if (distId && (!num(model.value.department_id) || !num(model.value.province_id))) {
        await hydrateFromDistrictId();
    } else if (model.value.ubigeo && String(model.value.ubigeo).length === 6) {
        try {
            const r = await api.get('/geo/ubigeo', { params: { ubigeo: String(model.value.ubigeo) } });
            const d = r.data;
            if (d) {
                model.value.department_id = num(d.department_id);
                model.value.province_id = num(d.province_id);
                model.value.district_id = num(d.district_id) ?? distId;
            }
        } catch {
            /* noop */
        }
    }

    const deptId = num(model.value.department_id);
    if (deptId) {
        await onDeptChange(true);
        model.value.department_id = deptId;
    }

    const provId = num(model.value.province_id);
    if (provId) {
        await onProvChange(true);
        model.value.province_id = provId;
    }

    const finalDist = num(model.value.district_id);
    if (finalDist) {
        model.value.district_id = finalDist;
        onDistrictChange();
    }

    hydrating.value = false;
}

watch(
    () => model.value.department_id,
    (id, prev) => {
        if (hydrating.value || !id || id === prev) return;
        void onDeptChange();
    },
);

watch(
    () => model.value.province_id,
    (id, prev) => {
        if (hydrating.value || !id || id === prev) return;
        void onProvChange();
    },
);

watch(
    () => [model.value.district_id, model.value.department_id, model.value.province_id],
    () => {
        if (hydrating.value) return;
        const needs = num(model.value.district_id) && (!num(model.value.department_id) || !num(model.value.province_id));
        if (needs) void hydrateCascade();
    },
);

function getGeoLabels() {
    const dept = departments.list.find((row) => num(row.id) === num(model.value.department_id));
    const prov = provinces.list.find((row) => num(row.id) === num(model.value.province_id));
    const dist = districts.list.find((row) => num(row.id) === num(model.value.district_id));
    return {
        department_name: dept?.name || null,
        province_name: prov?.name || null,
        district_name: dist?.name || null,
    };
}

onMounted(() => {
    void hydrateCascade();
});

defineExpose({ hydrateCascade, getGeoLabels });
</script>

<template>
    <div class="space-y-4 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
        <p class="text-sm font-bold text-slate-800">Ubicación en el mapa</p>
        <label class="block text-sm">
            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Nombre del local (opcional)</span>
            <input
                v-model="model.location_label"
                type="text"
                maxlength="120"
                placeholder="Ej. Sede Los Olivos, Local 2"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
            />
            <span class="mt-1 block text-xs text-slate-500">Aparece en el anuncio como nombre del local. Si lo dejas vacío, se usa el nombre comercial del perfil.</span>
        </label>
        <label class="block text-sm">
            <span class="font-bold text-slate-600 text-xs uppercase tracking-wide">Dirección</span>
            <input
                v-model="model.address_text"
                type="text"
                maxlength="500"
                placeholder="Calle, número, referencia…"
                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2.5"
            />
        </label>
        <div class="grid sm:grid-cols-3 gap-3">
            <label class="block text-sm">
                <span class="text-xs font-bold uppercase text-slate-500">Departamento</span>
                <select
                    v-model.number="model.department_id"
                    required
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    @change="onDeptChange()"
                >
                    <option :value="null">—</option>
                    <option v-for="d in departments.list" :key="d.id" :value="Number(d.id)">{{ d.name }}</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="text-xs font-bold uppercase text-slate-500">Provincia</span>
                <select
                    v-model.number="model.province_id"
                    required
                    :disabled="!model.department_id"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-50"
                    @change="onProvChange()"
                >
                    <option :value="null">—</option>
                    <option v-for="p in provinces.list" :key="p.id" :value="Number(p.id)">{{ p.name }}</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="text-xs font-bold uppercase text-slate-500">Distrito <span class="text-rose-600">*</span></span>
                <select
                    v-model.number="model.district_id"
                    required
                    :disabled="!model.province_id"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-50"
                    @change="onDistrictChange"
                >
                    <option :value="null">—</option>
                    <option v-for="d in districts.list" :key="d.id" :value="Number(d.id)">{{ d.name }}</option>
                </select>
            </label>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <label class="block text-sm">
                <span class="text-xs font-bold uppercase text-slate-500">Latitud (opcional)</span>
                <input
                    v-model="model.latitude"
                    type="number"
                    step="any"
                    placeholder="Auto desde distrito"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                />
            </label>
            <label class="block text-sm">
                <span class="text-xs font-bold uppercase text-slate-500">Longitud (opcional)</span>
                <input
                    v-model="model.longitude"
                    type="number"
                    step="any"
                    placeholder="Auto desde distrito"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                />
            </label>
        </div>
    </div>
</template>
