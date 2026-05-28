<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const err = ref('');
const ok = ref('');
const busy = ref(null);

const userQ = ref('');
const userRole = ref('all');
const userStatus = ref('all');
const riskLevel = ref('all');
const onlyFlags = ref(false);
const users = ref([]);
const usersMeta = ref({ total: 0, current_page: 1, last_page: 1 });
const usersLoading = ref(false);

const suspendReason = ref('');
const suspendHideListings = ref(true);
const modalUser = ref(null);
const modalResetUser = ref(null);
const modalActivityUser = ref(null);
const activityLoading = ref(false);
const activityData = ref(null);
const requestMessagesModal = ref({ open: false, loading: false, request: null, messages: [] });

const userPages = computed(() => {
    const last = usersMeta.value.last_page || 1;
    return Array.from({ length: last }, (_, i) => i + 1);
});

const filteredUsers = computed(() => {
    return (users.value || []).filter((u) => {
        const levelOk = riskLevel.value === 'all' || (u.risk?.level || 'bajo') === riskLevel.value;
        const flagsOk = !onlyFlags.value || (u.risk?.flags?.length || 0) > 0;
        return levelOk && flagsOk;
    });
});

async function loadUsers(page = 1) {
    usersLoading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/users', {
            auth: true,
            params: {
                q: userQ.value || undefined,
                role: userRole.value,
                status: userStatus.value,
                page,
                per_page: 25,
            },
        });
        users.value = r.data || [];
        usersMeta.value = r.meta || usersMeta.value;
    } catch (e) {
        err.value = e.message;
        users.value = [];
    } finally {
        usersLoading.value = false;
    }
}

onMounted(() => loadUsers(1));

function openDisableModal(row) {
    modalUser.value = row;
    suspendReason.value = '';
    suspendHideListings.value = row.role === 'proveedor';
}

function openResetModal(row) {
    modalResetUser.value = row;
}

async function openActivityModal(row) {
    modalActivityUser.value = row;
    activityData.value = null;
    activityLoading.value = true;
    err.value = '';
    try {
        const r = await api.get(`/admin/users/${row.id}/activity`, { auth: true });
        activityData.value = r.data || null;
    } catch (e) {
        err.value = e.message || 'No se pudo cargar el historial.';
    } finally {
        activityLoading.value = false;
    }
}

async function openRequestMessages(requestId) {
    if (!modalActivityUser.value?.id || !requestId) return;
    requestMessagesModal.value = { open: true, loading: true, request: null, messages: [] };
    err.value = '';
    try {
        const r = await api.get(
            `/admin/users/${modalActivityUser.value.id}/service-requests/${requestId}/messages`,
            { auth: true },
        );
        requestMessagesModal.value = {
            open: true,
            loading: false,
            request: r.data?.service_request || null,
            messages: r.data?.messages || [],
        };
    } catch (e) {
        requestMessagesModal.value.loading = false;
        err.value = e.message || 'No se pudieron cargar los mensajes.';
    }
}

function closeRequestMessages() {
    requestMessagesModal.value = { open: false, loading: false, request: null, messages: [] };
}

