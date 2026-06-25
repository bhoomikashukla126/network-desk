import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'complaint-desk';

export const shortcutActions = mergeShortcutActions(baseShortcutActions, {
    navComplaints: { id: 'navComplaints', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>c' },
    newComplaint: { id: 'newComplaint', category: SHORTCUT_CATEGORIES.actions, defaultCombo: 'n>c' },
    navAccess: { id: 'navAccess', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>m' },
});

export function createHandlers({ router, canManageAccess, openActivityModal, openShortcutsModal, toggleChrome, canCreate }) {
    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        toggleChrome: () => toggleChrome?.(),
        navComplaints: () => router.push({ name: 'complaints.index' }),
        newComplaint: () => {
            if (canCreate?.value) {
                router.push({ name: 'complaints.create' });
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
