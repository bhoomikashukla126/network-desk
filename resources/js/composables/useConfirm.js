import { reactive, readonly } from 'vue';
import { i18n } from '../i18n';

const state = reactive({
    visible: false,
    title: 'Confirm',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'danger',
});

let resolver = null;

export function confirmAction(input) {
    const options = typeof input === 'string' ? { message: input } : (input ?? {});
    const t = i18n.global.t;

    state.title = options.title ?? t('common.confirmTitle');
    state.message = options.message ?? '';
    state.confirmLabel = options.confirmLabel ?? t('common.confirm');
    state.cancelLabel = options.cancelLabel ?? t('common.cancel');
    state.variant = options.variant ?? 'danger';
    state.visible = true;

    return new Promise((resolve) => {
        resolver = resolve;
    });
}

export function useConfirmState() {
    return readonly(state);
}

export function resolveConfirm(confirmed) {
    state.visible = false;
    resolver?.(confirmed);
    resolver = null;
}
