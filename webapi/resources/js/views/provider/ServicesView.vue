<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useProviderProfileStore } from '@/stores/providerProfile';
import { useCatalogStore } from '@/stores/catalog';
import { useAuthStore } from '@/stores/auth';
import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppAlert from '@/components/ui/AppAlert.vue';
import Money from '@/components/common/Money.vue';
import ListingCardPreview from '@/components/listing/ListingCardPreview.vue';
import BusinessHoursEditor from '@/components/provider/BusinessHoursEditor.vue';
import ListingLocationFields from '@/components/provider/ListingLocationFields.vue';

const MAX_PHOTOS = 3;

const store = useProviderProfileStore();
const catalog = useCatalogStore();
const auth = useAuthStore();

const editing = ref(null);
const showForm = ref(false);
function buildEmptyForm() {
    const p = store.profile;
    return {
        listing_type: 'presencia',
        category_id: catalog.categories[0]?.id || null,
        title: '',
        description: '',
        base_price: '',
        price_type: 'cotizar',
        location_label: '',
        address_text: p?.address_text || '',
        department_id: null,
        province_id: null,
        district_id: p?.district_id || null,
        ubigeo: '',
        latitude: '',
        longitude: '',
        business_hours: null,
        use_profile_hours: true,
        request_home_banner: false,
    };
}

const form = ref(buildEmptyForm());
/** @type {import('vue').Ref<Array<{ key: string, file?: File, url: string, id?: number, isExisting?: boolean }>>} */
const formPhotos = ref([]);
const photosToDelete = ref([]);
const errMsg = ref('');
const okMsg = ref('');
const loadError = ref('');
const saving = ref(false);
const rowActionBusyId = ref(null);
const locationFieldsRef = ref(null);

const categoryName = computed(() => {
    const id = form.value.category_id;
    return catalog.categories.find((c) => Number(c.id) === Number(id))?.name || '';
});

const previewImages = computed(() => formPhotos.value.map((p) => p.url));

const quota = computed(() => store.servicesQuota || {});

const canCreatePromocion = computed(() => (quota.value.promocion?.max ?? 0) > 0);

const previewService = computed(() => {
    const { department_id, province_id, district_id } = form.value;
    void department_id;
    void province_id;
    void district_id;
    const geo = locationFieldsRef.value?.getGeoLabels?.() || {};
    const locationLabel = form.value.location_label?.trim() || null;
    return {
        service_id: editing.value?.id || 0,
        title: form.value.title.trim() || 'Título del anuncio',
        base_price: form.value.base_price === '' ? null : form.value.base_price,
        price_type: form.value.price_type,
        cover_image_url: previewImages.value[0] || null,
        images: previewImages.value.slice(0, MAX_PHOTOS),
        location_label: locationLabel,
        provider_name: locationLabel || store.profile?.business_name || auth.user?.full_name || 'Tu negocio',
        department_name: geo.department_name || null,
        province_name: geo.province_name || null,
        district_name: geo.district_name || null,
        address_text: form.value.address_text || store.profile?.address_text || null,
        avg_rating: store.profile?.avg_rating,
        total_reviews: store.profile?.total_reviews,
        is_pro: auth.isPro,
        listing_type: form.value.listing_type,
    };
});

function listingTypeLabel(type) {
    return type === 'promocion' ? 'Destacado' : 'Presencia';
}

function revokePhotoUrls() {
    for (const p of formPhotos.value) {
        if (p.url?.startsWith('blob:')) {
            URL.revokeObjectURL(p.url);
        }
    }
}

function resetPhotos() {
    revokePhotoUrls();
    formPhotos.value = [];
    photosToDelete.value = [];
}

function startCreate() {
    editing.value = null;
    form.value = buildEmptyForm();
    resetPhotos();
    showForm.value = true;
    errMsg.value = '';
    okMsg.value = '';
}

function startEdit(s) {
    editing.value = s;
    form.value = {
        listing_type: s.listing_type || 'presencia',
        category_id: s.category?.id || null,
        title: s.title,
        description: s.description,
        base_price: s.base_price ?? '',
        price_type: s.price_type,
        location_label: s.location_label || '',
        address_text: s.address_text || '',
        department_id: s.department_id || null,
        province_id: s.province_id || null,
        district_id: s.district_id || null,
        ubigeo: s.ubigeo || '',
        latitude: s.latitude ?? '',
        longitude: s.longitude ?? '',
        business_hours: s.business_hours || null,
        use_profile_hours: !s.business_hours,
        request_home_banner: !!(s.home_featured_requested && !s.home_featured),
    };
    resetPhotos();
    formPhotos.value = (s.images || []).map((img) => ({
        key: `existing-${img.id}`,
        id: img.id,
        url: img.url,
        isExisting: true,
    }));
    showForm.value = true;
    errMsg.value = '';
    okMsg.value = '';
    nextTick(() => locationFieldsRef.value?.hydrateCascade?.());
}

