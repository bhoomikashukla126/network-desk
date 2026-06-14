<template>
    <div class="grid gap-4 md:hidden">
        <article
            v-for="complaint in complaints"
            :key="complaint.id"
            class="app-card rounded-xl border p-4 shadow-sm"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <RouterLink
                        v-if="canEdit"
                        :to="{ name: 'complaints.edit', params: { id: complaint.id } }"
                        class="link-theme text-base font-semibold"
                    >
                        {{ complaint.title }}
                    </RouterLink>
                    <h3 v-else class="text-base font-semibold text-theme-heading">{{ complaint.title }}</h3>
                    <p class="mt-1 text-sm text-theme-muted">{{ complaint.customer_name || t('complaints.noCustomer') }}</p>
                </div>
                <StatusBadge :status="complaint.status" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="app-field-label text-xs uppercase tracking-wide">{{ t('complaints.columns.priority') }}</p>
                    <div class="mt-1"><PriorityBadge :priority="complaint.priority" /></div>
                </div>
                <div>
                    <p class="app-field-label text-xs uppercase tracking-wide">{{ t('complaints.columns.assignee') }}</p>
                    <p class="mt-1 text-theme-body">{{ complaint.assignee || t('complaints.unassigned') }}</p>
                </div>
            </div>

            <p class="mt-3 text-xs text-theme-muted">{{ t('complaints.updated') }} {{ formatDate(complaint.updated_at) }}</p>
        </article>
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
