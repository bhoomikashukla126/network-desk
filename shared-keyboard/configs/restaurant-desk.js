import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'restaurant-desk';

export const shortcutActions = mergeShortcutActions(baseShortcutActions, {
    navDashboard: { id: 'navDashboard', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>d' },
    navPos: { id: 'navPos', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>p' },
    navCustomers: { id: 'navCustomers', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>c' },
    navInventory: { id: 'navInventory', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>i' },
    navReports: { id: 'navReports', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>r' },
    navAccess: { id: 'navAccess', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>m' },
});

export function createHandlers({ router, canManageAccess, openActivityModal, openShortcutsModal, toggleChrome }) {
    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        toggleChrome: () => toggleChrome?.(),
        navDashboard: () => router.push({ name: 'dashboard' }),
        navPos: () => router.push({ name: 'pos' }),
        navCustomers: () => router.push({ name: 'customers.index' }),
        navInventory: () => router.push({ name: 'inventory.index' }),
        navReports: () => router.push({ name: 'reports' }),
        navAccess: () => {
            if (canManageAccess?.value) {
                router.push({ name: 'access.index' });
            }
        },
        activityLog: () => openActivityModal?.(),
    };
}
