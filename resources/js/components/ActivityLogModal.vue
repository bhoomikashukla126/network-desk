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
                class="fixed inset-0 z-[100] flex bg-slate-900/50 sm:items-center sm:justify-center sm:p-4"
                @click.self="close"
            >
                <div
                    class="flex h-[100dvh] w-full min-h-0 flex-col overflow-hidden border-slate-200 bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-4xl sm:rounded-2xl sm:border"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="modalTitle"
                >
                    <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-6">
                        <div class="min-w-0 pr-2">
                            <h2 class="text-lg font-bold text-slate-900 sm:text-xl">{{ modalTitle }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ modalDescription }}</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            :aria-label="t('activity.close')"
                            @click="close"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="shrink-0 border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" :class="canViewAll ? 'lg:grid-cols-4' : 'lg:grid-cols-3'">
                            <div v-if="canViewAll">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('activity.user') }}</label>
                                <select
                                    v-model="filters.central_user_id"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                    @change="applyFilters"
                                >
                                    <option value="">{{ t('activity.allUsers') }}</option>
                                    <option v-for="actor in actors" :key="actor.central_user_id" :value="actor.central_user_id">
                                        {{ actor.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('activity.action') }}</label>
                                <select
                                    v-model="filters.action"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                    @change="applyFilters"
                                >
                                    <option value="">{{ t('activity.allActions') }}</option>
                                    <option v-for="option in translatedActionOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('activity.fromDate') }}</label>
                                <input
                                    v-model="filters.date_from"
                                    type="date"
                                    class="app-input w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm"
                                    @change="applyFilters"
                                >
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('activity.toDate') }}</label>
                                <input
                                    v-model="filters.date_to"
                                    type="date"
                                    class="app-input w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm"
                                    @change="applyFilters"
                                >
                            </div>
                        </div>

                        <div v-if="hasActiveFilters" class="mt-3">
                            <button
                                type="button"
                                class="rounded-full bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                                @click="clearFilters"
                            >
                                {{ t('activity.clearFilters') }}
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-6">
                        <div v-if="loading" class="flex items-center justify-center py-16 text-slate-500">
                            <LoaderCircle class="h-5 w-5 animate-spin" />
                            <span class="ml-2">{{ t('activity.loading') }}</span>
                        </div>

                        <div v-else-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ error }}
                        </div>

                        <div v-else-if="logs.length === 0" class="py-16 text-center">
                            <ScrollText class="mx-auto h-10 w-10 text-slate-300" />
                            <p class="mt-3 text-base font-medium text-slate-700">{{ t('activity.noActivityFound') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ emptyMessage }}</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="hidden overflow-x-auto rounded-xl border border-slate-200 md:block">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 font-medium">{{ t('activity.when') }}</th>
                                            <th v-if="canViewAll" class="px-4 py-3 font-medium">{{ t('activity.user') }}</th>
                                            <th class="px-4 py-3 font-medium">{{ t('activity.action') }}</th>
                                            <th class="px-4 py-3 font-medium">{{ t('activity.details') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/80">
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ formatDate(log.created_at) }}</td>
                                            <td v-if="canViewAll" class="px-4 py-3 font-medium text-slate-900">{{ log.actor_name }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="actionBadgeClass(log.action)">
                                                    {{ translateActionLabel(log.action, log.action_label) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">
                                                <p>{{ log.description }}</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="space-y-3 md:hidden">
                                <article
                                    v-for="log in logs"
                                    :key="log.id"
                                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                                >
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                                        <div class="min-w-0">
                                            <p v-if="canViewAll" class="font-medium text-slate-900">{{ log.actor_name }}</p>
                                            <p class="text-xs text-slate-500" :class="{ 'mt-0.5': canViewAll }">{{ formatDate(log.created_at) }}</p>
                                        </div>
                                        <span class="inline-flex w-fit max-w-full rounded-full px-2.5 py-1 text-xs font-semibold leading-snug" :class="actionBadgeClass(log.action)">
                                            {{ translateActionLabel(log.action, log.action_label) }}
                                        </span>
                                    </div>
                                    <p class="mt-3 break-words text-sm text-slate-700">{{ log.description }}</p>
                                </article>
                            </div>

                            <Pagination :meta="meta" :label="t('activity.paginationLabel')" @change="changePage" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { LoaderCircle, ScrollText, X } from 'lucide-vue-next';
import { api } from '../api/client';
import Pagination from './Pagination.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    session: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const { t } = useI18n();

const loading = ref(false);
const error = ref('');
const canViewAll = ref(false);
const logs = ref([]);
const actors = ref([]);
const actionOptions = ref([]);
const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: 0,
    from: 0,
    to: 0,
});

const filters = ref({
    central_user_id: '',
    action: '',
    date_from: '',
    date_to: '',
});

const showAllScope = computed(() => canViewAll.value || props.session?.can_view_activity);

const modalTitle = computed(() => (
    showAllScope.value ? t('profile.workspaceActivity') : t('profile.myActivity')
));
const modalDescription = computed(() => (
    showAllScope.value ? t('activity.descriptionAll') : t('activity.descriptionOwn')
));
const emptyMessage = computed(() => (
    showAllScope.value ? t('activity.emptyAll') : t('activity.emptyOwn')
));

const translatedActionOptions = computed(() =>
    actionOptions.value.map((option) => ({
        ...option,
        label: translateActionLabel(option.value, option.label),
    })),
);

const hasActiveFilters = computed(() => (
    (canViewAll.value && filters.value.central_user_id !== '')
    || filters.value.action !== ''
    || filters.value.date_from !== ''
    || filters.value.date_to !== ''
));

function translateActionLabel(action, fallback) {
    const i18nKey = `activity.actions.${action}`;
    const translated = t(i18nKey);

    return translated !== i18nKey ? translated : fallback;
}

function setBodyScrollLocked(locked) {
    document.body.style.overflow = locked ? 'hidden' : '';
}

function onEscape(event) {
    if (event.key === 'Escape' && props.open) {
        close();
    }
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        loadActivity();
        setBodyScrollLocked(true);
        document.addEventListener('keydown', onEscape);
    } else {
        setBodyScrollLocked(false);
        document.removeEventListener('keydown', onEscape);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', onEscape);
    setBodyScrollLocked(false);
});

function close() {
    emit('close');
}

function actionBadgeClass(action) {
    if (action.endsWith('.created')) {
        return 'bg-emerald-50 text-emerald-700';
    }

    if (action.endsWith('.deleted')) {
        return 'bg-rose-50 text-rose-700';
    }

    return 'bg-sky-50 text-sky-700';
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function buildQuery(page = 1) {
    const params = new URLSearchParams({ page: String(page), per_page: '25' });

    if (filters.value.central_user_id) {
        params.set('central_user_id', filters.value.central_user_id);
    }

    if (filters.value.action) {
        params.set('action', filters.value.action);
    }

    if (filters.value.date_from) {
        params.set('date_from', filters.value.date_from);
    }

    if (filters.value.date_to) {
        params.set('date_to', filters.value.date_to);
    }

    return params.toString();
}

async function loadActivity(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await api(`/api/activity?${buildQuery(page)}`);
        logs.value = response.data ?? [];
        meta.value = response.meta ?? meta.value;
        canViewAll.value = response.can_view_all ?? false;
        actors.value = response.actors ?? [];
        actionOptions.value = response.action_options ?? [];
    } catch (err) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    loadActivity(1);
}

function clearFilters() {
    filters.value = {
        central_user_id: '',
        action: '',
        date_from: '',
        date_to: '',
    };
    loadActivity(1);
}

function changePage(page) {
    loadActivity(page);
}
</script>
