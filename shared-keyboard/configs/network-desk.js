import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'network-desk';

export const shortcutActions = mergeShortcutActions(baseShortcutActions, {
    navDashboard: { id: 'navDashboard', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>d' },
    navMap: { id: 'navMap', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>m' },
    navPoints: { id: 'navPoints', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>p' },
    navAccess: { id: 'navAccess', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>a' },
});

export function createHandlers({ router, canManageAccess, openActivityModal, openShortcutsModal, toggleChrome }) {
    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        toggleChrome: () => toggleChrome?.(),
        navDashboard: () => router.push({ name: 'dashboard.index' }),
        navMap: () => router.push({ name: 'map.index' }),
        navPoints: () => router.push({ name: 'points.index' }),
        navAccess: () => {
            if (canManageAccess?.value) {
                router.push({ name: 'access.index' });
            }
        },
        activityLog: () => openActivityModal?.(),
    };
}
