import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'accounts-billing';

export const shortcutActions = mergeShortcutActions(baseShortcutActions, {
    navDashboard: {
        id: 'navDashboard',
        category: SHORTCUT_CATEGORIES.navigation,
        defaultCombo: 'g>d',
    },
    navAccounts: {
        id: 'navAccounts',
        category: SHORTCUT_CATEGORIES.navigation,
        defaultCombo: 'g>a',
    },
    navBills: {
        id: 'navBills',
        category: SHORTCUT_CATEGORIES.navigation,
        defaultCombo: 'g>b',
    },
    navReceipts: {
        id: 'navReceipts',
        category: SHORTCUT_CATEGORIES.navigation,
        defaultCombo: 'g>r',
    },
    navAccess: {
        id: 'navAccess',
        category: SHORTCUT_CATEGORIES.navigation,
        defaultCombo: 'g>m',
    },
    newAccount: {
        id: 'newAccount',
        category: SHORTCUT_CATEGORIES.actions,
        defaultCombo: 'n>a',
    },
    toggleAccountsView: {
        id: 'toggleAccountsView',
        category: SHORTCUT_CATEGORIES.actions,
        defaultCombo: 'alt+shift+v',
    },
});

export function createHandlers({ router, route, canManageAccess, canCreateAccount, openActivityModal, openShortcutsModal, toggleChrome }) {
    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        toggleChrome: () => toggleChrome?.(),
        navDashboard: () => router.push({ name: 'dashboard' }),
        navAccounts: () => router.push({ name: 'accounts.index' }),
        navBills: () => router.push({ name: 'bills.index' }),
        navReceipts: () => router.push({ name: 'receipts.index' }),
        navAccess: () => {
            if (canManageAccess?.value) {
                router.push({ name: 'access.index' });
            }
        },
        newAccount: () => {
            if (canCreateAccount?.value) {
                router.push({ name: 'accounts.create' });
            }
        },
        activityLog: () => openActivityModal?.(),
        toggleAccountsView: () => {
            if (route.name === 'accounts.index') {
                window.dispatchEvent(new CustomEvent(`${APP_ID}:toggle-accounts-view`));
            }
        },
    };
}
