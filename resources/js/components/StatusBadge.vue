<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="classes">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    status: { type: String, required: true },
});

const { t } = useI18n();

const label = computed(() => {
    const key = `complaints.status.${props.status}`;
    const translated = t(key);

    return translated !== key ? translated : props.status.replaceAll('_', ' ');
});

const classes = computed(() => ({
    open: 'app-chip-info',
    in_progress: 'app-chip-warning',
    resolved: 'app-chip-success',
    closed: 'app-chip-neutral',
}[props.status] ?? 'app-chip-neutral'));
</script>
