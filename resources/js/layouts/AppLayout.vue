<template>
    <div
        class="app-shell flex flex-col"
        :class="(fillViewport || !chromeVisible) ? 'h-[100dvh] overflow-hidden' : 'min-h-screen'"
    >
        <a
            href="#app-main-content"
            class="skip-link"
        >
            {{ $t('shortcuts.skipToContent') }}
        </a>

        <header v-show="chromeVisible" class="app-header sticky top-0 border-b backdrop-blur">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="bg-theme-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm">
                        <Network class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-lg font-semibold text-theme-heading">{{ $t('app.name') }}</p>
                        <p class="hidden truncate text-sm text-theme-muted sm:block">{{ $t('app.tagline') }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <AppChromeToggle
                        :chrome-visible="chromeVisible"
                        :label="chromeToggleLabel"
                        @toggle="toggleChrome"
                    />

                    <UserProfileMenu
                        v-if="session"
                        :session="session"
                        @open-activity="showActivityModal = true"
                        @open-shortcuts="showShortcutsModal = true"
                        @quotas-refreshed="onQuotasRefreshed"
                    />
                </div>
            </div>

            <nav class="app-nav border-t" aria-label="Main navigation">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-1 px-2 py-2 sm:justify-start sm:gap-2 sm:px-6 sm:py-2.5 lg:px-8">
                    <RouterLink
                        :to="{ name: 'dashboard.index' }"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-2 py-2.5 text-sm font-medium transition sm:flex-none sm:px-3 sm:py-2"
                        :class="navClass(isActive('dashboard.index'))"
                    >
                        <LayoutDashboard class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:inline">{{ $t('nav.dashboard') }}</span>
                    </RouterLink>

                    <RouterLink
                        :to="{ name: 'map.index' }"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-2 py-2.5 text-sm font-medium transition sm:flex-none sm:px-3 sm:py-2"
                        :class="navClass(isActive('map.index'))"
                    >
                        <Map class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:inline">{{ $t('nav.map') }}</span>
                    </RouterLink>

                    <RouterLink
                        :to="{ name: 'points.index' }"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-2 py-2.5 text-sm font-medium transition sm:flex-none sm:px-3 sm:py-2"
                        :class="navClass(isActive('points.index'))"
                    >
                        <ListTree class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:inline">{{ $t('nav.points') }}</span>
                    </RouterLink>

                    <RouterLink
                        v-if="canManageAccess"
                        :to="{ name: 'access.index' }"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-2 py-2.5 text-sm font-medium transition sm:flex-none sm:px-3 sm:py-2"
                        :class="navClass(isActive('access.index'))"
                    >
                        <Shield class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:inline">{{ $t('nav.access') }}</span>
                    </RouterLink>
                </div>
            </nav>
        </header>

        <AppChromeToggle
            v-if="!chromeVisible"
            floating
            :chrome-visible="chromeVisible"
            :label="chromeToggleLabel"
            @toggle="toggleChrome"
        />

        <main
            id="app-main-content"
            tabindex="-1"
            class="flex-1 min-h-0 outline-none"
            :class="[
                fillViewport ? 'flex flex-col overflow-hidden' : '',
                chromeVisible
                    ? (fillViewport
                        ? 'mx-auto w-full max-w-7xl px-4 py-4 sm:px-6 lg:px-4'
                        : 'mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8')
                    : 'app-main-fullscreen w-full',
            ]"
        >
            <div v-if="loading" class="flex items-center justify-center py-24 text-theme-muted">
                <LoaderCircle class="h-6 w-6 animate-spin" />
                <span class="ml-2">{{ $t('common.loading') }}</span>
            </div>
            <div v-else :class="fillViewport ? 'flex min-h-0 flex-1 flex-col' : ''">
                <RouterView v-slot="{ Component }">
                    <component
                        :is="Component"
                        :session="session"
                        :class="fillViewport ? 'flex min-h-0 flex-1 flex-col' : ''"
                    />
                </RouterView>
            </div>
        </main>

        <footer v-show="chromeVisible" class="app-footer mt-auto border-t">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-theme-muted sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ year }} {{ $t('app.name') }}. {{ $t('app.footer') }}</p>
                <p v-if="session?.workspace?.name">{{ $t('app.activeWorkspace') }} <span class="font-medium text-theme-body">{{ session.workspace.name }}</span></p>
            </div>
        </footer>

        <ActivityLogModal
            :open="showActivityModal"
            :session="session"
            @close="showActivityModal = false"
        />

        <ConfirmDialog />

        <KeyboardShortcutsModal
            :open="showShortcutsModal"
            :actions="shortcutActions"
            :overrides="shortcutOverrides"
            :shortcut-map="shortcutMap"
            @close="showShortcutsModal = false"
            @save="onShortcutSave"
            @reset="resetShortcut"
            @reset-all="resetAllShortcuts"
        />
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { LayoutDashboard, ListTree, LoaderCircle, Map, Network, Shield } from 'lucide-vue-next';
import { api } from '../api/client';
import { useAppChrome } from '../composables/useAppChrome';
import { useAppKeyboardShortcuts, shortcutActions } from '../keyboard';
import { setLocaleFromWorkspace } from '../i18n';
import { applyWorkspaceAppearance, watchSystemColorMode } from '../utils/applyWorkspaceAppearance';
import AppChromeToggle from '../components/AppChromeToggle.vue';
import UserProfileMenu from '../components/UserProfileMenu.vue';
import ActivityLogModal from '../components/ActivityLogModal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import KeyboardShortcutsModal from '@platform/keyboard/vue/KeyboardShortcutsModal.vue';

