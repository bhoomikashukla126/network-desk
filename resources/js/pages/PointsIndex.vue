<template>
    <div class="space-y-6">
        <section class="app-card rounded-2xl border p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-theme-heading">{{ t('points.title') }}</h1>
                    <p class="mt-1 text-sm text-theme-muted">{{ t('points.description') }}</p>
                </div>

                <RouterLink
                    v-if="permissions.create"
                    :to="{ name: 'map.index' }"
                    class="btn-primary inline-flex shrink-0 items-center justify-center gap-2 rounded-lg px-2.5 py-2.5 text-sm font-semibold shadow-sm transition sm:px-4"
                    :aria-label="t('points.addOnMap')"
                    :title="t('points.addOnMap')"
                >
                    <MapPinPlus class="h-4 w-4 shrink-0" />
                    <span class="hidden sm:inline">{{ t('points.addOnMap') }}</span>
                </RouterLink>
            </div>

            <div class="mt-5">
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('points.searchPlaceholder')"
                    class="app-input w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm sm:max-w-sm"
                    @input="debouncedLoad"
                >
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition"
                    :class="filters.type === '' ? 'app-filter-active' : 'app-filter-inactive'"
                    @click="setType('')"
                >
                    {{ t('points.allTypes') }}
                    <span class="opacity-80">({{ filterStats.total }})</span>
                </button>
                <button
                    v-for="(label, key) in meta.types"
                    :key="key"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition"
                    :class="filters.type === key ? 'app-filter-active' : 'app-filter-inactive'"
                    @click="setType(key)"
                >
                    <span
                        class="inline-flex h-5 w-5 items-center justify-center rounded-full"
                        :class="filters.type === key ? 'bg-white/20' : ''"
                        :style="filters.type === key ? undefined : { background: pointColor(key), color: '#fff' }"
                    >
                        <NetworkPointTypeIcon :type="key" size-class="h-3 w-3" />
                    </span>
                    {{ label }}
                    <span class="opacity-80">({{ filterStats.by_type?.[key] ?? 0 }})</span>
                </button>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                    :class="filters.status === '' ? 'app-filter-active' : 'app-filter-inactive'"
                    @click="setStatus('')"
                >
                    {{ t('points.allStatuses') }}
                </button>
                <button
                    v-for="(label, key) in meta.statuses"
                    :key="key"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                    :class="filters.status === key ? statusFilterClass(key) : 'app-filter-inactive'"
                    @click="setStatus(key)"
                >
                    {{ label }}
                    <span class="ml-1 opacity-80">({{ filterStats.by_status?.[key] ?? 0 }})</span>
                </button>
            </div>

            <div v-if="hasActiveFilters" class="mt-4">
                <button
                    type="button"
                    class="btn-danger-outline rounded-full px-3 py-1.5 text-sm font-medium transition"
                    @click="clearFilters"
                >
                    {{ t('points.clearFilters') }}
                </button>
            </div>

            <div v-if="loading" class="mt-6 flex items-center justify-center py-16 text-theme-muted">
                <LoaderCircle class="h-5 w-5 animate-spin" />
                <span class="ml-2">{{ t('points.loading') }}</span>
            </div>

            <div v-else-if="error" class="app-alert-error mt-6 rounded-xl border px-4 py-3 text-sm">
                {{ error }}
            </div>

            <div v-else-if="points.length === 0" class="py-16 text-center">
                <MapPin class="mx-auto h-10 w-10 text-theme-muted opacity-50" />
                <p class="mt-3 text-base font-medium text-theme-body">{{ t('points.noResults') }}</p>
                <p v-if="hasActiveFilters || search.trim()" class="mt-1 text-sm text-theme-muted">{{ t('points.tryDifferentFilters') }}</p>
            </div>

            <div v-else class="mt-6 space-y-4">
                <div class="hidden overflow-x-auto rounded-xl border border-theme md:block">
                    <table class="min-w-full divide-y divide-theme text-sm">
                        <thead class="app-table-head text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ t('points.table.name') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('points.table.type') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('points.table.status') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('points.table.area') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('points.table.contact') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('points.table.photos') }}</th>
                                <th class="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-theme bg-theme-card">
                            <tr v-for="point in points" :key="point.id" class="app-table-row">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-theme-heading">{{ point.name }}</p>
                                    <p v-if="point.address" class="mt-0.5 line-clamp-1 text-xs text-theme-muted">{{ point.address }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <NetworkPointTypeBadges :point="point" :labels="meta.types" />
                                </td>
                                <td class="px-4 py-3">
                                    <NetworkPointStatusBadge
                                        :status="point.status"
                                        :label="meta.statuses?.[point.status] || point.status"
                                    />
                                </td>
                                <td class="px-4 py-3 text-theme-body">{{ point.area || '—' }}</td>
                                <td class="px-4 py-3 text-theme-body">
                                    <p v-if="point.contact_name">{{ point.contact_name }}</p>
                                    <p v-if="point.contact_phone" class="text-xs text-theme-muted">{{ point.contact_phone }}</p>
                                    <span v-if="!point.contact_name && !point.contact_phone">—</span>
                                </td>
                                <td class="px-4 py-3 text-theme-body">{{ point.images?.length ?? 0 }}</td>
                                <td class="px-4 py-3 text-right">
                                    <RouterLink
                                        :to="{ name: 'map.index', query: { point: point.id } }"
                                        class="link-theme font-medium"
                                    >
                                        {{ t('points.openOnMap') }}
                                    </RouterLink>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-3 md:hidden">
                    <article
                        v-for="point in points"
                        :key="point.id"
                        class="app-card rounded-xl border p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-theme-heading">{{ point.name }}</p>
                                <p v-if="point.area" class="mt-1 text-sm text-theme-muted">{{ point.area }}</p>
                            </div>
                            <NetworkPointStatusBadge
                                :status="point.status"
                                :label="meta.statuses?.[point.status] || point.status"
                            />
                        </div>

                        <div class="mt-3">
                            <NetworkPointTypeBadges :point="point" :labels="meta.types" />
                        </div>

                        <p v-if="point.contact_name || point.contact_phone" class="mt-2 text-sm text-theme-body">
                            {{ point.contact_name }}
                            <span v-if="point.contact_phone" class="text-theme-muted"> · {{ point.contact_phone }}</span>
                        </p>

                        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                            <span class="text-theme-muted">{{ t('points.table.photos') }}: {{ point.images?.length ?? 0 }}</span>
                            <RouterLink
                                :to="{ name: 'map.index', query: { point: point.id } }"
                                class="link-theme font-medium"
                            >
                                {{ t('points.openOnMap') }}
                            </RouterLink>
                        </div>
                    </article>
                </div>

                <Pagination :meta="pagination" :label="t('points.paginationLabel')" @change="loadPoints" />
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { LoaderCircle, MapPin, MapPinPlus } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { api } from '../api/client';
import NetworkPointStatusBadge from '../components/NetworkPointStatusBadge.vue';
import NetworkPointTypeBadges from '../components/network/NetworkPointTypeBadges.vue';
import NetworkPointTypeIcon from '../components/network/NetworkPointTypeIcon.vue';
import Pagination from '../components/Pagination.vue';
import { pointColor } from '../utils/networkMap';

const { t } = useI18n();

const loading = ref(true);
const error = ref('');
const search = ref('');
const points = ref([]);
const meta = ref({ types: {}, statuses: {} });
const permissions = ref({ create: false });
const filterStats = ref({ total: 0, by_type: {}, by_status: {} });
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 });
const filters = reactive({ type: '', status: '' });

