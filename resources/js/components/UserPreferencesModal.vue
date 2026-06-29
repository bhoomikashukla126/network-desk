<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex bg-slate-900/50 sm:items-center sm:justify-center sm:p-4"
                @click.self="close"
            >
                <div
                    class="app-card flex h-[100dvh] w-full min-h-0 flex-col overflow-hidden border shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-2xl sm:rounded-2xl"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="t('preferences.title')"
                >
                    <div class="flex shrink-0 items-start justify-between gap-3 border-b border-theme px-4 py-4 sm:px-6">
                        <div class="min-w-0 pr-2">
                            <h2 class="text-lg font-bold text-theme-heading sm:text-xl">{{ t('preferences.title') }}</h2>
                            <p class="mt-1 text-sm text-theme-muted">{{ t('preferences.description') }}</p>
                            <p v-if="defaultsHint" class="mt-2 text-xs text-theme-muted">{{ defaultsHint }}</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-theme-muted transition hover:bg-theme-background hover:text-theme-body"
                            :aria-label="t('preferences.close')"
                            @click="close"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6">
                        <div v-if="loading" class="flex items-center justify-center py-16 text-theme-muted">
                            <LoaderCircle class="h-6 w-6 animate-spin" />
                            <span class="ml-2">{{ t('common.loading') }}</span>
                        </div>

                        <div v-else-if="loadError" class="app-alert-error rounded-xl border px-4 py-3 text-sm">
                            {{ loadError }}
                        </div>

                        <form v-else class="space-y-6" @submit.prevent="save">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="text-sm font-medium text-theme-heading" for="pref-language">{{ t('preferences.language') }}</label>
                                    <label class="inline-flex items-center gap-2 text-xs text-theme-muted">
                                        <input v-model="form.use_workspace_language" type="checkbox" class="rounded border-theme text-theme-primary focus:ring-theme-primary">
                                        {{ t('preferences.useWorkspaceDefault') }}
                                    </label>
                                </div>
                                <select
                                    id="pref-language"
                                    v-model="form.language"
                                    class="app-input w-full rounded-xl border px-4 py-3 text-sm"
                                    :disabled="form.use_workspace_language"
                                    :class="fieldDisabledClass(form.use_workspace_language)"
                                >
                                    <option v-for="(label, code) in languages" :key="code" :value="code">{{ label }}</option>
                                </select>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-theme-heading">{{ t('preferences.themeColor') }}</span>
                                    <label class="inline-flex items-center gap-2 text-xs text-theme-muted">
                                        <input v-model="form.use_workspace_theme" type="checkbox" class="rounded border-theme text-theme-primary focus:ring-theme-primary">
                                        {{ t('preferences.useWorkspaceDefault') }}
                                    </label>
                                </div>
                                <div
                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                                    :class="fieldDisabledClass(form.use_workspace_theme)"
                                >
                                    <label
                                        v-for="theme in themeList"
                                        :key="theme.key"
                                        class="app-option relative cursor-pointer rounded-xl border-2 p-3 transition"
                                        :class="form.theme_key === theme.key ? 'app-option-selected ring-2 ring-theme-primary' : ''"
                                    >
                                        <input
                                            v-model="form.theme_key"
                                            type="radio"
                                            class="peer sr-only"
                                            :value="theme.key"
                                            :disabled="form.use_workspace_theme"
                                        >
                                        <span
                                            class="theme-picker-check pointer-events-none absolute right-2 top-2 hidden h-6 w-6 items-center justify-center rounded-full text-xs font-bold peer-checked:flex"
                                            aria-hidden="true"
                                        >✓</span>
                                        <div class="mb-2 flex items-center gap-2 pr-6">
                                            <span class="ring-theme h-5 w-5 rounded-full border border-white shadow-sm ring-1" :style="{ backgroundColor: theme.primary }" />
                                            <span class="ring-theme h-5 w-5 rounded-full border border-white shadow-sm ring-1" :style="{ backgroundColor: theme.secondary }" />
                                            <span class="ring-theme h-5 w-5 rounded-full border border-white shadow-sm ring-1" :style="{ backgroundColor: theme.accent }" />
                                        </div>
                                        <span class="block text-sm font-medium text-theme-heading">{{ theme.name }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="text-sm font-medium text-theme-heading" for="pref-color-mode">{{ t('preferences.colorMode') }}</label>
                                    <label class="inline-flex items-center gap-2 text-xs text-theme-muted">
                                        <input v-model="form.use_workspace_color_mode" type="checkbox" class="rounded border-theme text-theme-primary focus:ring-theme-primary">
                                        {{ t('preferences.useWorkspaceDefault') }}
                                    </label>
                                </div>
                                <select
                                    id="pref-color-mode"
                                    v-model="form.color_mode"
                                    class="app-input w-full rounded-xl border px-4 py-3 text-sm"
                                    :disabled="form.use_workspace_color_mode"
                                    :class="fieldDisabledClass(form.use_workspace_color_mode)"
                                >
                                    <option v-for="mode in colorModes" :key="mode" :value="mode">{{ t(`preferences.colorModes.${mode}`) }}</option>
                                </select>
                            </div>

                            <p v-if="saveError" class="text-sm text-rose-500">{{ saveError }}</p>
                            <p v-if="saved" class="app-alert-success rounded-xl border px-4 py-3 text-sm">{{ t('preferences.updated') }}</p>

                            <button
                                type="submit"
                                class="btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold transition"
                                :disabled="saving"
                            >
                                {{ saving ? t('common.loading') : t('preferences.save') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { LoaderCircle, X } from 'lucide-vue-next';
import { api } from '../api/client';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();

const loading = ref(false);
const saving = ref(false);
const saved = ref(false);
const loadError = ref('');
const saveError = ref('');
const languages = ref({});
const themes = ref({});
const colorModes = ref(['light', 'dark', 'system']);
const form = ref({
    language: 'en',
    theme_key: 'forest',
    color_mode: 'light',
    use_workspace_language: true,
    use_workspace_theme: true,
    use_workspace_color_mode: true,
});
const workspaceDefaults = ref(null);

const themeList = computed(() => Object.values(themes.value));

const defaultsHint = computed(() => {
    if (!workspaceDefaults.value) {
        return '';
    }

    const languageLabel = languages.value[workspaceDefaults.value.language] ?? workspaceDefaults.value.language;
    const themeLabel = themes.value[workspaceDefaults.value.theme_key]?.name ?? workspaceDefaults.value.theme_key;
    const modeLabel = t(`preferences.colorModes.${workspaceDefaults.value.color_mode}`);

    return t('preferences.workspaceDefaultsHint', {
        language: languageLabel,
        theme: themeLabel,
        mode: modeLabel,
    });
});

function fieldDisabledClass(disabled) {
    return disabled ? 'pointer-events-none opacity-50' : '';
}

function applyPayload(data) {
    const effective = data.effective ?? {};
    const uses = data.uses_workspace_default ?? {};

    form.value = {
        language: effective.language ?? 'en',
        theme_key: effective.theme_key ?? 'forest',
        color_mode: effective.color_mode ?? 'light',
        use_workspace_language: uses.language ?? true,
        use_workspace_theme: uses.theme_key ?? true,
        use_workspace_color_mode: uses.color_mode ?? true,
    };
    workspaceDefaults.value = data.workspace_defaults ?? null;
}

async function loadPreferences() {
    loading.value = true;
    loadError.value = '';
    saved.value = false;
    saveError.value = '';

    try {
        const response = await api('/api/user-preferences');
        languages.value = response.languages ?? {};
        themes.value = response.themes ?? {};
        colorModes.value = response.color_modes ?? ['light', 'dark', 'system'];
        applyPayload(response.data ?? {});
    } catch (error) {
        loadError.value = error?.message || t('preferences.loadError');
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value = true;
    saveError.value = '';
    saved.value = false;

    try {
        const response = await api('/api/user-preferences', {
            method: 'PUT',
            body: {
                use_workspace_language: form.value.use_workspace_language,
                language: form.value.language,
                use_workspace_theme: form.value.use_workspace_theme,
                theme_key: form.value.theme_key,
                use_workspace_color_mode: form.value.use_workspace_color_mode,
                color_mode: form.value.color_mode,
            },
        });

        applyPayload(response.data ?? {});
        saved.value = true;
        emit('saved', response.data?.context ?? null);
    } catch (error) {
        saveError.value = error?.message || t('preferences.saveError');
    } finally {
        saving.value = false;
    }
}

function close() {
    emit('close');
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            loadPreferences();
        }
    },
);
</script>
