import { useRoute, useRouter } from 'vue-router';
import { setupExtensionKeyboard } from './setupExtensionKeyboard.js';

export function createAppKeyboardShortcuts({ appId, actions, createHandlers, apiClient, endpoints }) {
    return function useAppKeyboardShortcuts(options = {}) {
        const router = useRouter();
        const route = useRoute();

        return setupExtensionKeyboard({
            appId,
            actions,
            apiClient,
            endpoints,
            ...options,
            createHandlers: (context) => createHandlers({
                router,
                route,
                ...context,
                ...options,
            }),
        });
    };
}
