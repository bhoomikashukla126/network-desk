import { createAppKeyboardShortcuts } from '@platform/keyboard/vue/createAppKeyboardShortcuts.js';
import { shortcutActions, createHandlers, APP_ID } from '@platform/keyboard/configs/network-desk.js';
import { api } from '../api/client';

export { shortcutActions, APP_ID };

export const useAppKeyboardShortcuts = createAppKeyboardShortcuts({
    appId: APP_ID,
    actions: shortcutActions,
    createHandlers,
    apiClient: api,
});
