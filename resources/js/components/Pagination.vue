<template>
    <div v-if="meta.total > 0" class="flex flex-col gap-3 border-t border-theme pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-theme-muted">
            {{ t('common.pagination.showing', {
                from: meta.from ?? 0,
                to: meta.to ?? 0,
                total: meta.total,
                label,
            }) }}
        </p>

        <div v-if="meta.last_page > 1" class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="btn-secondary rounded-lg border px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="meta.current_page <= 1"
                @click="$emit('change', meta.current_page - 1)"
            >
                {{ t('common.pagination.previous') }}
            </button>

            <div class="flex flex-wrap gap-1">
                <button
                    v-for="page in pages"
                    :key="page"
                    type="button"
                    class="min-w-9 rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="page === meta.current_page ? 'bg-theme-primary text-white' : 'btn-secondary border'"
                    @click="$emit('change', page)"
                >
                    {{ page }}
                </button>
            </div>

            <button
                type="button"
                class="btn-secondary rounded-lg border px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="meta.current_page >= meta.last_page"
                @click="$emit('change', meta.current_page + 1)"
            >
                {{ t('common.pagination.next') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    meta: { type: Object, required: true },
    label: { type: String, default: 'items' },
});

defineEmits(['change']);

const { t } = useI18n();

const pages = computed(() => {
    const total = props.meta.last_page;
    const current = props.meta.current_page;
    const window = 2;
    const start = Math.max(1, current - window);
    const end = Math.min(total, current + window);
    const result = [];

    for (let page = start; page <= end; page += 1) {
        result.push(page);
    }

    return result;
});
</script>
