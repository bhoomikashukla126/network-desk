<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="classes">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: '' },
});

const classes = computed(() => ({
    active: 'app-chip-success',
    planned: 'app-chip-info',
    maintenance: 'app-chip-warning',
    inactive: 'app-chip-neutral',
}[props.status] ?? 'app-chip-neutral'));

const label = computed(() => props.label || props.status.replaceAll('_', ' '));
</script>
