import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'menu-card';

export const shortcutActions = mergeShortcutActions(baseShortcutActions, {
    navMenuCards: { id: 'navMenuCards', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>m' },
    newMenuCard: { id: 'newMenuCard', category: SHORTCUT_CATEGORIES.actions, defaultCombo: 'n>m' },
    navAccess: { id: 'navAccess', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>a' },
});

export function createHandlers({ router, canManageAccess, openActivityModal, openShortcutsModal, toggleChrome, canCreate }) {
    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        toggleChrome: () => toggleChrome?.(),
        navMenuCards: () => router.push({ name: 'menu-cards.index' }),
        newMenuCard: () => {
            if (canCreate?.value) {
                router.push({ name: 'menu-cards.create' });
            }
        },
        navAccess: () => {
            if (canManageAccess?.value) {
                router.push({ name: 'access.index' });
            }
        },
        activityLog: () => openActivityModal?.(),
    };
}
