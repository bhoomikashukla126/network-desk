<template>
    <div class="space-y-6">
        <section class="app-card rounded-2xl border p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-theme-heading">{{ $t('dashboard.title') }}</h1>
                    <p class="mt-1 text-sm text-theme-muted">{{ $t('dashboard.description') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-theme bg-theme-background px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-theme-muted">{{ $t('dashboard.stats.points') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-theme-heading">
                            {{ summary.filtered_points ?? summary.total_points ?? 0 }}
                        </p>
                        <p v-if="hasActiveFilters" class="mt-0.5 text-[11px] text-theme-muted">
                            {{ $t('dashboard.stats.ofTotal', { count: summary.total_points ?? 0 }) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-theme bg-theme-background px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-theme-muted">{{ $t('dashboard.stats.cables') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-theme-heading">
                            {{ summary.filtered_cables ?? summary.total_cables ?? 0 }}
                        </p>
                        <p v-if="hasActiveFilters" class="mt-0.5 text-[11px] text-theme-muted">
                            {{ $t('dashboard.stats.ofTotal', { count: summary.total_cables ?? 0 }) }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-theme bg-theme-background px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-theme-muted">{{ $t('dashboard.stats.images') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-theme-heading">{{ summary.total_images ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-theme bg-theme-background px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-theme-muted">{{ $t('dashboard.stats.filtered') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-theme-heading">{{ summary.filtered_points ?? 0 }}</p>
                        <p class="mt-0.5 text-[11px] text-theme-muted">{{ $t('dashboard.stats.onMap') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-theme pt-5">
                <button
                    type="button"
                    class="btn-secondary inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold shadow-sm transition"
                    @click="filtersModalOpen = true"
                >
                    <SlidersHorizontal class="h-4 w-4 shrink-0" />
                    {{ $t('dashboard.openFilters') }}
                    <span
                        v-if="activeFilterCount"
                        class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-theme-primary px-1.5 py-0.5 text-[11px] font-bold text-white"
                    >
                        {{ activeFilterCount }}
                    </span>
                </button>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="btn-danger-outline rounded-lg border px-3 py-2 text-sm font-semibold transition"
                    @click="resetFilters"
                >
                    {{ $t('dashboard.resetFilters') }}
                </button>

                <p v-if="hasActiveFilters" class="text-sm text-theme-muted">
                    {{ $t('dashboard.filteredResults', {
                        points: summary.filtered_points ?? 0,
                        cables: summary.filtered_cables ?? 0,
                    }) }}
                </p>
            </div>
        </section>

        <div class="app-card h-[580px] min-h-[580px] overflow-hidden rounded-2xl border shadow-sm">
            <NetworkMap
                :points="points"
                :cables="displayCables"
                :loading="loading"
                :fit-on-update="true"
                :layout-visible="!filtersModalOpen"
                @select-point="openPoint"
            />
        </div>

        <DashboardFiltersModal
            :open="filtersModalOpen"
            :filters="filters"
            :filter-options="filterOptions"
            :summary="summary"
            @close="filtersModalOpen = false"
            @apply="applyFilters"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { SlidersHorizontal } from 'lucide-vue-next';
import { api } from '../api/client';
import DashboardFiltersModal from '../components/network/DashboardFiltersModal.vue';
import NetworkMap from '../components/network/NetworkMap.vue';
import { buildQuery, setCableTypeCatalog } from '../utils/networkMap';

defineProps({
    session: { type: Object, default: null },
});

const router = useRouter();
const loading = ref(true);
const filtersModalOpen = ref(false);
const points = ref([]);
const cables = ref([]);
const summary = ref({});
const filterOptions = ref({
    types: {},
    statuses: {},
    cable_types: {},
    cable_type_colors: {},
    cable_statuses: {},
});

const filters = reactive({
    type: '',
    status: '',
    cable_type: '',
    cable_status: '',
    show_cables: true,
});

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.type) {
        count += 1;
    }

    if (filters.status) {
        count += 1;
    }

    if (filters.cable_type) {
        count += 1;
    }

    if (filters.cable_status) {
        count += 1;
    }

    if (!filters.show_cables) {
        count += 1;
    }

    return count;
});

const displayCables = computed(() => (filters.show_cables ? cables.value : []));

watch(
    () => filterOptions.value.cable_types,
    () => {
        setCableTypeCatalog(
            filterOptions.value.cable_types,
            filterOptions.value.cable_type_colors,
        );
    },
    { deep: true },
);

async function loadDashboard() {
    loading.value = true;

    try {
        const query = buildQuery({
            type: filters.type,
            status: filters.status,
            cable_type: filters.cable_type,
            cable_status: filters.cable_status,
            show_cables: filters.show_cables ? '1' : '0',
        });
        const response = await api(`/api/network/dashboard${query}`);
        points.value = response.points ?? [];
        cables.value = response.cables ?? [];
        summary.value = response.summary ?? {};
        filterOptions.value = response.filters ?? filterOptions.value;
        setCableTypeCatalog(
            filterOptions.value.cable_types,
            filterOptions.value.cable_type_colors,
        );
    } finally {
        loading.value = false;
    }
}

function applyFilters(nextFilters) {
    Object.assign(filters, nextFilters);
    filtersModalOpen.value = false;
    loadDashboard();
}

function resetFilters() {
    filters.type = '';
    filters.status = '';
    filters.cable_type = '';
    filters.cable_status = '';
    filters.show_cables = true;
    loadDashboard();
}

function openPoint(point) {
    router.push({ name: 'map.index', query: { point: point.id } });
}

onMounted(loadDashboard);
</script>
