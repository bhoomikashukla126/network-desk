import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

function readChromePreference(storageKey) {
    try {
        if (localStorage.getItem(storageKey) === 'false') {
            return false;
        }
    } catch {
        // Ignore storage errors.
    }

    return true;
}

export function useAppChrome(storageKey) {
    const { t } = useI18n();
    const chromeVisible = ref(readChromePreference(storageKey));

    const chromeToggleLabel = computed(() => (
        chromeVisible.value ? t('app.enterFullscreen') : t('app.exitFullscreen')
    ));

    function toggleChrome() {
        chromeVisible.value = !chromeVisible.value;
    }

    watch(chromeVisible, (visible) => {
        try {
            localStorage.setItem(storageKey, visible ? 'true' : 'false');
        } catch {
            // Ignore storage errors.
        }
    });

    return {
        chromeVisible,
        chromeToggleLabel,
        toggleChrome,
    };
}
