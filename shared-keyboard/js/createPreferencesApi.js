import { preferencesStorageKey } from './core.js';

export function createPreferencesApi({ appId, apiClient, endpoints = {} }) {
    const showUrl = endpoints.show ?? '/api/user/preferences';
    const updateUrl = endpoints.update ?? '/api/user/preferences';

    async function fetchUserPreferences(session) {
        const userId = session?.user?.sub;
        const workspaceId = session?.workspace?.id;
        const cacheKey = preferencesStorageKey(appId, userId, workspaceId);

        try {
            const response = await apiClient(showUrl);
            const preferences = response?.preferences ?? response?.data?.preferences ?? {};

            if (userId && workspaceId) {
                window.localStorage.setItem(cacheKey, JSON.stringify(preferences));
            }

            return preferences;
        } catch {
            try {
                const cached = window.localStorage.getItem(cacheKey);

                return cached ? JSON.parse(cached) : {};
            } catch {
                return {};
            }
        }
    }

    async function saveUserPreferences(session, preferences) {
        const userId = session?.user?.sub;
        const workspaceId = session?.workspace?.id;
        const cacheKey = preferencesStorageKey(appId, userId, workspaceId);

        const response = await apiClient(updateUrl, {
            method: 'PUT',
            body: { preferences },
        });

        const saved = response?.preferences ?? response?.data?.preferences ?? preferences;

        if (userId && workspaceId) {
            window.localStorage.setItem(cacheKey, JSON.stringify(saved));
        }

        return saved;
    }

    async function fetchKeyboardShortcuts(session) {
        const preferences = await fetchUserPreferences(session);
        const keyboardShortcuts = preferences?.keyboard_shortcuts ?? {};

        if (keyboardShortcuts[appId] && typeof keyboardShortcuts[appId] === 'object') {
            return keyboardShortcuts[appId];
        }

        const sample = Object.values(keyboardShortcuts)[0];

        if (typeof sample === 'string') {
            return keyboardShortcuts;
        }

        return {};
    }

    async function saveKeyboardShortcuts(session, shortcuts) {
        const current = await fetchUserPreferences(session);
        const keyboardShortcuts = {
            ...(current.keyboard_shortcuts ?? {}),
            [appId]: shortcuts,
        };

        if (!Object.keys(shortcuts).length) {
            delete keyboardShortcuts[appId];
        }

        const saved = await saveUserPreferences(session, {
            ...current,
            keyboard_shortcuts: keyboardShortcuts,
        });

        return saved?.keyboard_shortcuts?.[appId] ?? shortcuts;
    }

    return {
        fetchUserPreferences,
        saveUserPreferences,
        fetchKeyboardShortcuts,
        saveKeyboardShortcuts,
    };
}
