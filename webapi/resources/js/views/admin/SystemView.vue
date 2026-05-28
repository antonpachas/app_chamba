<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '@/services/api';
import AppButton from '@/components/ui/AppButton.vue';
import AppAlert from '@/components/ui/AppAlert.vue';

const err = ref('');
const loading = ref(false);
const logs = ref([]);
const meta = ref({ total: 0, current_page: 1, last_page: 1, per_page: 25 });

const level = ref('all');
const channel = ref('all');
const q = ref('');
const detail = ref(null);
const detailLoading = ref(false);

const pages = computed(() => {
    const last = meta.value.last_page || 1;
    return Array.from({ length: last }, (_, i) => i + 1);
});

const levelClass = {
    error: 'bg-red-100 text-red-800',
    critical: 'bg-red-200 text-red-900',
    alert: 'bg-orange-100 text-orange-800',
    warning: 'bg-amber-100 text-amber-800',
    info: 'bg-sky-100 text-sky-800',
};

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-PE', { dateStyle: 'medium', timeStyle: 'medium' });
}

function shortMsg(msg, max = 80) {
    if (!msg) return '—';
    return msg.length > max ? `${msg.slice(0, max)}…` : msg;
}

async function load(page = 1) {
    loading.value = true;
    err.value = '';
    try {
        const r = await api.get('/admin/system-logs', {
            auth: true,
            params: {
                level: level.value,
                channel: channel.value,
                q: q.value || undefined,
                page,
                per_page: 25,
            },
        });
        logs.value = r.data || [];
        meta.value = r.meta || meta.value;
        if (r.message) {
            err.value = r.message;
        }
    } catch (e) {
        err.value = e.message || 'No se pudieron cargar los logs.';
        logs.value = [];
    } finally {
        loading.value = false;
    }
}

async function openDetail(row) {
    detail.value = { ...row };
    detailLoading.value = true;
    err.value = '';
    try {
        const r = await api.get(`/admin/system-logs/${row.id}`, { auth: true });
        detail.value = r.data || row;
    } catch (e) {
        err.value = e.message;
    } finally {
        detailLoading.value = false;
    }
}

function closeDetail() {
    detail.value = null;
}

