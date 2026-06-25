<template>
    <div :class="rootClass">
        <p class="font-medium text-theme-heading">{{ log.description }}</p>
        <dl
            v-if="detailLines.length"
            class="mt-2 space-y-1.5 rounded-lg border border-theme/60 bg-theme-background/60 px-3 py-2.5 text-xs"
        >
            <div
                v-for="line in detailLines"
                :key="line.key"
                class="grid grid-cols-1 gap-0.5 sm:grid-cols-[minmax(0,8rem)_1fr] sm:gap-x-3"
            >
                <dt class="font-semibold uppercase tracking-wide text-theme-muted">{{ line.label }}</dt>
                <dd class="break-words text-theme-body">{{ line.value }}</dd>
            </div>
        </dl>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { buildActivityDetailLines } from '../utils/activityLogDetails';

const props = defineProps({
    log: { type: Object, required: true },
    rootClass: { type: String, default: '' },
});

const { t } = useI18n();

const detailLines = computed(() => buildActivityDetailLines(props.log, t));
</script>
