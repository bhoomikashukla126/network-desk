<template>
    <form class="space-y-4" @submit.prevent="$emit('submit')">
        <div>
            <h3 class="text-base font-semibold text-theme-heading">
                {{ mode === 'create' ? t('access.roleForm.createTitle') : t('access.roleForm.editTitle', { name: form.name }) }}
            </h3>
            <p class="mt-1 text-sm text-theme-muted">
                {{ mode === 'create' ? t('access.roleForm.createDescription') : t('access.roleForm.editDescription') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="app-label mb-1.5 block text-sm font-medium">{{ t('access.roleForm.roleName') }}</label>
                <input
                    v-model="form.name"
                    required
                    class="app-input w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm disabled:opacity-60"
                    type="text"
                    maxlength="80"
                    :disabled="mode === 'edit' && isSystem"
                >
                <p v-if="mode === 'edit' && isSystem" class="mt-1 text-xs text-theme-muted">{{ t('access.roleForm.systemNameHint') }}</p>
            </div>
            <div>
                <label class="app-label mb-1.5 block text-sm font-medium">{{ t('access.roleForm.description') }}</label>
                <input
                    v-model="form.description"
                    class="app-input w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm"
                    type="text"
                    maxlength="255"
                >
            </div>
        </div>

        <div>
            <p class="app-label mb-2 text-sm font-medium">{{ t('access.roleForm.permissions') }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <label
                    v-for="permission in permissions"
                    :key="permission.key"
                    class="app-option flex items-start gap-3 rounded-xl border p-3"
                    :class="permission.key === 'network.view' ? 'app-option-selected' : ''"
                >
                    <input
                        v-model="form.permissions"
                        type="checkbox"
                        class="mt-1 rounded border-theme text-theme-primary focus:ring-theme-primary"
                        :value="permission.key"
                        :disabled="permission.key === 'network.view'"
                    >
                    <span>
                        <span class="block text-sm font-medium text-theme-heading">
                            {{ permissionLabel(permission.key, permission.label) }}
                            <span v-if="permission.key === 'network.view'" class="text-theme-primary text-xs font-normal">{{ t('access.roleForm.required') }}</span>
                        </span>
                        <span class="block text-xs capitalize text-theme-muted">{{ permissionGroup(permission.group) }}</span>
                    </span>
                </label>
            </div>
        </div>

        <p v-if="error" class="text-sm text-rose-500">{{ error }}</p>

        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                class="btn-primary rounded-lg px-4 py-2.5 text-sm font-semibold transition disabled:opacity-60"
                :disabled="saving"
            >
                {{ mode === 'create' ? t('access.roleForm.createRole') : t('access.roleForm.saveChanges') }}
            </button>
            <button
                type="button"
                class="btn-secondary rounded-lg border px-4 py-2.5 text-sm font-semibold transition"
                :disabled="saving"
                @click="$emit('cancel')"
            >
                {{ t('common.cancel') }}
            </button>
            <button
                v-if="mode === 'edit' && !isSystem"
                type="button"
                class="btn-danger-outline ml-auto rounded-lg border px-4 py-2.5 text-sm font-semibold transition disabled:opacity-60"
                :disabled="saving"
                @click="$emit('delete')"
            >
                {{ t('access.roleForm.deleteRole') }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    mode: { type: String, required: true },
    form: { type: Object, required: true },
    permissions: { type: Array, required: true },
    error: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    isSystem: { type: Boolean, default: false },
});

defineEmits(['submit', 'cancel', 'delete']);

const { t } = useI18n();

function permissionLabel(key, fallback) {
    const i18nKey = `access.permissions.${key}`;
    const translated = t(i18nKey);

    return translated !== i18nKey ? translated : fallback;
}

function permissionGroup(group) {
    const i18nKey = `access.permissionGroups.${group}`;
    const translated = t(i18nKey);

    return translated !== i18nKey ? translated : group;
}
</script>
