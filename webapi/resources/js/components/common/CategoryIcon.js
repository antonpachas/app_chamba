/**
 * Mapea el nombre de categoría (en BD) al icono Material Symbols
 * y al estilo de fondo/contraste para tarjetas y chips.
 */
export function categoryStyleFor(name) {
    const n = (name || '').toLowerCase();
    if (n.includes('plomer') || n.includes('gasfit') || n.includes('gasf')) {
        return { icon: 'plumbing', box: 'bg-blue-50 text-[#003874] group-hover:bg-blue-100' };
    }
    if (n.includes('carpin') || n.includes('mader')) {
        return { icon: 'carpenter', box: 'bg-orange-50 text-[#9f4200] group-hover:bg-orange-100' };
    }
    if (n.includes('electric')) {
        return { icon: 'electrical_services', box: 'bg-yellow-50 text-yellow-800 group-hover:bg-yellow-100' };
    }
    if (n.includes('limpie')) {
        return { icon: 'cleaning_services', box: 'bg-green-50 text-emerald-800 group-hover:bg-green-100' };
    }
    if (n.includes('pintur')) {
        return { icon: 'format_paint', box: 'bg-purple-50 text-purple-800 group-hover:bg-purple-100' };
    }
    if (n.includes('jardin') || n.includes('paisaj')) {
        return { icon: 'grass', box: 'bg-emerald-50 text-emerald-800 group-hover:bg-emerald-100' };
    }
    return { icon: 'handyman', box: 'bg-slate-50 text-[#003874] group-hover:bg-slate-100' };
}
