<template>
    <section class="app-card rounded-2xl border shadow-sm">
        <div class="flex flex-col gap-3 border-b border-theme px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-lg font-semibold text-theme-heading">{{ t('access.roles.title') }}</h2>
                <p class="mt-1 text-sm text-theme-muted">{{ t('access.roles.description') }}</p>
            </div>
            <button
                type="button"
                class="btn-primary inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                @click="startCreate"
            >
                <Plus class="h-4 w-4" />
                {{ t('access.roles.createRole') }}
            </button>
        </div>

        <div v-if="loading" class="p-6 text-sm text-theme-muted">{{ t('access.roles.loading') }}</div>

        <div v-else class="divide-y divide-theme">
            <div v-if="isCreating" id="role-create-form" class="app-panel-accent px-4 py-5 sm:px-6">
                <RoleForm
                    mode="create"
                    :form="form"
                    :permissions="permissions"
                    :error="error"
                    :saving="saving"
                    @submit="saveRole"
                    @cancel="cancelForm"
                />
            </div>

            <template v-for="role in roles" :key="role.id">
                <div
                    class="px-4 py-4 sm:px-6"
                    :class="{ 'app-panel-accent': editingRole?.id === role.id && !isCreating }"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-theme-heading">{{ role.name }}</p>
                                <span v-if="role.is_system" class="app-badge-neutral rounded-full px-2 py-0.5 text-xs font-medium">{{ t('access.roles.system') }}</span>
                                <span
                                    v-if="editingRole?.id === role.id && !isCreating"
                                    class="app-badge-count rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ t('access.roles.editing') }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-theme-muted">{{ role.description || t('access.roles.noDescription') }}</p>
                            <p class="mt-2 text-xs text-theme-muted opacity-80">{{ t('access.roles.stats', { members: role.members_count, permissions: role.permissions.length }) }}</p>
                        </div>

                        <button
                            v-if="editingRole?.id !== role.id || isCreating"
                            type="button"
                            class="btn-secondary rounded-lg border px-4 py-2 text-sm font-medium transition"
                            @click="startEdit(role)"
                        >
                            {{ t('access.roles.editPermissions') }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="btn-secondary rounded-lg border px-4 py-2 text-sm font-medium transition"
                            @click="cancelForm"
                        >
                            {{ t('common.cancel') }}
                        </button>
                    </div>

                    <div
                        v-if="editingRole?.id === role.id && !isCreating"
                        :id="`role-editor-${role.id}`"
                        class="app-card mt-5 rounded-xl border p-4 shadow-sm"
                    >
                        <RoleForm
                            mode="edit"
                            :form="form"
                            :permissions="permissions"
                            :error="error"
                            :saving="saving"
                            :is-system="role.is_system"
                            @submit="saveRole"
                            @cancel="cancelForm"
                            @delete="deleteRole"
                        />
                    </div>
                </div>
            </template>
        </div>
    </section>
</template>

<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Plus } from 'lucide-vue-next';
import { api } from '../../api/client';
import { confirmAction } from '../../composables/useConfirm';
import RoleForm from './RoleForm.vue';

const { t } = useI18n();
const roles = ref([]);
const permissions = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const editingRole = ref(null);
const isCreating = ref(false);

const form = reactive({
    name: '',
    description: '',
    permissions: [],
});

async function loadData() {
    loading.value = true;
    const [rolesResponse, permissionsResponse] = await Promise.all([
        api('/api/roles'),
        api('/api/permissions'),
    ]);
    roles.value = rolesResponse.data;
    permissions.value = permissionsResponse.data;
    loading.value = false;
}

function resetForm() {
    editingRole.value = null;
    isCreating.value = false;
    form.name = '';
    form.description = '';
    form.permissions = [];
    error.value = '';
}

function cancelForm() {
    resetForm();
}

async function startCreate() {
    resetForm();
    isCreating.value = true;
    form.permissions = ['network.view'];

    await nextTick();
    document.getElementById('role-create-form')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function startEdit(role) {
    resetForm();
    editingRole.value = role;
    form.name = role.name;
    form.description = role.description ?? '';
    form.permissions = [...role.permissions];

    await nextTick();
    document.getElementById(`role-editor-${role.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function saveRole() {
    saving.value = true;
    error.value = '';

    try {
        if (editingRole.value) {
            await api(`/api/roles/${editingRole.value.id}`, {
                method: 'PUT',
                body: JSON.stringify(form),
            });
        } else {
            await api('/api/roles', {
                method: 'POST',
                body: JSON.stringify(form),
            });
        }

        resetForm();
        await loadData();
    } catch (err) {
        error.value = err.message;
        if (err.errors?.permissions?.[0]) {
            error.value = err.errors.permissions[0];
        }
    } finally {
        saving.value = false;
    }
}

async function deleteRole() {
    if (!editingRole.value || !await confirmAction({
        message: t('access.roles.deleteRole', { name: editingRole.value.name }),
        title: t('access.roles.deleteRoleTitle'),
        confirmLabel: t('common.delete'),
    })) return;

    saving.value = true;
    error.value = '';

    try {
        await api(`/api/roles/${editingRole.value.id}`, { method: 'DELETE' });
        resetForm();
        await loadData();
    } catch (err) {
        error.value = err.message;
    } finally {
        saving.value = false;
    }
}

onMounted(loadData);
</script>