function closeForm() {
    showForm.value = false;
    resetPhotos();
}

async function onPickPhotos(event) {
    const input = event.target;
    const files = Array.from(input.files || []);
    errMsg.value = '';
    for (const f of files) {
        if (formPhotos.value.length >= MAX_PHOTOS) {
            errMsg.value = `Máximo ${MAX_PHOTOS} fotos por anuncio.`;
            break;
        }
        if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
            errMsg.value = 'Solo JPG, PNG o WEBP.';
            continue;
        }
        if (f.size > 5 * 1024 * 1024) {
            errMsg.value = 'Cada imagen máximo 5 MB.';
            continue;
        }
        formPhotos.value.push({
            key: `new-${Date.now()}-${Math.random().toString(36).slice(2)}`,
            file: f,
            url: URL.createObjectURL(f),
        });
    }
    if (input) input.value = '';
}

function removeFormPhoto(item) {
    if (item.isExisting && item.id) {
        photosToDelete.value.push(item.id);
    }
    if (item.url?.startsWith('blob:')) {
        URL.revokeObjectURL(item.url);
    }
    formPhotos.value = formPhotos.value.filter((p) => p.key !== item.key);
}

function setCover(index) {
    if (index <= 0 || index >= formPhotos.value.length) return;
    const next = [...formPhotos.value];
    const [picked] = next.splice(index, 1);
    next.unshift(picked);
    formPhotos.value = next;
}

async function uploadPendingPhotos(serviceId) {
    for (const photo of formPhotos.value) {
        if (photo.file) {
            await store.addServiceImage(serviceId, photo.file);
        }
    }
    for (const imageId of photosToDelete.value) {
        await store.removeServiceImage(serviceId, imageId);
    }
}

function homeBannerLabel(s) {
    if (s.home_featured) return { text: 'En banner', cls: 'bg-emerald-100 text-emerald-800' };
    if (s.home_featured_requested) return { text: 'Pendiente', cls: 'bg-amber-100 text-amber-900' };
    if (s.home_featured_rejected_at) return { text: 'Rechazado', cls: 'bg-rose-100 text-rose-800' };
    return null;
}

async function syncHomeBannerRequest(serviceId, previous) {
    const wants = !!form.value.request_home_banner;
    const wasPending = !!(previous?.home_featured_requested && !previous?.home_featured);
    const wasApproved = !!previous?.home_featured;
    if (wasApproved) return;
    if (wants && !wasPending) {
        const r = await store.requestHomeBanner(serviceId);
        okMsg.value = r.message || okMsg.value;
    } else if (!wants && wasPending) {
        const r = await store.cancelHomeBannerRequest(serviceId);
        okMsg.value = r.message || okMsg.value;
    }
}

async function submit() {
    errMsg.value = '';
    okMsg.value = '';
    if (!store.profile) {
        errMsg.value = 'Primero crea tu perfil de negocio.';
        return;
    }
    if (!formPhotos.value.length) {
        errMsg.value = 'Agrega al menos una foto para tu anuncio.';
        return;
    }
    if (!form.value.district_id) {
        errMsg.value = 'Selecciona el distrito donde está este local.';
        return;
    }
    saving.value = true;
    try {
        const payload = {
            listing_type: form.value.listing_type,
            category_id: form.value.category_id,
            title: form.value.title,
            description: form.value.description,
            base_price: form.value.base_price === '' ? null : Number(form.value.base_price),
            price_type: form.value.price_type,
            location_label: form.value.location_label || null,
            address_text: form.value.address_text || null,
            department_id: form.value.department_id,
            province_id: form.value.province_id,
            district_id: form.value.district_id,
            ubigeo: form.value.ubigeo || null,
            latitude: form.value.latitude === '' ? null : Number(form.value.latitude),
            longitude: form.value.longitude === '' ? null : Number(form.value.longitude),
            business_hours: form.value.use_profile_hours ? null : form.value.business_hours,
        };
        let serviceId;
        const previous = editing.value ? { ...editing.value } : null;
        if (editing.value) {
            await store.updateService(editing.value.id, payload);
            serviceId = editing.value.id;
            await uploadPendingPhotos(serviceId);
            await syncHomeBannerRequest(serviceId, previous);
            await store.loadServices();
            okMsg.value = okMsg.value || 'Anuncio actualizado.';
        } else {
            const created = await store.createService(payload);
            serviceId = created.id;
            await uploadPendingPhotos(serviceId);
            if (form.value.request_home_banner) {
                const r = await store.requestHomeBanner(serviceId);
                okMsg.value = r.message || 'Anuncio publicado con fotos.';
            } else {
                okMsg.value = 'Anuncio publicado con fotos.';
            }
            await store.loadServices();
        }
        resetPhotos();
        showForm.value = false;
        editing.value = null;
    } catch (e) {
        errMsg.value = e.message;
    } finally {
        saving.value = false;
    }
}

