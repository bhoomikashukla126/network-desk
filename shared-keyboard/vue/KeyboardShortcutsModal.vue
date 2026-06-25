<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-4"
            @click.self="emit('close')"
        >
            <div
                class="flex h-[100dvh] w-full min-h-0 flex-col overflow-hidden border-theme bg-theme-card shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-3xl sm:rounded-2xl sm:border"
                role="dialog"
                aria-modal="true"
                :aria-label="t('shortcuts.title')"
            >
                <div class="flex items-start justify-between gap-4 border-b border-theme px-4 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-bold text-theme-heading">{{ t('shortcuts.title') }}</h2>
                        <p class="mt-1 text-sm text-theme-muted">{{ t('shortcuts.description') }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-theme p-2 text-theme-muted transition hover-surface"
                        :aria-label="t('shortcuts.close')"
                        @click="emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                    <p class="mb-4 rounded-xl border border-theme bg-theme-muted/40 px-4 py-3 text-sm text-theme-body">
                        {{ t('shortcuts.hint') }}
                    </p>

                    <div
                        v-for="category in categories"
                        :key="category"
                        class="mb-6 last:mb-0"
                    >
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-theme-muted">
                            {{ t(`shortcuts.categories.${category}`) }}
                        </h3>

                        <div class="overflow-hidden rounded-xl border border-theme">
                            <table class="min-w-full divide-y divide-theme text-sm">
                                <thead class="app-table-head text-left">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">{{ t('shortcuts.columns.action') }}</th>
                                        <th class="px-4 py-3 font-medium">{{ t('shortcuts.columns.shortcut') }}</th>
                                        <th class="px-4 py-3 font-medium text-right">{{ t('shortcuts.columns.edit') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-theme bg-theme-card">
                                    <tr
                                        v-for="action in actionsByCategory[category]"
                                        :key="action.id"
                                        class="app-table-row"
                                    >
                                        <td class="px-4 py-3 text-theme-body">
                                            {{ t(`shortcuts.actions.${action.id}`) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <kbd
                                                v-if="recordingActionId !== action.id"
                                                class="app-kbd inline-flex min-w-[5rem] items-center justify-center rounded-md border border-theme px-2 py-1 text-xs font-semibold text-theme-heading"
                                            >
                                                {{ formatComboLabel(resolvedCombo(action.id), t) }}
                                            </kbd>
                                            <input
                                                v-else
                                                ref="recordingInput"
                                                type="text"
                                                readonly
                                                class="app-input w-full max-w-xs rounded-lg border px-3 py-2 text-sm"
                                                :placeholder="t('shortcuts.pressKeys')"
                                                :value="recordingPreview"
                                                @keydown.prevent.stop="captureShortcut($event, action.id)"
                                                @keyup.prevent.stop
                                            >
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    v-if="recordingActionId === action.id"
                                                    type="button"
                                                    class="btn-secondary rounded-lg border px-2.5 py-1.5 text-xs font-medium"
                                                    @click="cancelRecording"
                                                >
                                                    {{ t('shortcuts.cancel') }}
                                                </button>
                                                <button
                                                    v-else
                                                    type="button"
                                                    class="btn-secondary rounded-lg border px-2.5 py-1.5 text-xs font-medium"
                                                    @click="startRecording(action.id)"
                                                >
                                                    {{ t('shortcuts.change') }}
                                                </button>
                                                <button
                                                    v-if="overrides[action.id]"
                                                    type="button"
                                                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-theme-muted transition hover:text-theme-heading"
                                                    @click="emit('reset', action.id)"
                                                >
                                                    {{ t('shortcuts.reset') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-theme px-4 py-4 sm:px-6">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-theme-muted transition hover:text-theme-heading"
                        @click="emit('reset-all')"
                    >
                        {{ t('shortcuts.resetAll') }}
                    </button>
                    <button
                        type="button"
                        class="btn-primary rounded-lg px-4 py-2 text-sm font-semibold"
                        @click="emit('close')"
                    >
                        {{ t('shortcuts.done') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { X } from 'lucide-vue-next';
import {
    SHORTCUT_CATEGORIES,
    comboFromKeyboardEvent,
    formatComboLabel,
    normalizeCombo,
} from '../js/core.js';

const props = defineProps({
    open: { type: Boolean, default: false },
    actions: { type: Object, required: true },
    overrides: { type: Object, default: () => ({}) },
    shortcutMap: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'save', 'reset', 'reset-all']);

const { t } = useI18n();

const recordingActionId = ref('');
const recordingPreview = ref('');
const chordFirstKey = ref('');
const recordingInput = ref(null);

const categories = Object.values(SHORTCUT_CATEGORIES);

const actionsByCategory = computed(() => categories.reduce((groups, category) => {
    groups[category] = Object.values(props.actions).filter((action) => action.category === category);

    return groups;
}, {}));

function resolvedCombo(actionId) {
    return props.shortcutMap[actionId] ?? props.actions[actionId]?.defaultCombo ?? '';
}

function startRecording(actionId) {
    recordingActionId.value = actionId;
    recordingPreview.value = '';
    chordFirstKey.value = '';

    nextTick(() => {
        const input = Array.isArray(recordingInput.value)
            ? recordingInput.value[0]
            : recordingInput.value;

        input?.focus();
    });
}

function cancelRecording() {
    recordingActionId.value = '';
    recordingPreview.value = '';
    chordFirstKey.value = '';
}

function captureShortcut(event, actionId) {
    if (event.key === 'Escape') {
        cancelRecording();

        return;
    }

    if (event.key === 'Backspace' || event.key === 'Delete') {
        emit('save', actionId, '');
        cancelRecording();

        return;
    }

    const combo = comboFromKeyboardEvent(event);

    if (!combo) {
        return;
    }

    const hasModifiers = combo.includes('mod') || combo.includes('alt') || combo.includes('shift');

    if (!hasModifiers && !chordFirstKey.value) {
        chordFirstKey.value = combo;
        recordingPreview.value = `${formatComboLabel(combo, t)} then …`;

        return;
    }

    if (chordFirstKey.value && !hasModifiers) {
        const chord = normalizeCombo(`${chordFirstKey.value}>${combo}`);
        emit('save', actionId, chord);
        cancelRecording();

        return;
    }

    emit('save', actionId, combo);
    cancelRecording();
}

watch(() => props.open, (visible) => {
    if (!visible) {
        cancelRecording();
    }
});
</script>