const CHROME_STORAGE_KEY = 'network-desk-app-chrome-visible';

const route = useRoute();
const session = ref(null);
const loading = ref(true);
const showActivityModal = ref(false);
const showShortcutsModal = ref(false);
const year = new Date().getFullYear();
const { chromeVisible, chromeToggleLabel, toggleChrome } = useAppChrome(CHROME_STORAGE_KEY);

const canManageAccess = computed(() => session.value?.can_manage_roles || session.value?.can_manage_members);
const fillViewport = computed(() => route.matched.some((record) => record.meta?.fillViewport));

const isShortcutOverlayOpen = computed(() => showShortcutsModal.value || showActivityModal.value);

const {
    shortcutMap,
    shortcutOverrides,
    setShortcut,
    resetShortcut,
    resetAllShortcuts,
    mount: mountKeyboardShortcuts,
    unmount: unmountKeyboardShortcuts,
} = useAppKeyboardShortcuts({
    session,
    chromeVisible,
    toggleChrome,
    openActivityModal: () => {
        showActivityModal.value = true;
    },
    openShortcutsModal: () => {
        showShortcutsModal.value = true;
    },
    canManageAccess,
    isOverlayOpen: isShortcutOverlayOpen,
});

function isActive(name) {
    return route.name === name;
}

function navClass(active) {
    return active ? 'app-nav-link-active' : 'app-nav-link';
}

function onQuotasRefreshed(quotas) {
    if (session.value) {
        session.value = { ...session.value, quotas };
    }
}

function onShortcutSave(actionId, combo) {
    setShortcut(actionId, combo);
}

function closeOverlays() {
    showShortcutsModal.value = false;
    showActivityModal.value = false;
    window.dispatchEvent(new CustomEvent('network-desk:close-profile-menu'));
}

let unwatchColorMode = () => {};
let stopCloseOverlayListener = () => {};

onMounted(async () => {
    try {
        session.value = await api('/api/session');
        setLocaleFromWorkspace(session.value?.workspace?.language ?? session.value?.locale);
        applyWorkspaceAppearance(session.value?.workspace);
        unwatchColorMode = watchSystemColorMode(session.value?.workspace);
        mountKeyboardShortcuts();
        stopCloseOverlayListener = () => {
            window.removeEventListener('network-desk:close-overlay', closeOverlays);
        };
        window.addEventListener('network-desk:close-overlay', closeOverlays);
    } finally {
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    unwatchColorMode();
    unmountKeyboardShortcuts();
    stopCloseOverlayListener();
    window.removeEventListener('network-desk:close-overlay', closeOverlays);
});
</script>
