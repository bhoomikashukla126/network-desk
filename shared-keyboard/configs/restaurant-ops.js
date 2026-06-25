import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'restaurant-ops';

export const shortcutActions = mergeShortcutActions(baseShortcutActions, {
    navDashboard: { id: 'navDashboard', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>d' },
    navMenu: { id: 'navMenu', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>m' },
    navKot: { id: 'navKot', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>k' },
    navInventory: { id: 'navInventory', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>i' },
    navTables: { id: 'navTables', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>t' },
    navAccess: { id: 'navAccess', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>a' },
});

export function createHandlers({ router, canManageAccess, openActivityModal, openShortcutsModal, toggleChrome }) {
    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        toggleChrome: () => toggleChrome?.(),
        navDashboard: () => router.push({ name: 'dashboard' }),
        navMenu: () => router.push({ name: 'menu.index' }),
        navKot: () => router.push({ name: 'kot.index' }),
        navInventory: () => router.push({ name: 'inventory.index' }),
        navTables: () => router.push({ name: 'tables.index' }),
        navAccess: () => {
            if (canManageAccess?.value) {
                router.push({ name: 'access.index' });
            }
        },
        activityLog: () => openActivityModal?.(),
    };
}
