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
                    class="app-card flex h-[100dvh] w-full min-h-0 flex-col overflow-hidden border shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-2xl sm:rounded-2xl"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="t('bugReport.title')"
                >
                    <div class="flex shrink-0 items-start justify-between gap-3 border-b border-theme px-4 py-4 sm:px-6">
                        <div class="min-w-0 pr-2">
                            <h2 class="text-lg font-bold text-theme-heading sm:text-xl">{{ t('bugReport.title') }}</h2>
                            <p class="mt-1 text-sm text-theme-muted">{{ t('bugReport.description') }}</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-theme-muted transition hover:bg-theme-background hover:text-theme-body"
                            :aria-label="t('bugReport.close')"
                            @click="close"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6">
                        <div v-if="loading" class="flex items-center justify-center py-16 text-theme-muted">
                            <LoaderCircle class="h-6 w-6 animate-spin" />
                            <span class="ml-2">{{ t('common.loading') }}</span>
                        </div>

                        <template v-else>
                            <div v-if="loadError" class="app-alert-error mb-4 rounded-xl border px-4 py-3 text-sm">
                                {{ loadError }}
                            </div>

                            <div v-if="successMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                {{ successMessage }}
                            </div>

                            <form class="space-y-4" @submit.prevent="submit">
                                <div>
                                    <label for="bug-title" class="text-sm font-medium text-theme-heading">{{ t('bugReport.issueTitle') }}</label>
                                    <input
                                        id="bug-title"
                                        v-model="form.title"
                                        type="text"
                                        maxlength="200"
                                        required
                                        class="app-input mt-1 w-full rounded-xl border px-4 py-3 text-sm"
                                        :placeholder="t('bugReport.issueTitlePlaceholder')"
                                    >
                                </div>

                                <div>
                                    <label for="bug-description" class="text-sm font-medium text-theme-heading">{{ t('bugReport.issueDescription') }}</label>
                                    <textarea
                                        id="bug-description"
                                        v-model="form.description"
                                        rows="5"
                                        maxlength="10000"
                                        required
                                        class="app-input mt-1 w-full rounded-xl border px-4 py-3 text-sm"
                                        :placeholder="t('bugReport.issueDescriptionPlaceholder')"
                                    />
                                </div>

                                <p class="text-xs text-theme-muted">{{ t('bugReport.pageUrlHint', { url: pageUrl }) }}</p>

                                <div v-if="submitError" class="app-alert-error rounded-xl border px-4 py-3 text-sm">
                                    {{ submitError }}
                                </div>

                                <button
                                    type="submit"
                                    class="btn-primary inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold disabled:opacity-60"
                                    :disabled="submitting"
                                >
                                    <LoaderCircle v-if="submitting" class="h-4 w-4 animate-spin" />
                                    {{ t('bugReport.submit') }}
                                </button>
                            </form>

                            <div class="mt-8 border-t border-theme pt-6">
                                <h3 class="text-sm font-semibold text-theme-heading">{{ t('bugReport.myReports') }}</h3>
                                <p class="mt-1 text-xs text-theme-muted">{{ t('bugReport.myReportsHint') }}</p>

                                <div v-if="reports.length === 0" class="mt-4 rounded-xl border border-dashed border-theme px-4 py-8 text-center text-sm text-theme-muted">
                                    {{ t('bugReport.noReports') }}
                                </div>

                                <ul v-else class="mt-4 space-y-3">
                                    <li
                                        v-for="report in reports"
                                        :key="report.id"
                                        class="rounded-xl border border-theme bg-theme-surface px-4 py-3"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-medium text-theme-heading">{{ report.title }}</p>
                                                <p class="mt-1 line-clamp-2 text-sm text-theme-muted">{{ report.description }}</p>
                                            </div>
                                            <span
                                                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                                :class="statusClass(report.status)"
                                            >
                                                {{ report.status_label || report.status }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-xs text-theme-muted">{{ formatDate(report.created_at) }}</p>
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { LoaderCircle, X } from 'lucide-vue-next';
import { api } from '../api/client';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submitted']);

const { t } = useI18n();

const loading = ref(false);
const submitting = ref(false);
const loadError = ref('');
const submitError = ref('');
const successMessage = ref('');
const reports = ref([]);
const form = ref({
    title: '',
    description: '',
});

const pageUrl = computed(() => (typeof window !== 'undefined' ? window.location.href : ''));

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            loadReports();
        } else {
            resetForm();
        }
    },
);

function resetForm() {
    form.value = { title: '', description: '' };
    submitError.value = '';
    successMessage.value = '';
    loadError.value = '';
}

function close() {
    emit('close');
}

function statusClass(status) {
    const classes = {
        todo: 'bg-slate-100 text-slate-700',
        in_progress: 'bg-blue-100 text-blue-800',
        pending: 'bg-amber-100 text-amber-800',
        fixed: 'bg-emerald-100 text-emerald-800',
    };

    return classes[status] || classes.todo;
}

function formatDate(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

async function loadReports() {
    loading.value = true;
    loadError.value = '';

    try {
        const payload = await api('/api/bug-reports');
        reports.value = Array.isArray(payload.data) ? payload.data : [];
    } catch (error) {
        loadError.value = error.message || t('bugReport.loadError');
        reports.value = [];
    } finally {
        loading.value = false;
    }
}

async function submit() {
    if (submitting.value) {
        return;
    }

    submitting.value = true;
    submitError.value = '';
    successMessage.value = '';

    try {
        const payload = await api('/api/bug-reports', {
            method: 'POST',
            body: {
                title: form.value.title.trim(),
                description: form.value.description.trim(),
                page_url: pageUrl.value,
            },
        });

        if (payload.data) {
            reports.value = [payload.data, ...reports.value.filter((item) => item.id !== payload.data.id)];
        } else {
            await loadReports();
        }

        form.value = { title: '', description: '' };
        successMessage.value = payload.message || t('bugReport.submitted');
        emit('submitted');
    } catch (error) {
        submitError.value = error.message || t('bugReport.submitError');
    } finally {
        submitting.value = false;
    }
}
</script>