let debounceTimer = null;

const hasActiveFilters = computed(() => Boolean(filters.type || filters.status));

function statusFilterClass(status) {
    return {
        active: 'app-filter-active',
        planned: 'app-filter-active',
        maintenance: 'bg-amber-600 text-white',
        inactive: 'border border-theme bg-theme-muted text-theme-heading',
    }[status] ?? 'app-filter-active';
}

function buildParams(page = 1) {
    const params = new URLSearchParams({ page: String(page), per_page: '10' });

    if (search.value.trim()) {
        params.set('search', search.value.trim());
    }

    if (filters.type) {
        params.set('type', filters.type);
    }

    if (filters.status) {
        params.set('status', filters.status);
    }

    return params;
}

async function loadMeta() {
    meta.value = await api('/api/network/meta');
}

async function loadPoints(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await api(`/api/network/points?${buildParams(page).toString()}`);
        points.value = response.data ?? [];
        filterStats.value = response.filter_stats ?? filterStats.value;
        pagination.value = response.meta ?? pagination.value;
        permissions.value = response.permissions ?? permissions.value;
    } catch (err) {
        error.value = err.message ?? t('points.loadFailed');
    } finally {
        loading.value = false;
    }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadPoints(1), 300);
}

function setType(type) {
    filters.type = type;
    loadPoints(1);
}

function setStatus(status) {
    filters.status = status;
    loadPoints(1);
}

function clearFilters() {
    filters.type = '';
    filters.status = '';
    search.value = '';
    loadPoints(1);
}

onMounted(async () => {
    await loadMeta();
    await loadPoints();
});
</script>