async function toggle(s) {
    errMsg.value = '';
    okMsg.value = '';
    rowActionBusyId.value = s.id;
    try {
        const nextActive = !s.is_active;
        await store.toggleServiceActive(s.id, nextActive);
        okMsg.value = nextActive ? 'Anuncio activado.' : 'Anuncio pausado.';
    } catch (e) {
        errMsg.value = e.message;
    } finally {
        rowActionBusyId.value = null;
    }
}

async function renew(s) {
    errMsg.value = '';
    okMsg.value = '';
    rowActionBusyId.value = s.id;
    try {
        await store.renewService(s.id);
        okMsg.value = 'Anuncio renovado con nueva fecha de vencimiento.';
    } catch (e) {
        errMsg.value = e.message;
    } finally {
        rowActionBusyId.value = null;
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
    errMsg.value = '';
    okMsg.value = '';
    try {
        await store.removeServiceImage(s.id, img.id);
        okMsg.value = 'Imagen eliminada.';
    } catch (err) {
        errMsg.value = err?.message || 'No se pudo eliminar.';
    }
}

onMounted(async () => {
    loadError.value = '';
    try {
        await Promise.all([store.loadProfile(), store.loadServices(), catalog.ensureCategories()]);
    } catch (e) {
        loadError.value = e?.message || 'No se pudo cargar la página de anuncios.';
    }
});

onBeforeUnmount(() => {
    revokePhotoUrls();
});

watch(showForm, (open) => {
    if (!open) revokePhotoUrls();
});
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex justify-between items-end gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Mis fichas</h1>
                <p class="text-slate-600 mt-1">
                    Cada ficha es tu negocio en el mapa (presencia permanente) o un anuncio destacado (vence en
                    {{ store.defaultDurationDays }} días y sale primero en búsquedas).
                </p>
                <p class="text-sm text-slate-500 mt-1">
                    Presencia: {{ quota.presencia?.active ?? 0 }}/{{ quota.presencia?.max ?? '—' }}
                    <span v-if="canCreatePromocion">
                        · Destacados: {{ quota.promocion?.active ?? 0 }}/{{ quota.promocion?.max ?? '—' }}
                    </span>
                </p>
            </div>
            <AppButton variant="primary" @click="startCreate">+ Nueva ficha</AppButton>
        </header>

        <AppAlert v-if="loadError" type="error" class="mb-4">{{ loadError }}</AppAlert>
        <AppAlert v-if="!store.profile && !store.loading" type="warning" class="mb-4">
            Primero crea tu perfil de negocio para poder publicar anuncios.
        </AppAlert>
        <AppAlert v-if="errMsg" type="error" class="mb-4">{{ errMsg }}</AppAlert>
        <AppAlert v-if="okMsg" type="success" class="mb-4">{{ okMsg }}</AppAlert>

        <p v-if="store.servicesLoading" class="text-slate-500">Cargando…</p>
        <div v-else-if="!store.services.length" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">
            Aún no publicaste anuncios.
        </div>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Ficha</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Ubicación</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Vigencia</th>
                        <th class="text-left px-4 py-3">Precio</th>
                        <th class="text-left px-4 py-3">Banner</th>
                        <th class="text-left px-4 py-3">Fotos</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in store.services" :key="s.id" class="border-t border-slate-100 hover:bg-slate-50/50">
                        <td class="px-4 py-3 min-w-0">
                            <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">{{ s.category?.name }}</p>
                            <p class="font-semibold text-slate-900 truncate max-w-[320px]">{{ s.title }}</p>
                            <p class="text-xs text-slate-500 line-clamp-1">{{ s.description }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex px-2 py-0.5 text-[10px] font-bold uppercase rounded-full"
                                :class="s.listing_type === 'promocion' ? 'bg-amber-100 text-amber-900' : 'bg-sky-100 text-sky-800'"
                            >{{ listingTypeLabel(s.listing_type) }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 max-w-[160px]">
                            <span class="line-clamp-2">{{ s.district?.name || '—' }}</span>
                            <span v-if="s.location_label" class="block text-slate-400">{{ s.location_label }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="s.admin_hidden"
                                class="inline-flex px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-rose-100 text-rose-800"
                            >Oculto por admin</span>
                            <span
                                v-else
                                class="inline-flex px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                :class="s.is_visible ? 'bg-emerald-100 text-emerald-800' : (s.is_expired ? 'bg-amber-100 text-amber-900' : 'bg-slate-200 text-slate-600')"
                            >{{ s.is_visible ? 'Visible' : (s.is_expired ? 'Vencido' : 'Pausado') }}</span>
                            <p v-if="s.admin_hidden && s.admin_hidden_reason" class="mt-2 text-xs text-rose-700 bg-rose-50 border border-rose-100 rounded-lg px-2 py-1.5 max-w-[260px]">
                                Motivo: {{ s.admin_hidden_reason }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                            <template v-if="s.listing_type === 'promocion' && s.expires_at">
                                {{ new Date(s.expires_at).toLocaleDateString() }}
                            </template>
                            <template v-else-if="s.listing_type === 'presencia'">Permanente</template>
                            <template v-else>—</template>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <Money :amount="s.base_price" />
                            <span class="text-xs text-slate-500">({{ s.price_type }})</span>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <span
                                v-if="homeBannerLabel(s)"
                                class="inline-flex px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                :class="homeBannerLabel(s).cls"
                            >{{ homeBannerLabel(s).text }}</span>
                            <span v-else class="text-slate-400">—</span>
                            <p v-if="s.home_featured_rejection_reason" class="mt-1 text-[11px] text-rose-700 max-w-[180px]">
                                {{ s.home_featured_rejection_reason }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ (s.images || []).length }} foto(s)</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <AppButton variant="ghost" size="sm" @click="startEdit(s)">Editar</AppButton>
                                <AppButton
                                    v-if="s.listing_type === 'promocion' && s.is_expired && !s.admin_hidden"
                                    variant="primary"
                                    size="sm"
                                    :loading="rowActionBusyId === s.id"
                                    @click="renew(s)"
                                >Renovar</AppButton>
                                <AppButton v-else-if="!s.admin_hidden" variant="outline" size="sm" :loading="rowActionBusyId === s.id" @click="toggle(s)">
                                    {{ s.is_active ? 'Pausar' : 'Activar' }}
                                </AppButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="showForm"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="closeForm"
        >
            <div class="w-full max-w-6xl max-h-[90vh] rounded-2xl border border-slate-200 bg-white shadow-2xl flex flex-col overflow-hidden">
                <div class="sticky top-0 px-6 py-4 border-b border-slate-100 bg-white z-10 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ editing ? 'Editar ficha' : 'Nueva ficha' }}</h2>
                        <p class="text-sm text-slate-600 mt-0.5">Ubicación, horario y fotos de esta aparición en Busca PE.</p>
                    </div>
                    <button type="button" class="text-slate-500 hover:text-slate-800 text-xl leading-none" @click="closeForm">×</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="grid lg:grid-cols-2 gap-0 lg:gap-8 p-6">
                    <form id="service-modal-form" class="space-y-4 order-2 lg:order-1" @submit.prevent="submit">
                        <label v-if="canCreatePromocion" class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Tipo de ficha</span>
                            <select v-model="form.listing_type" class="w-full rounded-lg border border-slate-200 px-3 py-2.5">
                                <option value="presencia">Presencia en el mapa (sin vencimiento)</option>
                                <option value="promocion">Anuncio destacado (prioridad + vence)</option>
                            </select>
                        </label>
                        <p v-else class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            Plan Free: solo fichas de presencia. Mejora a Pro para anuncios destacados.
                        </p>
                        <ListingLocationFields ref="locationFieldsRef" v-model="form" />
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Categoría</span>
                            <select v-model="form.category_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:border-[#003874]">
                                <option v-for="c in catalog.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </label>
                        <AppInput v-model="form.title" label="Título" placeholder="Ej. Ferretería con delivery" required />
                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Descripción</span>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                required
                                maxlength="2000"
                                placeholder="Describe tu negocio o servicio…"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[#003874]"
                            ></textarea>
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

                        <div class="block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">
                                Fotos del anuncio <span class="text-rose-600">*</span>
                                <span class="text-slate-400 font-normal">({{ formPhotos.length }}/{{ MAX_PHOTOS }})</span>
                            </span>
                            <p class="text-xs text-slate-500 mb-3">Máximo {{ MAX_PHOTOS }} fotos. La primera es la portada en búsqueda. JPG, PNG o WEBP · máx. 5 MB c/u.</p>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <div
                                    v-for="(photo, idx) in formPhotos"
                                    :key="photo.key"
                                    class="relative group w-20 h-20"
                                >
                                    <img :src="photo.url" alt="" class="w-full h-full object-cover rounded-lg ring-2 ring-slate-200" :class="idx === 0 ? 'ring-[#003874]' : ''" />
                                    <span
                                        v-if="idx === 0"
                                        class="absolute bottom-0 left-0 right-0 text-center text-[9px] font-bold uppercase bg-[#003874] text-white rounded-b-lg py-0.5"
                                    >Portada</span>
                                    <button
                                        type="button"
                                        class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] font-bold shadow opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition"
                                        title="Quitar"
                                        @click="removeFormPhoto(photo)"
                                    >×</button>
                                    <button
                                        v-if="idx > 0"
                                        type="button"
                                        class="absolute -bottom-1 left-1/2 -translate-x-1/2 text-[8px] font-bold text-[#003874] bg-white border border-slate-200 rounded px-1 shadow-sm opacity-0 group-hover:opacity-100 transition whitespace-nowrap"
                                        title="Usar como portada"
                                        @click="setCover(idx)"
                                    >
                                        Portada
                                    </button>
                                </div>
                                <label
                                    v-if="formPhotos.length < MAX_PHOTOS"
                                    class="w-20 h-20 rounded-lg border-2 border-dashed border-slate-300 hover:border-[#003874] hover:bg-[#003874]/5 flex flex-col items-center justify-center cursor-pointer text-slate-400 hover:text-[#003874] transition"
                                >
                                    <span class="material-symbols-outlined text-[22px]">add</span>
                                    <span class="text-[9px] font-bold mt-0.5">Agregar</span>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        multiple
                                        class="hidden"
                                        @change="onPickPhotos"
                                    />
                                </label>
                            </div>
                        </div>

                        <div class="block rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                            <span class="block text-sm font-bold text-slate-700">Banner principal del inicio</span>
                            <p v-if="editing?.home_featured" class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
                                Tu anuncio está aprobado y visible en el banner superior del directorio.
                            </p>
                            <template v-else>
                                <label class="flex items-start gap-2 text-sm text-slate-700 cursor-pointer">
                                    <input v-model="form.request_home_banner" type="checkbox" class="rounded mt-0.5" />
                                    <span>
                                        Solicitar aparecer en el banner del inicio
                                        <span class="block text-xs text-slate-500 mt-1 font-normal">
                                            Un administrador revisará tu anuncio antes de publicarlo arriba de todos.
                                        </span>
                                    </span>
                                </label>
                                <p v-if="editing?.home_featured_requested" class="text-xs text-amber-800">
                                    Solicitud pendiente de aprobación.
                                </p>
                                <p v-if="editing?.home_featured_rejected_at" class="text-xs text-rose-700">
                                    Solicitud anterior rechazada{{ editing.home_featured_rejection_reason ? `: ${editing.home_featured_rejection_reason}` : '' }}.
                                </p>
                            </template>
                        </div>

                        <div class="block rounded-xl border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                            <span class="block text-sm font-bold text-slate-700">Horario de atención de este anuncio</span>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input v-model="form.use_profile_hours" type="checkbox" class="rounded" />
                                Usar el horario del perfil del negocio
                            </label>
                            <BusinessHoursEditor v-if="!form.use_profile_hours" v-model="form.business_hours" />
                            <p v-else class="text-xs text-slate-500">
                                Si el perfil no tiene horario, en búsqueda no se mostrará «abierto ahora».
                            </p>
                        </div>

                    </form>

                    <aside class="order-1 lg:order-2 lg:sticky lg:top-24 self-start space-y-3">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500 hidden lg:block">
                            Así lo verán en Busca PE
                        </p>
                        <ListingCardPreview :service="previewService" />
                        <p v-if="categoryName" class="text-xs text-slate-500 text-center hidden lg:block">
                            Categoría: <strong>{{ categoryName }}</strong>
                        </p>
                    </aside>
                    </div>
                </div>

                <div class="sticky bottom-0 border-t border-slate-100 bg-white px-6 py-4 flex justify-end gap-2 z-10">
                    <AppButton variant="ghost" type="button" @click="closeForm">Cancelar</AppButton>
                    <AppButton variant="primary" type="submit" form="service-modal-form" :loading="saving">
                        {{ editing ? 'Guardar cambios' : 'Publicar anuncio' }}
                    </AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
