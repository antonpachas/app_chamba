<script setup>
import { ref } from 'vue';

const MAX = 2;

const props = defineProps({
    max: { type: Number, default: MAX },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['error']);

/** @type {import('vue').Ref<Array<{ key: string, file: File, url: string }>>} */
const items = defineModel({ type: Array, default: () => [] });

const inputRef = ref(null);

function revokeUrls() {
    for (const item of items.value) {
        if (item.url?.startsWith('blob:')) URL.revokeObjectURL(item.url);
    }
}

function onPick(e) {
    const files = Array.from(e.target.files || []);
    if (e.target) e.target.value = '';
    for (const f of files) {
        if (items.value.length >= props.max) {
            emit('error', `Máximo ${props.max} imágenes.`);
            break;
        }
        if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
            emit('error', 'Solo JPG, PNG o WEBP.');
            continue;
        }
        if (f.size > 5 * 1024 * 1024) {
            emit('error', 'Cada imagen máximo 5 MB.');
            continue;
        }
        items.value.push({
            key: `img-${Date.now()}-${Math.random().toString(36).slice(2)}`,
            file: f,
            url: URL.createObjectURL(f),
        });
    }
}

function remove(item) {
    if (item.url?.startsWith('blob:')) URL.revokeObjectURL(item.url);
    items.value = items.value.filter((row) => row.key !== item.key);
}

function clear() {
    revokeUrls();
    items.value = [];
}

defineExpose({ clear });
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Imágenes (opcional, máx. {{ max }})
            </span>
            <span class="text-xs text-slate-400">JPG, PNG o WEBP · 5 MB c/u</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <div
                v-for="item in items"
                :key="item.key"
                class="relative w-16 h-16 rounded-lg overflow-hidden ring-1 ring-slate-200"
            >
                <img :src="item.url" alt="" class="w-full h-full object-cover" />
                <button
                    type="button"
                    class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] font-bold"
                    :disabled="disabled"
                    @click="remove(item)"
                >
                    ×
                </button>
            </div>
            <label
                v-if="items.length < max"
                class="w-16 h-16 rounded-lg border-2 border-dashed border-slate-300 hover:border-[#003874] flex flex-col items-center justify-center cursor-pointer text-slate-400 hover:text-[#003874] transition"
                :class="disabled ? 'opacity-50 pointer-events-none' : ''"
            >
                <span class="material-symbols-outlined text-[20px]">add_photo_alternate</span>
                <span class="text-[9px] font-bold mt-0.5">Adjuntar</span>
                <input
                    ref="inputRef"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    class="hidden"
                    :disabled="disabled"
                    @change="onPick"
                />
            </label>
        </div>
    </div>
</template>