async function confirmDisable() {
    if (!modalUser.value || !suspendReason.value.trim()) {
        err.value = 'Indica el motivo de la deshabilitación (se enviará por correo).';
        return;
    }
    busy.value = modalUser.value.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(
            `/admin/users/${modalUser.value.id}/suspend`,
            { reason: suspendReason.value.trim(), hide_listings: suspendHideListings.value },
            { auth: true },
        );
        ok.value = r.message || 'Cuenta deshabilitada.';
        modalUser.value = null;
        await loadUsers(usersMeta.value.current_page);
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function activateUser(row) {
    if (!confirm(`¿Reactivar la cuenta de ${row.full_name}?`)) return;
    busy.value = row.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(`/admin/users/${row.id}/activate`, {}, { auth: true });
        ok.value = r.message || 'Cuenta reactivada.';
        await loadUsers(usersMeta.value.current_page);
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

async function confirmResetPassword() {
    if (!modalResetUser.value) return;
    busy.value = modalResetUser.value.id;
    err.value = '';
    ok.value = '';
    try {
        const r = await api.post(
            `/admin/users/${modalResetUser.value.id}/reset-password`,
            {},
            { auth: true },
        );
        ok.value = r.message || 'Contraseña reiniciada.';
        modalResetUser.value = null;
    } catch (e) {
        err.value = e.message;
    } finally {
        busy.value = null;
    }
}

function formatDate(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString('es-PE', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return '—';
    }
}

function shortBody(v, max = 120) {
    const s = String(v || '').trim();
    if (!s) return '—';
    return s.length > max ? `${s.slice(0, max)}…` : s;
}

function csvEscape(value) {
    const s = String(value ?? '');
    const safe = s.replace(/"/g, '""');
    return `"${safe}"`;
}

function exportUsersCsv() {
    const headers = [
        'id',
        'nombre',
        'email',
        'telefono',
        'rol',
        'estado',
        'negocio',
        'riesgo_nivel',
        'riesgo_score',
        'riesgo_alertas',
        'fecha_registro',
    ];
    const lines = [headers.join(',')];

    for (const u of filteredUsers.value || []) {
        lines.push([
            csvEscape(u.id),
            csvEscape(u.full_name),
            csvEscape(u.email),
            csvEscape(u.phone || ''),
            csvEscape(u.role || ''),
            csvEscape(u.status || ''),
            csvEscape(u.provider_profile?.business_name || ''),
            csvEscape(u.risk?.level || 'bajo'),
            csvEscape(u.risk?.score ?? 0),
            csvEscape((u.risk?.flags || []).join('|')),
            csvEscape(formatDate(u.created_at)),
        ].join(','));
    }

    const csv = `\uFEFF${lines.join('\n')}`;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const d = new Date();
    const stamp = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}_${String(d.getHours()).padStart(2, '0')}${String(d.getMinutes()).padStart(2, '0')}`;
    a.href = url;
    a.download = `admin_usuarios_${stamp}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#003874]">Administración</p>
                <h1 class="text-3xl font-bold text-[#0b1c30] tracking-tight">Usuarios</h1>
                <p class="text-slate-600 mt-1">
                    Clientes y proveedores registrados. Deshabilitar envía un correo con el motivo.
                </p>
            </div>
            <RouterLink
                :to="{ name: 'admin-moderation' }"
                class="text-sm font-bold text-[#003874] hover:underline no-underline"
            >
                ← Moderación de anuncios
            </RouterLink>
        </header>

        <AppAlert v-if="err" type="error" class="mb-4">{{ err }}</AppAlert>
        <AppAlert v-if="ok" type="success" class="mb-4">{{ ok }}</AppAlert>

        <div class="flex flex-wrap gap-3 mb-4">
            <input
                v-model="userQ"
                type="search"
                placeholder="Nombre, email o teléfono…"
                class="flex-1 min-w-[200px] rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                @keyup.enter="loadUsers(1)"
            />
            <select v-model="userRole" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="loadUsers(1)">
                <option value="all">Todos los roles</option>
                <option value="proveedor">Proveedores</option>
                <option value="cliente">Clientes</option>
            </select>
            <select v-model="userStatus" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="loadUsers(1)">
                <option value="all">Todos los estados</option>
                <option value="activo">Activos</option>
                <option value="suspendido">Deshabilitados</option>
            </select>
            <select v-model="riskLevel" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                <option value="all">Riesgo: todos</option>
                <option value="alto">Riesgo alto</option>
                <option value="medio">Riesgo medio</option>
                <option value="bajo">Riesgo bajo</option>
            </select>
            <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-slate-200 px-3 py-2.5">
                <input v-model="onlyFlags" type="checkbox" />
                Solo con alertas
            </label>
            <AppButton variant="ghost" @click="exportUsersCsv">
                Exportar CSV
            </AppButton>
            <AppButton variant="primary" @click="loadUsers(1)">Buscar</AppButton>
        </div>

        <p class="text-sm text-slate-500 mb-3">
            {{ usersMeta.total ?? 0 }} usuario(s) · página {{ usersMeta.current_page }} de {{ usersMeta.last_page }}
        </p>

        <p v-if="usersLoading" class="text-slate-500 py-12 text-center">Cargando usuarios…</p>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">ID</th>
                        <th class="text-left px-4 py-3">Usuario</th>
                        <th class="text-left px-4 py-3">Rol</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Riesgo</th>
                        <th class="text-left px-4 py-3">Registro</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filteredUsers.length">
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">Sin resultados.</td>
                    </tr>
                    <tr v-for="u in filteredUsers" :key="u.id" class="border-t border-slate-100 hover:bg-slate-50/50">
                        <td class="px-4 py-3 text-slate-500 font-mono text-xs">#{{ u.id }}</td>
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-900">{{ u.full_name }}</p>
                            <p class="text-xs text-slate-500">{{ u.email }}</p>
                            <p class="text-xs text-slate-500">{{ u.phone || 'Sin teléfono' }}</p>
                            <p v-if="u.provider_profile?.business_name" class="text-xs text-[#003874] mt-0.5 font-semibold">
                                {{ u.provider_profile.business_name }}
                            </p>
                            <p v-if="u.suspended_reason" class="text-xs text-rose-700 mt-1 max-w-xs">
                                Motivo: {{ u.suspended_reason }}
                            </p>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ u.role }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                                :class="u.status === 'activo' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ u.status === 'suspendido' ? 'deshabilitado' : u.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                                    :class="
                                        (u.risk?.level || 'bajo') === 'alto'
                                            ? 'bg-rose-100 text-rose-800'
                                            : (u.risk?.level || 'bajo') === 'medio'
                                                ? 'bg-amber-100 text-amber-800'
                                                : 'bg-emerald-100 text-emerald-800'
                                    "
                                >
                                    {{ u.risk?.level || 'bajo' }}
                                </span>
                                <span class="text-xs text-slate-600">Score {{ u.risk?.score ?? 0 }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                {{ (u.risk?.flags || []).join(', ') || 'Sin alertas' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                            {{ formatDate(u.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1">
                                <AppButton
                                    variant="ghost"
                                    size="sm"
                                    @click="openActivityModal(u)"
                                >
                                    Ver actividad
                                </AppButton>
                                <AppButton
                                    variant="ghost"
                                    size="sm"
                                    :loading="busy === u.id"
                                    @click="openResetModal(u)"
                                >
                                    Reiniciar clave
                                </AppButton>
                                <AppButton
                                    v-if="u.status === 'activo'"
                                    variant="ghost"
                                    size="sm"
                                    class="!text-rose-700"
                                    :loading="busy === u.id"
                                    @click="openDisableModal(u)"
                                >
                                    Deshabilitar
                                </AppButton>
                                <AppButton
                                    v-else
                                    variant="primary"
                                    size="sm"
                                    :loading="busy === u.id"
                                    @click="activateUser(u)"
                                >
                                    Reactivar
                                </AppButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="userPages.length > 1" class="flex justify-center gap-2 mt-6 flex-wrap">
            <button
                v-for="p in userPages"
                :key="p"
                type="button"
                class="w-9 h-9 rounded-lg text-sm font-bold border"
                :class="p === usersMeta.current_page ? 'bg-[#003874] text-white border-[#003874]' : 'border-slate-200'"
                @click="loadUsers(p)"
            >
                {{ p }}
            </button>
        </div>

        <!-- Modal deshabilitar -->
        <div
            v-if="modalUser"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="modalUser = null"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-slate-900">Deshabilitar cuenta</h3>
                <p class="text-sm text-slate-600 mt-1">
                    {{ modalUser.full_name }} ({{ modalUser.email }}) no podrá iniciar sesión.
                    Se enviará un correo con el motivo indicado.
                </p>
                <label class="block mt-4">
                    <span class="text-sm font-bold text-slate-700">Motivo <span class="text-rose-600">*</span></span>
                    <textarea
                        v-model="suspendReason"
                        rows="4"
                        maxlength="500"
                        required
                        placeholder="Explica por qué se deshabilita la cuenta…"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    />
                </label>
                <label v-if="modalUser.role === 'proveedor'" class="flex items-center gap-2 mt-3 text-sm">
                    <input v-model="suspendHideListings" type="checkbox" class="w-4 h-4" />
                    Ocultar también todos sus anuncios activos
                </label>
                <div class="flex justify-end gap-2 mt-5">
                    <AppButton variant="ghost" @click="modalUser = null">Cancelar</AppButton>
                    <AppButton variant="primary" :loading="busy === modalUser.id" @click="confirmDisable">
                        Deshabilitar y notificar
                    </AppButton>
                </div>
            </div>
        </div>

        <!-- Modal reiniciar contraseña -->
        <div
            v-if="modalResetUser"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="modalResetUser = null"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-slate-900">Reiniciar contraseña</h3>
                <p class="text-sm text-slate-600 mt-1">
                    Se generará una contraseña temporal y se enviará a
                    <strong>{{ modalResetUser.email }}</strong>.
                    Se cerrarán sus sesiones activas.
                </p>
                <div class="flex justify-end gap-2 mt-5">
                    <AppButton variant="ghost" @click="modalResetUser = null">Cancelar</AppButton>
                    <AppButton variant="primary" :loading="busy === modalResetUser.id" @click="confirmResetPassword">
                        Enviar nueva contraseña
                    </AppButton>
                </div>
            </div>
        </div>

        <!-- Modal actividad -->
        <div
            v-if="modalActivityUser"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="modalActivityUser = null"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-6xl max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">
                            Historial de {{ modalActivityUser.full_name }}
                        </h3>
                        <p class="text-sm text-slate-600">
                            Supervisión de actividad completa (cliente/proveedor).
                        </p>
                    </div>
                    <AppButton variant="ghost" @click="modalActivityUser = null">Cerrar</AppButton>
                </div>

                <p v-if="activityLoading" class="text-slate-500 py-8">Cargando historial…</p>
                <template v-else-if="activityData">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Solicitudes como cliente</p>
                            <p class="text-2xl font-bold text-slate-900">{{ activityData.summary?.client_requests_total ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Solicitudes como proveedor</p>
                            <p class="text-2xl font-bold text-slate-900">{{ activityData.summary?.provider_requests_total ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Mensajes</p>
                            <p class="text-2xl font-bold text-slate-900">{{ activityData.summary?.messages_total ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Anuncios</p>
                            <p class="text-2xl font-bold text-slate-900">{{ activityData.summary?.listings_total ?? 0 }}</p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-3 mb-6 bg-slate-50">
                        <p class="text-sm font-semibold text-slate-900 mb-1">
                            Riesgo: {{ activityData.risk?.level || 'bajo' }} · Score {{ activityData.risk?.score ?? 0 }}
                        </p>
                        <p class="text-xs text-slate-600">
                            Alertas: {{ (activityData.risk?.flags || []).join(', ') || 'Sin alertas' }}
                        </p>
                    </div>

                    <div class="space-y-5">
                        <section class="rounded-xl border border-slate-200 p-4">
                            <h4 class="font-bold text-slate-900 mb-2">Solicitudes como cliente</h4>
                            <div v-if="!(activityData.client_requests || []).length" class="text-sm text-slate-500">Sin actividad.</div>
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm min-w-[600px]">
                                    <thead class="text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="text-left py-2">ID</th>
                                            <th class="text-left py-2">Anuncio</th>
                                            <th class="text-left py-2">Estado</th>
                                            <th class="text-left py-2">Msgs</th>
                                            <th class="text-left py-2">Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="r in activityData.client_requests" :key="`c-${r.id}`" class="border-t border-slate-100">
                                            <td class="py-2">#{{ r.id }}</td>
                                            <td class="py-2">{{ r.provider_service?.title || 'Anuncio' }}</td>
                                            <td class="py-2">{{ r.status }}</td>
                                            <td class="py-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 text-[#003874] font-semibold hover:underline"
                                                    @click="openRequestMessages(r.id)"
                                                >
                                                    <span class="material-symbols-outlined text-base">chat</span>
                                                    {{ r.messages_count ?? 0 }}
                                                </button>
                                            </td>
                                            <td class="py-2">{{ formatDate(r.created_at) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 p-4">
                            <h4 class="font-bold text-slate-900 mb-2">Solicitudes como proveedor</h4>
                            <div v-if="!(activityData.provider_requests || []).length" class="text-sm text-slate-500">Sin actividad.</div>
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm min-w-[560px]">
                                    <thead class="text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="text-left py-2">ID</th>
                                            <th class="text-left py-2">Servicio</th>
                                            <th class="text-left py-2">Estado</th>
                                            <th class="text-left py-2">Msgs</th>
                                            <th class="text-left py-2">Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="r in activityData.provider_requests" :key="`p-${r.id}`" class="border-t border-slate-100">
                                            <td class="py-2">#{{ r.id }}</td>
                                            <td class="py-2">{{ r.provider_service_title || r.provider_service_id }}</td>
                                            <td class="py-2">{{ r.status }}</td>
                                            <td class="py-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 text-[#003874] font-semibold hover:underline"
                                                    @click="openRequestMessages(r.id)"
                                                >
                                                    <span class="material-symbols-outlined text-base">chat</span>
                                                    {{ r.messages_count ?? 0 }}
                                                </button>
                                            </td>
                                            <td class="py-2">{{ formatDate(r.created_at) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 p-4">
                            <h4 class="font-bold text-slate-900 mb-2">Reseñas y favoritos</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm min-w-[520px]">
                                    <thead class="text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="text-left py-2">Métrica</th>
                                            <th class="text-left py-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-t border-slate-100"><td class="py-2">Reseñas</td><td class="py-2">{{ activityData.summary?.reviews_total ?? 0 }}</td></tr>
                                        <tr class="border-t border-slate-100"><td class="py-2">Favoritos</td><td class="py-2">{{ activityData.summary?.favorites_total ?? 0 }}</td></tr>
                                        <tr class="border-t border-slate-100"><td class="py-2">Pagos</td><td class="py-2">{{ activityData.summary?.payments_total ?? 0 }}</td></tr>
                                        <tr class="border-t border-slate-100"><td class="py-2">Solicitudes (7d)</td><td class="py-2">{{ activityData.risk?.metrics?.requests_7d ?? 0 }}</td></tr>
                                        <tr class="border-t border-slate-100"><td class="py-2">Mensajes (7d)</td><td class="py-2">{{ activityData.risk?.metrics?.messages_7d ?? 0 }}</td></tr>
                                        <tr class="border-t border-slate-100"><td class="py-2">Disputas</td><td class="py-2">{{ activityData.risk?.metrics?.disputes_total ?? 0 }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal mensajes por solicitud -->
        <div
            v-if="requestMessagesModal.open"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50"
            @click.self="closeRequestMessages"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[82vh] overflow-y-auto p-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="text-lg font-bold text-slate-900">
                        Mensajes · solicitud #{{ requestMessagesModal.request?.id || '—' }}
                    </h4>
                    <AppButton variant="ghost" size="sm" @click="closeRequestMessages">Cerrar</AppButton>
                </div>
                <p v-if="requestMessagesModal.loading" class="text-slate-500 py-6">Cargando mensajes…</p>
                <p v-else-if="!(requestMessagesModal.messages || []).length" class="text-slate-500 py-6">
                    Esta solicitud no tiene mensajes.
                </p>
                <div v-else class="space-y-2">
                    <article
                        v-for="m in requestMessagesModal.messages"
                        :key="m.id"
                        class="rounded-lg border border-slate-200 px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-xs font-bold uppercase text-slate-500">{{ m.author_role }}</span>
                            <span class="text-xs text-slate-500">{{ formatDate(m.created_at) }}</span>
                        </div>
                        <p class="text-sm text-slate-800 whitespace-pre-wrap">{{ shortBody(m.body, 5000) }}</p>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>