function exportCsv() {
    const rows = logs.value || [];
    if (!rows.length) return;
    const header = ['id', 'fecha', 'nivel', 'canal', 'mensaje', 'excepcion', 'http', 'metodo', 'ruta', 'usuario', 'ip'];
    const lines = [header.join(',')];
    for (const l of rows) {
        const cols = [
            l.id,
            l.created_at,
            l.level,
            l.channel,
            `"${(l.message || '').replace(/"/g, '""')}"`,
            l.exception_class || '',
            l.http_status ?? '',
            l.request_method || '',
            l.request_path || '',
            l.user?.email || '',
            l.ip || '',
        ];
        lines.push(cols.join(','));
    }
    const blob = new Blob([`\uFEFF${lines.join('\n')}`], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `system-logs-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

onMounted(() => load(1));
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-6 pb-24">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Sistema</h1>
            <p class="text-slate-600 mt-1 text-sm">
                Registro de fallas y errores capturados automáticamente. Útil para diagnosticar incidencias en producción.
            </p>
        </div>

        <AppAlert v-if="err" variant="error" class="mb-4">{{ err }}</AppAlert>

        <div class="flex flex-wrap gap-2 mb-4">
            <input
                v-model="q"
                type="search"
                placeholder="Buscar mensaje, clase, ruta…"
                class="flex-1 min-w-[200px] rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                @keyup.enter="load(1)"
            />
            <select v-model="level" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="load(1)">
                <option value="all">Todos los niveles</option>
                <option value="error">Error</option>
                <option value="warning">Advertencia</option>
                <option value="critical">Crítico</option>
                <option value="info">Info</option>
            </select>
            <select v-model="channel" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" @change="load(1)">
                <option value="all">Todos los canales</option>
                <option value="api">API</option>
                <option value="app">App</option>
            </select>
            <AppButton variant="ghost" @click="exportCsv">Exportar CSV</AppButton>
            <AppButton variant="primary" @click="load(1)">Buscar</AppButton>
        </div>

        <p class="text-sm text-slate-500 mb-3">
            {{ meta.total ?? 0 }} registro(s) · página {{ meta.current_page }} de {{ meta.last_page }}
        </p>

        <p v-if="loading" class="text-slate-500 py-12 text-center">Cargando logs…</p>
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm min-w-[800px]">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">ID</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Nivel</th>
                        <th class="text-left px-4 py-3">Canal</th>
                        <th class="text-left px-4 py-3">Mensaje</th>
                        <th class="text-left px-4 py-3">HTTP</th>
                        <th class="text-left px-4 py-3">Ruta</th>
                        <th class="text-left px-4 py-3">Usuario</th>
                        <th class="text-right px-4 py-3">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!logs.length">
                        <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                            Sin registros. Los errores nuevos aparecerán aquí automáticamente.
                        </td>
                    </tr>
                    <tr
                        v-for="l in logs"
                        :key="l.id"
                        class="border-t border-slate-100 hover:bg-slate-50/50 cursor-pointer"
                        @click="openDetail(l)"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">#{{ l.id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs">{{ fmtDate(l.created_at) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold uppercase"
                                :class="levelClass[l.level] || 'bg-slate-100 text-slate-700'"
                            >
                                {{ l.level }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ l.channel }}</td>
                        <td class="px-4 py-3 max-w-[240px]" :title="l.message">{{ shortMsg(l.message) }}</td>
                        <td class="px-4 py-3 text-xs">{{ l.http_status ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-mono max-w-[160px] truncate" :title="l.request_path">
                            {{ l.request_method ? `${l.request_method} ` : '' }}{{ l.request_path || '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <span v-if="l.user">{{ l.user.email }}</span>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <AppButton variant="ghost" size="sm" @click.stop="openDetail(l)">Ver</AppButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="(meta.last_page || 1) > 1" class="flex flex-wrap justify-center gap-2 mt-6">
            <AppButton
                v-for="p in pages"
                :key="p"
                :variant="p === meta.current_page ? 'primary' : 'ghost'"
                size="sm"
                @click="load(p)"
            >
                {{ p }}
            </AppButton>
        </div>

        <div
            v-if="detail"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 p-4"
            @click.self="closeDetail"
        >
            <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                <div class="flex justify-between items-start gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Log #{{ detail.id }}</h2>
                        <p class="text-sm text-slate-500">{{ fmtDate(detail.created_at) }}</p>
                    </div>
                    <button type="button" class="text-slate-500 hover:text-slate-800 text-xl leading-none" @click="closeDetail">×</button>
                </div>

                <p v-if="detailLoading" class="text-slate-500 py-8 text-center">Cargando detalle…</p>
                <template v-else>
                    <dl class="grid gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">Nivel / canal</dt>
                            <dd>{{ detail.level }} · {{ detail.channel }}</dd>
                        </div>
                        <div v-if="detail.http_status">
                            <dt class="text-xs font-bold uppercase text-slate-500">HTTP</dt>
                            <dd>{{ detail.http_status }} — {{ detail.request_method }} {{ detail.request_path }}</dd>
                        </div>
                        <div v-if="detail.user">
                            <dt class="text-xs font-bold uppercase text-slate-500">Usuario</dt>
                            <dd>{{ detail.user.full_name }} ({{ detail.user.email }}) · {{ detail.user.role }}</dd>
                        </div>
                        <div v-if="detail.ip">
                            <dt class="text-xs font-bold uppercase text-slate-500">IP</dt>
                            <dd class="font-mono">{{ detail.ip }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase text-slate-500">Mensaje</dt>
                            <dd class="whitespace-pre-wrap break-words">{{ detail.message }}</dd>
                        </div>
                        <div v-if="detail.exception_class">
                            <dt class="text-xs font-bold uppercase text-slate-500">Excepción</dt>
                            <dd class="font-mono text-xs break-all">{{ detail.exception_class }}</dd>
                            <dd v-if="detail.file" class="text-xs text-slate-600 mt-1">{{ detail.file }}:{{ detail.line }}</dd>
                        </div>
                        <div v-if="detail.context && Object.keys(detail.context).length">
                            <dt class="text-xs font-bold uppercase text-slate-500">Contexto</dt>
                            <dd>
                                <pre class="text-xs bg-slate-50 rounded-lg p-3 overflow-x-auto">{{ JSON.stringify(detail.context, null, 2) }}</pre>
                            </dd>
                        </div>
                        <div v-if="detail.trace">
                            <dt class="text-xs font-bold uppercase text-slate-500">Stack trace</dt>
                            <dd>
                                <pre class="text-xs bg-slate-900 text-slate-100 rounded-lg p-3 overflow-x-auto max-h-64">{{ detail.trace }}</pre>
                            </dd>
                        </div>
                    </dl>
                </template>
            </div>
        </div>
    </div>
</template>
