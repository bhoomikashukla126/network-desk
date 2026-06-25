import { createKeyboardEvents } from '../js/events.js';
import { createPreferencesApi } from '../js/createPreferencesApi.js';
import { createUseKeyboardShortcuts } from '../vue/createUseKeyboardShortcuts.js';

export function setupExtensionKeyboard({
    appId,
    actions,
    createHandlers,
    apiClient,
    endpoints,
    session,
    isOverlayOpen,
    ...handlerContext
}) {
    const events = createKeyboardEvents(appId);
    const preferencesApi = createPreferencesApi({ appId, apiClient, endpoints });

    const handlers = createHandlers({
        ...handlerContext,
        openShortcutsModal: handlerContext.openShortcutsModal,
    });

    return {
        events,
        preferencesApi,
        ...createUseKeyboardShortcuts({
            appId,
            actions,
            events,
            session,
            preferencesApi,
            handlers,
            isOverlayOpen,
        }),
    };
}
