<template>
    <div class="hidden overflow-hidden rounded-xl border border-theme md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-theme text-sm">
                <thead class="app-table-head">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('complaints.columns.title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('complaints.columns.customer') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('complaints.columns.priority') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('complaints.columns.status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('complaints.columns.assignee') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">{{ t('complaints.columns.updated') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme bg-theme-card">
                    <tr v-for="complaint in complaints" :key="complaint.id" class="app-table-row">
                        <td class="px-4 py-4 font-medium text-theme-heading">
                            <RouterLink
                                v-if="canEdit"
                                :to="{ name: 'complaints.edit', params: { id: complaint.id } }"
                                class="link-theme"
                            >
                                {{ complaint.title }}
                            </RouterLink>
                            <span v-else>{{ complaint.title }}</span>
                        </td>
                        <td class="px-4 py-4 text-theme-body">
                            <div>{{ complaint.customer_name || '—' }}</div>
                            <div v-if="complaint.customer_email" class="text-xs text-theme-muted">{{ complaint.customer_email }}</div>
                        </td>
                        <td class="px-4 py-4"><PriorityBadge :priority="complaint.priority" /></td>
                        <td class="px-4 py-4"><StatusBadge :status="complaint.status" /></td>
                        <td class="px-4 py-4 text-theme-body">{{ complaint.assignee || t('complaints.unassigned') }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-theme-muted">{{ formatDate(complaint.updated_at) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import PriorityBadge from './PriorityBadge.vue';
import StatusBadge from './StatusBadge.vue';

defineProps({
    complaints: { type: Array, required: true },
    canEdit: { type: Boolean, default: false },
});

const { t } = useI18n();

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
</script>
