export function createKeyboardEvents(appId) {
    const prefix = String(appId || 'platform').trim();

    return {
        focusSearch: `${prefix}:focus-search`,
        shortcutsChanged: `${prefix}:shortcuts-changed`,
        closeOverlay: `${prefix}:close-overlay`,
        closeProfileMenu: `${prefix}:close-profile-menu`,
    };
}

export function dispatchAppEvent(name, detail = null) {
    window.dispatchEvent(new CustomEvent(name, detail == null ? undefined : { detail }));
}

export function onAppEvent(name, listener) {
    window.addEventListener(name, listener);

    return () => window.removeEventListener(name, listener);
}
