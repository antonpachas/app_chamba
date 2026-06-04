<script setup>
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import AvatarCropModal from '@/components/common/AvatarCropModal.vue';

const auth = useAuthStore();
const fileInput = ref(null);
const uploading = ref(false);
const error = ref('');
const cropOpen = ref(false);
const pendingFile = ref(null);

const initials = computed(() => {
    const n = auth.user?.full_name?.trim() || '';
    if (!n) return '?';
    const p = n.split(/\s+/).filter(Boolean);
    return ((p[0]?.[0] || '') + (p[1]?.[0] || '')).toUpperCase() || '?';
});

function pick() { fileInput.value?.click(); }

function onFile(e) {
    const f = e.target.files?.[0];
    if (e.target) e.target.value = '';
    if (!f) return;
    error.value = '';
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
        error.value = 'Solo JPG, PNG o WEBP.';
        return;
    }
    if (f.size > 5 * 1024 * 1024) {
        error.value = 'Máximo 5 MB.';
        return;
    }
    pendingFile.value = f;
    cropOpen.value = true;
}

async function onCropped(file) {
    cropOpen.value = false;
    pendingFile.value = null;
    uploading.value = true;
    try {
        await auth.uploadAvatar(file);
    } catch (err) {
        error.value = err?.message || 'No se pudo subir la foto.';
    } finally {
        uploading.value = false;
    }
}

function cancelCrop() {
    cropOpen.value = false;
    pendingFile.value = null;
}

async function remove() {
    error.value = '';
    uploading.value = true;
    try { await auth.removeAvatar(); }
    catch (err) { error.value = err?.message || 'No se pudo eliminar.'; }
    finally { uploading.value = false; }
}
</script>

<template>
    <div class="flex items-center gap-4">
        <div class="relative">
            <img
                v-if="auth.avatarUrl"
                :src="auth.avatarUrl"
                alt="Avatar"
                class="w-24 h-24 rounded-2xl object-cover ring-4 ring-white shadow-lg"
            />
            <div
                v-else
                class="w-24 h-24 rounded-2xl bg-grad-brand text-white text-3xl font-black flex items-center justify-center shadow-lg ring-4 ring-white"
            >{{ initials }}</div>
            <button
                v-if="uploading"
                type="button"
                class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center text-white text-xs font-bold"
                disabled
            >Subiendo…</button>
        </div>
        <div class="flex-1">
            <p class="text-sm font-bold text-[#0b1c30]">Foto de perfil</p>
            <p class="text-xs text-slate-500 mb-2">JPG, PNG o WEBP · máx. 5 MB. Puedes recortar antes de subir.</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="pick" :disabled="uploading"
                    class="rounded-full bg-chamba-700 text-white text-sm font-bold px-4 py-2 hover:bg-chamba-800 disabled:opacity-60">
                    {{ auth.avatarUrl ? 'Cambiar foto' : 'Subir foto' }}
                </button>
                <button v-if="auth.avatarUrl" type="button" @click="remove" :disabled="uploading"
                    class="rounded-full border border-slate-300 text-sm font-bold text-slate-700 px-4 py-2 hover:bg-slate-50">
                    Quitar
                </button>
            </div>
            <p v-if="error" class="text-xs text-rose-700 mt-2">{{ error }}</p>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" @change="onFile" class="hidden" />
        </div>
        <AvatarCropModal
            :open="cropOpen"
            :file="pendingFile"
            @cancel="cancelCrop"
            @confirm="onCropped"
        />
    </div>
</template>
