<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="classes">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    priority: { type: String, required: true },
});

const { t } = useI18n();

const label = computed(() => {
    const key = `complaints.priority.${props.priority}`;
    const translated = t(key);

    return translated !== key ? translated : props.priority;
});

const classes = computed(() => ({
    low: 'app-chip-neutral',
    medium: 'app-tag-chip',
    high: 'app-chip-warning',
    urgent: 'app-chip-danger',
}[props.priority] ?? 'app-chip-neutral'));
</script>
