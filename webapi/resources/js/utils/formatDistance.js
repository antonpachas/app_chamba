/**
 * @param {number|string|null|undefined} km
 * @returns {string|null}
 */
export function formatDistanceKm(km) {
    const d = parseFloat(String(km ?? '').replace(',', '.'));
    if (!Number.isFinite(d) || d < 0) {
        return null;
    }
    if (d < 1) {
        const m = Math.max(1, Math.round(d * 1000));
        return `${m} m`;
    }
    if (d < 10) {
        return `${d.toFixed(1)} km`;
    }
    return `${Math.round(d)} km`;
}
