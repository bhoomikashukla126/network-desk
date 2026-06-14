<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[200] flex items-end justify-center bg-slate-900/50 p-0 sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                :aria-label="$t('dashboard.filters')"
                @click.self="close"
            >
                <div
                    class="app-card flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl border shadow-2xl sm:max-h-[85vh] sm:max-w-2xl sm:rounded-2xl"
                    @click.stop
                >
                    <div class="flex shrink-0 items-start justify-between gap-3 border-b border-theme px-4 py-4 sm:px-6">
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-theme-heading">{{ $t('dashboard.filters') }}</h2>
                            <p class="mt-1 text-sm text-theme-muted">{{ $t('dashboard.filtersHint') }}</p>
                        </div>
                        <button
                            type="button"
                            class="btn-secondary rounded-lg border p-2 transition"
                            :aria-label="$t('common.cancel')"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                        <div class="space-y-5">
                            <section>
                                <div class="mb-2 flex items-center gap-2">
                                    <MapPin class="h-4 w-4 text-theme-primary" />
                                    <h3 class="text-sm font-semibold text-theme-heading">{{ $t('dashboard.sections.points') }}</h3>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition"
                                        :class="draft.type === '' ? 'app-filter-active' : 'app-filter-inactive'"
                                        @click="draft.type = ''"
                                    >
                                        {{ $t('dashboard.allTypes') }}
                                        <span class="opacity-80">({{ totalPointCount }})</span>
                                    </button>
                                    <button
                                        v-for="(label, key) in filterOptions.types"
                                        :key="key"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition"
                                        :class="draft.type === key ? 'app-filter-active' : 'app-filter-inactive'"
                                        @click="draft.type = key"
                                    >
                                        <span
                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full"
                                            :class="draft.type === key ? 'bg-white/20' : ''"
                                            :style="draft.type === key ? undefined : { background: pointColor(key), color: '#fff' }"
                                        >
                                            <NetworkPointTypeIcon :type="key" size-class="h-3 w-3" />
                                        </span>
                                        {{ label }}
                                        <span class="opacity-80">({{ summary.by_type?.[key] ?? 0 }})</span>
                                    </button>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                                        :class="draft.status === '' ? 'app-filter-active' : 'app-filter-inactive'"
                                        @click="draft.status = ''"
                                    >
                                        {{ $t('dashboard.allStatuses') }}
                                    </button>
                                    <button
                                        v-for="(label, key) in filterOptions.statuses"
                                        :key="key"
                                        type="button"
                                        class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                                        :class="draft.status === key ? pointStatusFilterClass(key) : 'app-filter-inactive'"
                                        @click="draft.status = key"
                                    >
                                        {{ label }}
                                        <span class="ml-1 opacity-80">({{ summary.by_status?.[key] ?? 0 }})</span>
                                    </button>
                                </div>
                            </section>

                            <section>
                                <div class="mb-2 flex items-center gap-2">
                                    <Cable class="h-4 w-4 text-theme-primary" />
                                    <h3 class="text-sm font-semibold text-theme-heading">{{ $t('dashboard.sections.cables') }}</h3>
                                </div>

                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium transition"
                                        :class="draft.show_cables ? 'app-filter-active' : 'app-filter-inactive'"
                                        @click="toggleShowCables"
                                    >
                                        <span
                                            class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition"
                                            :class="draft.show_cables ? 'bg-white/30' : 'bg-theme-muted/30'"
                                        >
                                            <span
                                                class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition"
                                                :class="draft.show_cables ? 'left-4' : 'left-0.5'"
                                            />
                                        </span>
                                        {{ $t('dashboard.showCables') }}
                                    </button>
                                </div>

                                <div
                                    class="space-y-3 transition"
                                    :class="draft.show_cables ? '' : 'pointer-events-none opacity-50'"
                                >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.cable_type === '' ? 'app-filter-active' : 'app-filter-inactive'"
                                            :disabled="!draft.show_cables"
                                            @click="draft.cable_type = ''"
                                        >
                                            {{ $t('dashboard.allCableTypes') }}
                                            <span class="opacity-80">({{ summary.total_cables ?? 0 }})</span>
                                        </button>
                                        <button
                                            v-for="(label, key) in filterOptions.cable_types"
                                            :key="key"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.cable_type === key ? 'app-filter-active' : 'app-filter-inactive'"
                                            :disabled="!draft.show_cables"
                                            @click="draft.cable_type = key"
                                        >
                                            <span
                                                class="h-1.5 w-4 shrink-0 rounded-full"
                                                :style="{ background: cableTypeColorFor(key) }"
                                            />
                                            {{ label }}
                                            <span class="opacity-80">({{ summary.by_cable_type?.[key] ?? 0 }})</span>
                                        </button>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.cable_status === '' ? 'app-filter-active' : 'app-filter-inactive'"
                                            :disabled="!draft.show_cables"
                                            @click="draft.cable_status = ''"
                                        >
                                            {{ $t('dashboard.allCableStatuses') }}
                                        </button>
                                        <button
                                            v-for="(label, key) in filterOptions.cable_statuses"
                                            :key="key"
                                            type="button"
                                            class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.cable_status === key ? 'app-filter-active' : 'app-filter-inactive'"
                                            :disabled="!draft.show_cables"
                                            @click="draft.cable_status = key"
                                        >
                                            {{ label }}
                                            <span class="ml-1 opacity-80">({{ summary.by_cable_status?.[key] ?? 0 }})</span>
                                        </button>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-theme px-4 py-4 sm:px-6">
                        <button
                            type="button"
                            class="btn-danger-outline rounded-lg border px-4 py-2 text-sm font-semibold transition"
                            @click="clearDraft"
                        >
                            {{ $t('dashboard.resetFilters') }}
                        </button>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn-secondary rounded-lg border px-4 py-2 text-sm font-semibold transition"
                                @click="close"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="button"
                                class="btn-primary rounded-lg px-4 py-2 text-sm font-semibold transition"
                                @click="apply"
                            >
                                {{ $t('dashboard.applyFilters') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { Cable, MapPin, X } from 'lucide-vue-next';
import NetworkPointTypeIcon from './NetworkPointTypeIcon.vue';
import { cableTypeColor, pointColor } from '../../utils/networkMap';

const props = defineProps({
    open: { type: Boolean, default: false },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'apply']);

const draft = reactive(emptyFilters());

const totalPointCount = computed(() => (
    Object.values(props.summary.by_type ?? {}).reduce((total, count) => total + Number(count), 0)
));

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        Object.assign(draft, {
            type: props.filters.type ?? '',
            status: props.filters.status ?? '',
            cable_type: props.filters.cable_type ?? '',
            cable_status: props.filters.cable_status ?? '',
            show_cables: props.filters.show_cables !== false,
        });
        document.body.classList.add('overflow-hidden');

        return;
    }

    document.body.classList.remove('overflow-hidden');
});

function emptyFilters() {
    return {
        type: '',
        status: '',
        cable_type: '',
        cable_status: '',
        show_cables: true,
    };
}

function cableTypeColorFor(type) {
    const custom = props.filterOptions.cable_type_colors?.[type];

    return custom ?? cableTypeColor(type);
}

function pointStatusFilterClass(status) {
    return {
        active: 'app-filter-active',
        planned: 'app-filter-active',
        maintenance: 'bg-amber-600 text-white',
        inactive: 'border border-theme bg-theme-muted text-theme-heading',
        damaged: 'bg-rose-600 text-white',
    }[status] ?? 'app-filter-active';
}

function toggleShowCables() {
    draft.show_cables = !draft.show_cables;

    if (!draft.show_cables) {
        draft.cable_type = '';
        draft.cable_status = '';
    }
}

function clearDraft() {
    Object.assign(draft, emptyFilters());
}

function close() {
    emit('close');
}

function apply() {
    emit('apply', { ...draft });
}
</script>
