<script setup>
import { onMounted, ref } from 'vue';
import { useProviderProfileStore } from '@/stores/providerProfile';
import { useCatalogStore } from '@/stores/catalog';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import Money from '@/components/common/Money.vue';

const store = useProviderProfileStore();
const catalog = useCatalogStore();

const editing = ref(null);
const showForm = ref(false);
const form = ref({ category_id: null, title: '', description: '', base_price: '', price_type: 'cotizar' });
const errMsg = ref('');
const okMsg = ref('');
const saving = ref(false);

onMounted(async () => {
    await Promise.all([store.loadProfile(), store.loadServices(), catalog.ensureCategories()]);
});

function startCreate() {
    editing.value = null;
    form.value = { category_id: catalog.categories[0]?.id || null, title: '', description: '', base_price: '', price_type: 'cotizar' };
    showForm.value = true;
    errMsg.value = ''; okMsg.value = '';
}
function startEdit(s) {
    editing.value = s;
    form.value = {
        category_id: s.category?.id || null,
        title: s.title,
        description: s.description,
        base_price: s.base_price ?? '',
        price_type: s.price_type,
    };
    showForm.value = true;
    errMsg.value = ''; okMsg.value = '';
}

async function submit() {
    errMsg.value = ''; okMsg.value = '';
    if (!store.profile) {
        errMsg.value = 'Primero crea tu perfil de proveedor.';
        return;
    }
    saving.value = true;
    try {
        const payload = {
            category_id: form.value.category_id,
            title: form.value.title,
            description: form.value.description,
            base_price: form.value.base_price === '' ? null : Number(form.value.base_price),
            price_type: form.value.price_type,
        };
        if (editing.value) {
            await store.updateService(editing.value.id, payload);
            okMsg.value = 'Servicio actualizado.';
        } else {
            await store.createService(payload);
            okMsg.value = 'Servicio creado.';
        }
        showForm.value = false;
    } catch (e) {
        errMsg.value = e.message;
    } finally {
        saving.value = false;
    }
}

async function toggle(s) {
    try {
        await store.toggleServiceActive(s.id, !s.is_active);
    } catch (e) {
        errMsg.value = e.message;
    }
}

async function uploadImage(s, e) {
    const f = e.target.files?.[0];
    if (!f) return;
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
        errMsg.value = 'Solo JPG, PNG o WEBP.';
        e.target.value = '';
        return;
    }
    if (f.size > 5 * 1024 * 1024) {
        errMsg.value = 'Máximo 5 MB.';
        e.target.value = '';
        return;
    }
    errMsg.value = '';
    try {
        await store.addServiceImage(s.id, f);
        okMsg.value = 'Imagen agregada.';
    } catch (err) {
        errMsg.value = err?.message || 'No se pudo subir la imagen.';
    } finally {
        if (e.target) e.target.value = '';
    }
}

async function deleteImage(s, img) {
    errMsg.value = ''; okMsg.value = '';
    try {
        await store.removeServiceImage(s.id, img.id);
        okMsg.value = 'Imagen eliminada.';
    } catch (err) {
        errMsg.value = err?.message || 'No se pudo eliminar.';
    }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex justify-between items-end gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis servicios</h1>
                <p class="text-slate-600 mt-1">Publica los servicios que ofreces. Aparecen en la búsqueda.</p>
            </div>
            <AppButton variant="primary" @click="startCreate">+ Nuevo servicio</AppButton>
        </header>

        <AppAlert v-if="!store.profile && !store.loading" type="warning" class="mb-4">
            Primero crea tu perfil de proveedor para poder publicar servicios.
        </AppAlert>
        <AppAlert v-if="errMsg" type="error" class="mb-4">{{ errMsg }}</AppAlert>
        <AppAlert v-if="okMsg" type="success" class="mb-4">{{ okMsg }}</AppAlert>

        <form v-if="showForm" @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 mb-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">{{ editing ? 'Editar servicio' : 'Nuevo servicio' }}</h2>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Categoría</span>
                <select v-model="form.category_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874]">
                    <option v-for="c in catalog.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </label>
            <AppInput v-model="form.title" label="Título" placeholder="Instalación eléctrica residencial" required />
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Descripción</span>
                <textarea v-model="form.description" rows="4" required maxlength="2000"
                    class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[#003874]"></textarea>
            </label>
            <div class="grid sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Tipo de precio</span>
                    <select v-model="form.price_type" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874]">
                        <option value="cotizar">A cotizar</option>
                        <option value="desde">Desde…</option>
                        <option value="fijo">Fijo</option>
                    </select>
                </label>
                <AppInput v-model="form.base_price" label="Precio base (S/)" type="number" placeholder="150.00" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <AppButton variant="ghost" type="button" @click="showForm = false">Cancelar</AppButton>
                <AppButton variant="primary" type="submit" :loading="saving">{{ editing ? 'Guardar' : 'Crear' }}</AppButton>
            </div>
        </form>

        <p v-if="store.servicesLoading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!store.services.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no publicaste servicios.
        </div>
        <div v-else class="grid sm:grid-cols-2 gap-4">
            <article v-for="s in store.services" :key="s.id" class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">{{ s.category?.name }}</p>
                        <h3 class="text-base font-bold text-slate-900 mt-1 truncate">{{ s.title }}</h3>
                    </div>
                    <span
                        class="inline-flex px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                        :class="s.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                    >{{ s.is_active ? 'Activo' : 'Pausado' }}</span>
                </div>
                <p class="text-sm text-slate-600 mt-2 line-clamp-3">{{ s.description }}</p>
                <p class="text-base font-black text-[#003874] mt-3"><Money :amount="s.base_price" /> <span class="text-xs text-slate-500 font-medium">({{ s.price_type }})</span></p>

                <div class="mt-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Fotos del servicio</p>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="img in s.images || []" :key="img.id" class="relative group w-16 h-16">
                            <img :src="img.url" alt="" class="w-full h-full object-cover rounded-lg ring-1 ring-slate-200" />
                            <button type="button" @click="deleteImage(s, img)"
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] font-bold opacity-0 group-hover:opacity-100 transition shadow"
                                title="Eliminar">×</button>
                        </div>
                        <label class="w-16 h-16 rounded-lg border-2 border-dashed border-slate-300 hover:border-chamba-500 hover:bg-chamba-50/40 flex items-center justify-center cursor-pointer text-slate-400 hover:text-chamba-700 transition" title="Agregar foto">
                            <span class="material-symbols-outlined text-[20px]">add_photo_alternate</span>
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="uploadImage(s, $event)" />
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <AppButton variant="ghost" size="sm" @click="startEdit(s)">Editar</AppButton>
                    <AppButton variant="outline" size="sm" @click="toggle(s)">{{ s.is_active ? 'Pausar' : 'Activar' }}</AppButton>
                </div>
            </article>
        </div>
    </div>
</template>
