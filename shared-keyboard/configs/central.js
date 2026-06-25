import { SHORTCUT_CATEGORIES, baseShortcutActions, mergeShortcutActions } from '../js/core.js';

export const APP_ID = 'central';

export const shortcutActions = mergeShortcutActions({
    showHelp: {
        id: 'showHelp',
        category: SHORTCUT_CATEGORIES.general,
        defaultCombo: 'shift+/',
    },
    closeOverlay: {
        id: 'closeOverlay',
        category: SHORTCUT_CATEGORIES.general,
        defaultCombo: 'escape',
        allowInInput: true,
    },
    focusSearch: {
        id: 'focusSearch',
        category: SHORTCUT_CATEGORIES.general,
        defaultCombo: '/',
    },
}, {
    navWorkspaces: { id: 'navWorkspaces', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>w' },
    navWorkspaceApps: { id: 'navWorkspaceApps', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>a' },
    navWorkspaceSettings: { id: 'navWorkspaceSettings', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>s' },
    navWorkspaceMembers: { id: 'navWorkspaceMembers', category: SHORTCUT_CATEGORIES.navigation, defaultCombo: 'g>m' },
    navAccount: { id: 'navAccount', category: SHORTCUT_CATEGORIES.actions, defaultCombo: 'g>u' },
});

export function createCentralHandlers(context) {
    const {
        workspaceId = null,
        urls = {},
        openShortcutsModal,
    } = context;

    const go = (url) => {
        if (url) {
            window.location.assign(url);
        }
    };

    return {
        showHelp: () => openShortcutsModal?.(),
        closeOverlay: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:close-overlay`)),
        focusSearch: () => window.dispatchEvent(new CustomEvent(`${APP_ID}:focus-search`)),
        navWorkspaces: () => go(urls.workspaces),
        navWorkspaceApps: () => go(workspaceId ? urls.workspaceShow : urls.workspaces),
        navWorkspaceSettings: () => go(workspaceId ? urls.workspaceSettings : null),
        navWorkspaceMembers: () => go(workspaceId ? urls.workspaceMembers : null),
        navAccount: () => go(urls.account),
    };
}
