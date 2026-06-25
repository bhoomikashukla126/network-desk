export const SHORTCUT_CATEGORIES = {
    general: 'general',
    navigation: 'navigation',
    actions: 'actions',
};

const MODIFIER_KEYS = new Set(['control', 'alt', 'shift', 'meta', 'mod']);

export function preferencesStorageKey(appId, userId, workspaceId) {
    return `${appId}:preferences:${userId ?? 'anonymous'}:${workspaceId ?? 'default'}`;
}

export function normalizeKey(key) {
    const value = String(key ?? '').trim().toLowerCase();

    if (value === ' ') {
        return 'space';
    }

    if (value === 'escape' || value === 'esc') {
        return 'escape';
    }

    if (value === 'arrowup') {
        return 'up';
    }

    if (value === 'arrowdown') {
        return 'down';
    }

    if (value === 'arrowleft') {
        return 'left';
    }

    if (value === 'arrowright') {
        return 'right';
    }

    if (value.length === 1) {
        return value;
    }

    return value;
}

export function normalizeCombo(combo) {
    const raw = String(combo ?? '').trim().toLowerCase();

    if (!raw) {
        return '';
    }

    if (raw.includes('>')) {
        return raw
            .split('>')
            .map((part) => normalizeCombo(part))
            .filter(Boolean)
            .join('>');
    }

    const parts = raw.split('+').map((part) => {
        if (part === 'ctrl' || part === 'cmd' || part === 'command' || part === 'meta') {
            return 'mod';
        }

        return normalizeKey(part);
    }).filter(Boolean);

    const modifiers = parts.filter((part) => MODIFIER_KEYS.has(part)).sort();
    const keys = parts.filter((part) => !MODIFIER_KEYS.has(part));

    if (!keys.length) {
        return modifiers.join('+');
    }

    return [...modifiers, ...keys].join('+');
}

export function comboFromKeyboardEvent(event) {
    const parts = [];

    if (event.ctrlKey || event.metaKey) {
        parts.push('mod');
    }

    if (event.altKey) {
        parts.push('alt');
    }

    if (event.shiftKey) {
        parts.push('shift');
    }

    const key = normalizeKey(event.key);

    if (!MODIFIER_KEYS.has(key)) {
        parts.push(key);
    }

    return normalizeCombo(parts.join('+'));
}

export function formatComboLabel(combo, translate = null) {
    const normalized = normalizeCombo(combo);

    if (!normalized) {
        return translate?.('shortcuts.unassigned') ?? 'Unassigned';
    }

    if (normalized.includes('>')) {
        return normalized
            .split('>')
            .map((part) => formatComboLabel(part, translate))
            .join(' then ');
    }

    return normalized
        .split('+')
        .map((part) => {
            if (part === 'mod') {
                return navigator.platform?.includes('Mac') ? '⌘' : 'Ctrl';
            }

            if (part === 'alt') {
                return 'Alt';
            }

            if (part === 'shift') {
                return 'Shift';
            }

            if (part === 'escape') {
                return 'Esc';
            }

            if (part === 'space') {
                return 'Space';
            }

            if (part.length === 1) {
                return part.toUpperCase();
            }

            return part.charAt(0).toUpperCase() + part.slice(1);
        })
        .join(' + ');
}

export function isTypingTarget(target) {
    if (!target) {
        return false;
    }

    const tag = target.tagName?.toLowerCase();

    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
        const inputType = target.type?.toLowerCase();

        if (tag === 'input' && ['button', 'submit', 'reset', 'checkbox', 'radio', 'file'].includes(inputType)) {
            return false;
        }

        return true;
    }

    return Boolean(target.isContentEditable);
}

function comboMatchesEvent(combo, event) {
    const normalized = normalizeCombo(combo);

    if (!normalized || normalized.includes('>')) {
        return false;
    }

    const parts = normalized.split('+');
    const needsMod = parts.includes('mod');
    const needsAlt = parts.includes('alt');
    const needsShift = parts.includes('shift');
    const keyPart = parts.find((part) => !MODIFIER_KEYS.has(part));

    if (!keyPart) {
        return false;
    }

    const eventKey = normalizeKey(event.key);

    if (eventKey !== keyPart) {
        return false;
    }

    if (needsMod !== (event.ctrlKey || event.metaKey)) {
        return false;
    }

    if (needsAlt !== event.altKey) {
        return false;
    }

    if (needsShift !== event.shiftKey) {
        return false;
    }

    return true;
}

export function buildShortcutMap(actions, overrides = {}) {
    const map = {};

    Object.values(actions).forEach((action) => {
        const combo = overrides[action.id] ?? action.defaultCombo;
        map[action.id] = normalizeCombo(combo) || normalizeCombo(action.defaultCombo);
    });

    return map;
}

export function createShortcutMatcher(actions, shortcutMap) {
    let chordBuffer = '';
    let chordTimer = null;

    function resetChord() {
        chordBuffer = '';

        if (chordTimer) {
            clearTimeout(chordTimer);
            chordTimer = null;
        }
    }

    function scheduleChordReset() {
        if (chordTimer) {
            clearTimeout(chordTimer);
        }

        chordTimer = setTimeout(resetChord, 1200);
    }

    function match(event, { allowInInput = false } = {}) {
        if (!allowInInput && isTypingTarget(event.target)) {
            const action = Object.values(actions).find((item) => {
                const combo = shortcutMap[item.id];

                return item.allowInInput && combo && comboMatchesEvent(combo, event);
            });

            if (!action) {
                return null;
            }

            return action.id;
        }

        for (const action of Object.values(actions)) {
            const combo = shortcutMap[action.id];

            if (!combo || combo.includes('>')) {
                continue;
            }

            if (comboMatchesEvent(combo, event)) {
                resetChord();

                return action.id;
            }
        }

        const chordActions = Object.values(actions).filter((action) => {
            const combo = shortcutMap[action.id];

            return combo?.includes('>');
        });

        if (!chordActions.length) {
            return null;
        }

        const key = normalizeKey(event.key);

        if (MODIFIER_KEYS.has(key) || event.ctrlKey || event.metaKey || event.altKey) {
            return null;
        }

        if (!chordBuffer) {
            chordBuffer = key;
            scheduleChordReset();

            return null;
        }

        const attempt = normalizeCombo(`${chordBuffer}>${key}`);
        resetChord();

        const matched = chordActions.find((action) => normalizeCombo(shortcutMap[action.id]) === attempt);

        return matched?.id ?? null;
    }

    return {
        match,
        resetChord,
        destroy() {
            resetChord();
        },
    };
}

export const baseShortcutActions = {
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
    toggleChrome: {
        id: 'toggleChrome',
        category: SHORTCUT_CATEGORIES.general,
        defaultCombo: 'alt+shift+f',
    },
    activityLog: {
        id: 'activityLog',
        category: SHORTCUT_CATEGORIES.actions,
        defaultCombo: 'alt+shift+l',
    },
};

export function mergeShortcutActions(...groups) {
    return groups.reduce((merged, group) => ({ ...merged, ...group }), {});
}
