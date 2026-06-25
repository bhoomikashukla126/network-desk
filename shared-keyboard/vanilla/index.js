import {
    buildShortcutMap,
    createShortcutMatcher,
    formatComboLabel,
    normalizeCombo,
    comboFromKeyboardEvent,
    SHORTCUT_CATEGORIES,
} from '../js/core.js';
import { createKeyboardEvents } from '../js/events.js';
import { createPreferencesApi } from '../js/createPreferencesApi.js';

export function createKeyboardManager({
    appId,
    actions,
    createHandlers,
    apiClient,
    endpoints = {},
    session = null,
    labels = {},
    isOverlayOpen = () => false,
}) {
    const events = createKeyboardEvents(appId);
    const preferencesApi = createPreferencesApi({ appId, apiClient, endpoints });
    const handlers = createHandlers({
        openShortcutsModal: () => manager.openModal(),
    });

    let overrides = {};
    let matcher = null;
    let modalOpen = false;

    const manager = {
        appId,
        events,
        actions,
        labels,
        shortcutMap: {},
        overrides: {},

        async loadPreferences() {
            if (!session) {
                return;
            }

            manager.overrides = await preferencesApi.fetchKeyboardShortcuts(session);
            manager.shortcutMap = buildShortcutMap(actions, manager.overrides);
            manager.rebuildMatcher();
        },

        rebuildMatcher() {
            matcher?.destroy();
            manager.shortcutMap = buildShortcutMap(actions, manager.overrides);
            matcher = createShortcutMatcher(actions, manager.shortcutMap);
        },

        async saveOverrides(next) {
            if (!session) {
                manager.overrides = next;
                manager.rebuildMatcher();

                return;
            }

            manager.overrides = await preferencesApi.saveKeyboardShortcuts(session, next);
            manager.rebuildMatcher();
        },

        async setShortcut(actionId, combo) {
            const next = { ...manager.overrides };

            if (!combo) {
                delete next[actionId];
            } else {
                next[actionId] = combo;
            }

            await manager.saveOverrides(next);
        },

        async resetShortcut(actionId) {
            const next = { ...manager.overrides };
            delete next[actionId];
            await manager.saveOverrides(next);
        },

        async resetAllShortcuts() {
            await manager.saveOverrides({});
        },

        onKeyDown(event) {
            if (!matcher) {
                return;
            }

            if (event.target?.closest?.('[data-keyboard-shortcuts-modal]')) {
                return;
            }

            if ((modalOpen || isOverlayOpen()) && event.key !== 'Escape') {
                return;
            }

            const actionId = matcher.match(event);

            if (!actionId) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            handlers[actionId]?.();
        },

        mount() {
            window.addEventListener('keydown', manager.onKeyDown, true);
            manager.loadPreferences();
        },

        unmount() {
            window.removeEventListener('keydown', manager.onKeyDown, true);
            matcher?.destroy();
            matcher = null;
        },

        openModal() {
            modalOpen = true;
            window.dispatchEvent(new CustomEvent(`${appId}:shortcuts-modal-open`));
        },

        closeModal() {
            modalOpen = false;
            window.dispatchEvent(new CustomEvent(`${appId}:shortcuts-modal-close`));
        },

        isModalOpen() {
            return modalOpen;
        },
    };

    return manager;
}

