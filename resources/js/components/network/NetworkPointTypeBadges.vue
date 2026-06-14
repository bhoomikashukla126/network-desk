<template>
    <div class="flex flex-wrap gap-1.5">
        <span
            v-for="type in types"
            :key="type"
            class="inline-flex items-center gap-1.5 rounded-full border border-theme bg-theme-background px-2.5 py-1 text-xs font-medium text-theme-body"
        >
            <span
                class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                :style="{ background: pointColor(type), color: '#fff' }"
            >
                <NetworkPointTypeIcon :type="type" size-class="h-3 w-3" />
            </span>
            {{ labelFor(type) }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { normalizePointTypes, pointColor, pointTypeLabel } from '../../utils/networkMap';
import NetworkPointTypeIcon from './NetworkPointTypeIcon.vue';

const props = defineProps({
    point: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
});

const types = computed(() => normalizePointTypes(props.point));

function labelFor(type) {
    return props.labels[type] ?? pointTypeLabel(type);
}
</script>
