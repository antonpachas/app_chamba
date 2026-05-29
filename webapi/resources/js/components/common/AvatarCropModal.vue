<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    file: { type: Object, default: null },
});

const emit = defineEmits(['cancel', 'confirm']);

const canvasRef = ref(null);
const previewUrl = ref('');
const scale = ref(1);
const offsetX = ref(0);
const offsetY = ref(0);
let img = null;
let dragging = false;
let dragStart = { x: 0, y: 0 };

const SIZE = 320;

function revoke() {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = '';
}

function loadImage(file) {
    revoke();
    if (!file) return;
    previewUrl.value = URL.createObjectURL(file);
    img = new Image();
    img.onload = () => draw();
    img.src = previewUrl.value;
    scale.value = 1;
    offsetX.value = 0;
    offsetY.value = 0;
}

function draw() {
    const canvas = canvasRef.value;
    if (!canvas || !img) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, SIZE, SIZE);
    ctx.fillStyle = '#e2e8f0';
    ctx.fillRect(0, 0, SIZE, SIZE);
    const min = Math.min(img.width, img.height);
    const base = SIZE / min;
    const w = img.width * base * scale.value;
    const h = img.height * base * scale.value;
    const x = (SIZE - w) / 2 + offsetX.value;
    const y = (SIZE - h) / 2 + offsetY.value;
    ctx.drawImage(img, x, y, w, h);
}

function onPointerDown(e) {
    dragging = true;
    dragStart = { x: e.clientX - offsetX.value, y: e.clientY - offsetY.value };
}

function onPointerMove(e) {
    if (!dragging) return;
    offsetX.value = e.clientX - dragStart.x;
    offsetY.value = e.clientY - dragStart.y;
    draw();
}

function onPointerUp() {
    dragging = false;
}

watch(() => props.file, (f) => loadImage(f), { immediate: true });
watch([scale, () => props.open], () => {
    if (props.open) draw();
});

onBeforeUnmount(revoke);

function confirmCrop() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            const name = props.file?.name?.replace(/\.[^.]+$/, '') || 'avatar';
            const ext = props.file?.type === 'image/png' ? 'png' : 'jpg';
            const cropped = new File([blob], `${name}-crop.${ext}`, {
                type: blob.type || 'image/jpeg',
                lastModified: Date.now(),
            });
            emit('confirm', cropped);
        },
        'image/jpeg',
        0.9,
    );
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-black/60"
            @click.self="emit('cancel')"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="font-bold text-[#0b1c30] mb-1">Recortar foto</h3>
                <p class="text-xs text-slate-500 mb-4">Arrastra para centrar · usa el zoom</p>
                <div
                    class="mx-auto rounded-2xl overflow-hidden ring-2 ring-slate-200 touch-none"
                    style="width: 320px; height: 320px"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @pointerleave="onPointerUp"
                >
                    <canvas ref="canvasRef" :width="SIZE" :height="SIZE" class="w-full h-full" />
                </div>
                <label class="block mt-4 text-xs font-bold text-slate-600">
                    Zoom
                    <input v-model.number="scale" type="range" min="0.6" max="2.5" step="0.05" class="w-full mt-1" @input="draw" />
                </label>
                <div class="flex gap-2 mt-4">
                    <button type="button" class="flex-1 rounded-full border border-slate-200 py-2.5 text-sm font-bold" @click="emit('cancel')">
                        Cancelar
                    </button>
                    <button type="button" class="flex-1 rounded-full btn-grad-primary py-2.5 text-sm font-bold text-white" @click="confirmCrop">
                        Usar foto
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
