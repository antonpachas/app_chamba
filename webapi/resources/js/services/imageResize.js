/**
 * Redimensiona y comprime una imagen en el navegador antes de subirla.
 *
 * Útil porque algunos hostings limitan `upload_max_filesize` a 2 MB y las fotos
 * de móvil suelen pesar 3-5 MB. El resize se hace en cliente con Canvas y
 * devuelve un nuevo File listo para FormData.
 *
 * @param {File} file - archivo original (image/jpeg, image/png, image/webp).
 * @param {object} [opts]
 * @param {number} [opts.maxDimension=1600] - lado máximo (px) del resultado.
 * @param {number} [opts.maxBytes=1800000]   - tamaño objetivo (~1.8 MB) antes de comprimir más.
 * @param {number} [opts.quality=0.85]       - calidad JPEG inicial (0..1).
 * @param {number} [opts.minQuality=0.5]     - calidad JPEG mínima al iterar.
 * @returns {Promise<File>} archivo resultante (siempre JPEG salvo si era PNG con transparencia útil).
 */
export async function resizeImageFile(file, opts = {}) {
    if (!(file instanceof File)) return file;
    if (!file.type.startsWith('image/')) return file;

    const maxDimension = opts.maxDimension || 1600;
    const maxBytes = opts.maxBytes || 1_800_000;
    const initialQuality = opts.quality ?? 0.85;
    const minQuality = opts.minQuality ?? 0.5;

    // Si ya cumple el tamaño y no parece enorme, no hacemos nada.
    if (file.size <= maxBytes) {
        return file;
    }

    const bitmap = await loadImage(file);
    const { width, height } = bitmap;
    const longest = Math.max(width, height);
    const scale = longest > maxDimension ? maxDimension / longest : 1;
    const targetW = Math.max(1, Math.round(width * scale));
    const targetH = Math.max(1, Math.round(height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = targetW;
    canvas.height = targetH;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(bitmap, 0, 0, targetW, targetH);

    // El backend acepta JPG/PNG/WEBP. JPEG comprime mejor para fotos.
    const outputType = 'image/jpeg';
    let quality = initialQuality;
    let blob = await canvasToBlob(canvas, outputType, quality);

    while (blob && blob.size > maxBytes && quality > minQuality) {
        quality = Math.max(minQuality, quality - 0.1);
        blob = await canvasToBlob(canvas, outputType, quality);
    }

    if (!blob) return file;

    const newName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
    return new File([blob], newName, { type: outputType, lastModified: Date.now() });
}

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('No se pudo leer la imagen.'));
        };
        img.src = url;
    });
}

function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => {
        canvas.toBlob((b) => resolve(b), type, quality);
    });
}
