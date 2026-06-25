import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { buildShortcutMap, createShortcutMatcher } from '../js/core.js';
import { dispatchAppEvent, onAppEvent } from '../js/events.js';

export function createUseKeyboardShortcuts({
    appId,
    actions,
    events,
    session,
    preferencesApi,
    handlers = {},
    isOverlayOpen = null,
}) {
    const shortcutOverrides = ref({});
    const loading = ref(false);
    const saving = ref(false);
    let matcher = null;

    const shortcutMap = computed(() => buildShortcutMap(actions, shortcutOverrides.value));

    async function loadPreferences() {
        if (!session?.value) {
            return;
        }

        loading.value = true;

        try {
            shortcutOverrides.value = await preferencesApi.fetchKeyboardShortcuts(session.value);
            dispatchAppEvent(events.shortcutsChanged, { shortcuts: shortcutMap.value });
        } finally {
            loading.value = false;
        }
    }

    async function saveShortcutOverrides(overrides) {
        if (!session?.value) {
            return;
        }

        saving.value = true;

        try {
            shortcutOverrides.value = await preferencesApi.saveKeyboardShortcuts(session.value, overrides);
            dispatchAppEvent(events.shortcutsChanged, { shortcuts: shortcutMap.value });
        } finally {
            saving.value = false;
        }
    }

    function resetShortcut(actionId) {
        const next = { ...shortcutOverrides.value };
        delete next[actionId];
        saveShortcutOverrides(next);
    }

    function setShortcut(actionId, combo) {
        const next = { ...shortcutOverrides.value };

        if (!combo) {
            delete next[actionId];
        } else {
            next[actionId] = combo;
        }

        saveShortcutOverrides(next);
    }

    function resetAllShortcuts() {
        saveShortcutOverrides({});
    }

    function runAction(actionId) {
        handlers[actionId]?.();
    }

    function onKeyDown(event) {
        if (!matcher) {
            return;
        }

        if (isOverlayOpen?.value && event.key !== 'Escape') {
            return;
        }

        const actionId = matcher.match(event);

        if (!actionId) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        runAction(actionId);
    }

    function mount() {
        matcher?.destroy();
        matcher = createShortcutMatcher(actions, shortcutMap.value);
        window.addEventListener('keydown', onKeyDown, true);
    }

    function unmount() {
        window.removeEventListener('keydown', onKeyDown, true);
        matcher?.destroy();
        matcher = null;
    }

    watch(shortcutMap, (map) => {
        matcher?.destroy();
        matcher = createShortcutMatcher(actions, map);
        dispatchAppEvent(events.shortcutsChanged, { shortcuts: map });
    });

    watch(session, (value) => {
        if (value) {
            loadPreferences();
        }
    }, { immediate: true });

    const stopExternalSync = onAppEvent(events.shortcutsChanged, () => {
        matcher?.destroy();
        matcher = createShortcutMatcher(actions, shortcutMap.value);
    });

    onBeforeUnmount(() => {
        unmount();
        stopExternalSync();
    });

    return {
        appId,
        actions,
        shortcutMap,
        shortcutOverrides,
        loading,
        saving,
        loadPreferences,
        setShortcut,
        resetShortcut,
        resetAllShortcuts,
        mount,
        unmount,
        runAction,
    };
}