export function initKeyboardShortcutsModal(manager, rootId = 'keyboard-shortcuts-modal') {
    const root = document.getElementById(rootId);

    if (!root || !manager) {
        return () => {};
    }

    const t = (key, fallback = key) => manager.labels[key] ?? fallback;
    let recordingActionId = '';
    let chordFirstKey = '';
    let recordingPreview = '';

    const categories = Object.values(SHORTCUT_CATEGORIES);

    function actionsByCategory() {
        return categories.reduce((groups, category) => {
            groups[category] = Object.values(manager.actions).filter((action) => action.category === category);

            return groups;
        }, {});
    }

    function resolvedCombo(actionId) {
        return manager.shortcutMap[actionId] ?? manager.actions[actionId]?.defaultCombo ?? '';
    }

    function render() {
        const grouped = actionsByCategory();

        root.innerHTML = `
            <div data-shortcuts-backdrop data-keyboard-shortcuts-modal class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-4 hidden">
                <div class="flex h-[100dvh] w-full min-h-0 flex-col overflow-hidden border-theme bg-theme-card shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-3xl sm:rounded-2xl sm:border" role="dialog" aria-modal="true" data-keyboard-shortcuts-modal aria-label="${t('title')}">
                    <div class="flex items-start justify-between gap-4 border-b border-theme px-4 py-4 sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-theme-heading">${t('title')}</h2>
                            <p class="mt-1 text-sm text-theme-muted">${t('description')}</p>
                        </div>
                        <button type="button" data-shortcuts-close class="rounded-lg border border-theme p-2 text-theme-muted transition hover-surface" aria-label="${t('close')}">✕</button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                        <p class="mb-4 rounded-xl border border-theme bg-theme-muted/40 px-4 py-3 text-sm text-theme-body">${t('hint')}</p>
                        ${categories.map((category) => {
                            const rows = grouped[category] ?? [];

                            if (!rows.length) {
                                return '';
                            }

                            return `
                                <div class="mb-6 last:mb-0">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-theme-muted">${t(`categories.${category}`)}</h3>
                                    <div class="overflow-hidden rounded-xl border border-theme">
                                        <table class="min-w-full divide-y divide-theme text-sm">
                                            <thead class="app-table-head text-left">
                                                <tr>
                                                    <th class="px-4 py-3 font-medium">${t('columns.action')}</th>
                                                    <th class="px-4 py-3 font-medium">${t('columns.shortcut')}</th>
                                                    <th class="px-4 py-3 font-medium text-right">${t('columns.edit')}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-theme bg-theme-card">
                                                ${rows.map((action) => `
                                                    <tr class="app-table-row" data-action-id="${action.id}">
                                                        <td class="px-4 py-3 text-theme-body">${t(`actions.${action.id}`)}</td>
                                                        <td class="px-4 py-3">
                                                            <kbd class="app-kbd inline-flex min-w-[5rem] items-center justify-center rounded-md border border-theme px-2 py-1 text-xs font-semibold text-theme-heading" data-combo-display>${formatComboLabel(resolvedCombo(action.id), (k) => t(k))}</kbd>
                                                            <input type="text" readonly class="app-input hidden w-full max-w-xs rounded-lg border px-3 py-2 text-sm" data-recording-input placeholder="${t('pressKeys')}">
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <div class="flex justify-end gap-2">
                                                                <button type="button" class="btn-secondary rounded-lg border px-2.5 py-1.5 text-xs font-medium" data-change>${t('change')}</button>
                                                                ${manager.overrides[action.id] ? `<button type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-theme-muted transition hover:text-theme-heading" data-reset>${t('reset')}</button>` : ''}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-theme px-4 py-4 sm:px-6">
                        <button type="button" data-reset-all class="rounded-lg px-3 py-2 text-sm font-medium text-theme-muted transition hover:text-theme-heading">${t('resetAll')}</button>
                        <button type="button" data-shortcuts-done class="btn-primary rounded-lg px-4 py-2 text-sm font-semibold">${t('done')}</button>
                    </div>
                </div>
            </div>
        `;

        bindEvents();
    }

    function bindEvents() {
        const backdrop = root.querySelector('[data-shortcuts-backdrop]');

        backdrop?.addEventListener('click', (event) => {
            if (event.target === backdrop) {
                close();
            }
        });

        root.querySelector('[data-shortcuts-close]')?.addEventListener('click', close);
        root.querySelector('[data-shortcuts-done]')?.addEventListener('click', close);
        root.querySelector('[data-reset-all]')?.addEventListener('click', () => manager.resetAllShortcuts().then(render));

        root.querySelectorAll('[data-change]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('[data-action-id]');
                const actionId = row?.getAttribute('data-action-id');

                if (!actionId) {
                    return;
                }

                startRecording(row, actionId);
            });
        });

        root.querySelectorAll('[data-reset]').forEach((button) => {
            button.addEventListener('click', () => {
                const actionId = button.closest('[data-action-id]')?.getAttribute('data-action-id');

                if (actionId) {
                    manager.resetShortcut(actionId).then(render);
                }
            });
        });
    }

    function startRecording(row, actionId) {
        recordingActionId = actionId;
        chordFirstKey = '';
        recordingPreview = '';

        const kbd = row.querySelector('[data-combo-display]');
        const input = row.querySelector('[data-recording-input]');

        kbd?.classList.add('hidden');
        input?.classList.remove('hidden');
        input?.focus();

        const onKeyDown = (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (event.key === 'Escape') {
                cleanup();
                render();

                return;
            }

            if (event.key === 'Backspace' || event.key === 'Delete') {
                manager.setShortcut(actionId, '').then(() => {
                    cleanup();
                    render();
                });

                return;
            }

            const combo = comboFromKeyboardEvent(event);

            if (!combo) {
                return;
            }

            const hasModifiers = combo.includes('mod') || combo.includes('alt') || combo.includes('shift');

            if (!hasModifiers && !chordFirstKey) {
                chordFirstKey = combo;
                recordingPreview = `${formatComboLabel(combo, (k) => t(k))} then …`;
                if (input) {
                    input.value = recordingPreview;
                }

                return;
            }

            if (chordFirstKey && !hasModifiers) {
                const chord = normalizeCombo(`${chordFirstKey}>${combo}`);
                manager.setShortcut(actionId, chord).then(() => {
                    cleanup();
                    render();
                });

                return;
            }

            manager.setShortcut(actionId, combo).then(() => {
                cleanup();
                render();
            });
        };

        function cleanup() {
            input?.removeEventListener('keydown', onKeyDown);
            recordingActionId = '';
            chordFirstKey = '';
            recordingPreview = '';
        }

        input?.addEventListener('keydown', onKeyDown);
    }

    function open() {
        render();
        root.querySelector('[data-shortcuts-backdrop]')?.classList.remove('hidden');
        manager.openModal();
    }

    function close() {
        root.querySelector('[data-shortcuts-backdrop]')?.classList.add('hidden');
        manager.closeModal();
    }

    window.addEventListener(`${manager.appId}:shortcuts-modal-open`, open);
    window.addEventListener(`${manager.appId}:shortcuts-modal-close`, close);

    return { open, close, render };
}

export {
    buildShortcutMap,
    createShortcutMatcher,
    formatComboLabel,
    createPreferencesApi,
    createKeyboardEvents,
};
