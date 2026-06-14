function hexToRgb(hex) {
    const normalized = String(hex).replace('#', '');
    const r = parseInt(normalized.slice(0, 2), 16);
    const g = parseInt(normalized.slice(2, 4), 16);
    const b = parseInt(normalized.slice(4, 6), 16);
    return `${r}, ${g}, ${b}`;
}

function shouldUseDarkMode(colorMode) {
    if (colorMode === 'dark') {
        return true;
    }

    if (colorMode === 'system') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    return false;
}

export function applyWorkspaceAppearance(workspace) {
    const appearance = workspace?.appearance;
    if (!appearance?.colors) {
        return;
    }

    const root = document.documentElement;
    const colorMode = appearance.color_mode || 'light';

    Object.entries(appearance.colors).forEach(([key, value]) => {
        const cssVar = `--theme-${String(key).replace(/_/g, '-')}`;
        root.style.setProperty(cssVar, value);
        if (/^#[0-9A-Fa-f]{6}$/.test(String(value))) {
            root.style.setProperty(`${cssVar}-rgb`, hexToRgb(String(value)));
        }
    });

    root.classList.toggle('dark', shouldUseDarkMode(colorMode));
    root.dataset.colorMode = colorMode;
}

export function watchSystemColorMode(workspace, callback) {
    const appearance = workspace?.appearance;
    if ((appearance?.color_mode || 'light') !== 'system') {
        return () => {};
    }

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = () => {
        applyWorkspaceAppearance(workspace);
        if (typeof callback === 'function') {
            callback();
        }
    };

    media.addEventListener('change', handler);

    return () => media.removeEventListener('change', handler);
}
