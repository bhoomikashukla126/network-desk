<template>
    <div ref="menuRoot" class="relative z-50">
        <button
            type="button"
            class="app-card flex items-center gap-2 rounded-xl border px-2 py-1.5 shadow-sm transition hover-surface sm:gap-2.5 sm:px-3 sm:py-2"
            :aria-expanded="open"
            aria-haspopup="true"
            @click="toggle"
        >
            <div class="bg-theme-avatar flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold sm:h-9 sm:w-9">
                {{ initials }}
            </div>
            <div class="hidden min-w-0 text-left sm:block">
                <p class="truncate text-sm font-semibold text-theme-heading">{{ displayName }}</p>
                <p v-if="roleName" class="truncate text-xs text-theme-muted">{{ roleName }}</p>
            </div>
            <ChevronDown class="h-4 w-4 text-theme-muted transition" :class="{ 'rotate-180': open }" />
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="open"
                class="app-card absolute right-0 z-50 mt-2 w-72 origin-top-right rounded-2xl border p-2 shadow-lg"
            >
                <div class="app-surface-muted rounded-xl px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="bg-theme-avatar flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold">
                            {{ initials }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-theme-heading">{{ displayName }}</p>
                            <p v-if="email" class="truncate text-sm text-theme-muted">{{ email }}</p>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-theme-muted">{{ t('profile.workspace') }}</dt>
                            <dd class="truncate text-right font-medium text-theme-heading">{{ workspaceName }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-theme-muted">{{ t('profile.role') }}</dt>
                            <dd class="font-medium text-theme-heading">{{ roleName }}</dd>
                        </div>
                        <template v-if="showOwnerQuotas">
                            <div>
                                <div class="flex items-center justify-between gap-2 border-t border-theme/40 pt-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-theme-muted">{{ t('profile.usage') }}</span>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg p-1.5 text-theme-muted transition hover:bg-theme-surface hover:text-theme-heading disabled:opacity-50"
                                        :disabled="refreshingQuotas"
                                        :title="t('profile.refreshUsage')"
                                        :aria-label="t('profile.refreshUsage')"
                                        @click.stop="refreshUsageData"
                                    >
                                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshingQuotas }" />
                                    </button>
                                </div>
                                <p v-if="quotaRefreshError" class="mt-1 text-xs text-rose-600">{{ quotaRefreshError }}</p>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-theme-muted">{{ t('profile.apiRequestsLeft') }}</dt>
                                <dd class="font-medium text-theme-heading">{{ formatNumber(displayQuotas.remaining_activities) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-theme-muted">{{ t('profile.storageUsed') }}</dt>
                                <dd class="font-medium text-theme-heading">{{ formatStorageBytes(displayQuotas.usage_storage_bytes ?? 0) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-theme-muted">{{ t('profile.storageLeft') }}</dt>
                                <dd class="font-medium text-theme-heading">{{ formatStorageBytes(displayQuotas.remaining_storage_bytes ?? 0) }}</dd>
                            </div>
                        </template>
                    </dl>
                </div>

                <div class="mt-1 space-y-1 py-1">
                    <button
                        type="button"
                        class="hover-surface flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-theme-body transition"
                        @click="openActivity"
                    >
                        <ScrollText class="h-4 w-4 text-theme-muted" />
                        {{ activityLabel }}
                    </button>

                    <a
                        v-if="workspaceHomeUrl"
                        :href="workspaceHomeUrl"
                        class="hover-surface flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-theme-body transition"
                        @click="close"
                    >
                        <Home class="h-4 w-4 text-theme-muted" />
                        {{ t('profile.workspaceHome') }}
                    </a>

                    <a
                        v-if="centralUrl"
                        :href="`${centralUrl}/workspaces`"
                        class="hover-surface flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-theme-body transition"
                        @click="close"
                    >
                        <ArrowLeft class="h-4 w-4 text-theme-muted" />
                        {{ t('profile.workspaces') }}
                    </a>

                    <a
                        href="/docs"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hover-surface flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-theme-body transition"
                        @click="close"
                    >
                        <BookOpen class="h-4 w-4 text-theme-muted" />
                        {{ t('profile.help') }}
                    </a>

                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button
                            type="submit"
                            class="btn-danger-outline flex w-full items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition"
                        >
                            <LogOut class="h-4 w-4" />
                            {{ t('profile.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowLeft, BookOpen, ChevronDown, Home, LogOut, RefreshCw, ScrollText } from 'lucide-vue-next';
import { api } from '../api/client';
import { formatStorageBytes } from '../utils/formatStorage';

const props = defineProps({
    session: { type: Object, required: true },
});

const emit = defineEmits(['open-activity', 'quotas-refreshed']);

const { t } = useI18n();

const open = ref(false);
const menuRoot = ref(null);
const displayQuotas = ref({});
const refreshingQuotas = ref(false);
const quotaRefreshError = ref('');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const displayName = computed(() => props.session.user?.name || props.session.user?.email || t('profile.defaultUser'));
const email = computed(() => props.session.user?.email || null);
const roleName = computed(() => {
    const name = props.session.role?.name;

    if (!name) {
        return t('profile.defaultGuest');
    }

    if (name === 'Guest') {
        return t('profile.defaultGuest');
    }

    return name;
});
const workspaceName = computed(() => props.session.workspace?.name || t('profile.defaultWorkspace'));
const centralUrl = computed(() => props.session.central_url || null);
const workspaceHomeUrl = computed(() => {
    const id = props.session.workspace?.id;

    if (!centralUrl.value || !id) {
        return null;
    }

    return `${centralUrl.value}/workspaces/${id}`;
});
const showOwnerQuotas = computed(() => Boolean(props.session.workspace?.is_owner && Object.keys(displayQuotas.value).length > 0));
const activityLabel = computed(() => (
    props.session.can_view_activity ? t('profile.workspaceActivity') : t('profile.myActivity')
));

function formatNumber(value) {
    return new Intl.NumberFormat().format(Number(value ?? 0));
}

const initials = computed(() => {
    const name = displayName.value;
    const parts = name.split(/[\s@]+/).filter(Boolean);

    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }

    return name.slice(0, 2).toUpperCase();
});

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function openActivity() {
    close();
    emit('open-activity');
}

async function refreshUsageData() {
    if (refreshingQuotas.value) {
        return;
    }

    refreshingQuotas.value = true;
    quotaRefreshError.value = '';

    try {
        const payload = await api('/api/session/quotas/refresh', { method: 'POST' });
        displayQuotas.value = payload.quotas ?? displayQuotas.value;
        emit('quotas-refreshed', displayQuotas.value);
    } catch (error) {
        quotaRefreshError.value = error.message || t('profile.refreshUsageFailed');
    } finally {
        refreshingQuotas.value = false;
    }
}

function onDocumentClick(event) {
    if (!menuRoot.value?.contains(event.target)) {
        close();
    }
}

function onEscape(event) {
    if (event.key === 'Escape') {
        close();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onEscape);
});

watch(
    () => props.session?.quotas,
    (quotas) => {
        displayQuotas.value = quotas ? { ...quotas } : {};
    },
    { immediate: true, deep: true },
);

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onEscape);
});
</script>
