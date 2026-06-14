<template>
    <section class="app-card rounded-2xl border shadow-sm">
        <div class="border-b border-theme px-4 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-theme-heading">{{ t('access.members.title') }}</h2>
            <p class="mt-1 text-sm text-theme-muted">{{ t('access.members.description') }}</p>
        </div>

        <div v-if="loading" class="p-6 text-sm text-theme-muted">{{ t('access.members.loading') }}</div>
        <div v-else-if="error" class="app-alert-error m-6 rounded-xl border px-4 py-3 text-sm">{{ error }}</div>
        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-theme text-sm">
                <thead class="app-table-head">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('access.members.member') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('access.members.role') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('access.members.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme bg-theme-card">
                    <tr v-for="member in members" :key="member.id" class="app-table-row">
                        <td class="px-4 py-4">
                            <p class="font-medium text-theme-heading">
                                {{ member.name }}
                                <span v-if="member.is_current_user" class="text-theme-primary ml-1 text-xs">{{ t('access.members.you') }}</span>
                            </p>
                            <p class="text-xs text-theme-muted">{{ member.email || member.central_user_id }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <select
                                v-model="member.role.id"
                                class="app-input min-w-40 w-full rounded-lg border px-3 py-2 text-sm"
                                :disabled="savingId === member.id"
                                @change="updateMemberRole(member)"
                            >
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                        </td>
                        <td class="px-4 py-4 text-theme-muted">{{ member.role.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { api } from '../../api/client';

const { t } = useI18n();

const members = ref([]);
const roles = ref([]);
const loading = ref(true);
const savingId = ref(null);
const error = ref('');

async function loadMembers() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api('/api/members');
        members.value = response.data;
        roles.value = response.roles;
    } catch (err) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
}

async function updateMemberRole(member) {
    savingId.value = member.id;
    error.value = '';

    try {
        const response = await api(`/api/members/${member.id}`, {
            method: 'PUT',
            body: JSON.stringify({ role_id: member.role.id }),
        });
        const index = members.value.findIndex((item) => item.id === member.id);
        if (index >= 0) members.value[index] = response.data;
    } catch (err) {
        error.value = err.message;
        await loadMembers();
    } finally {
        savingId.value = null;
    }
}

onMounted(loadMembers);
</script>
