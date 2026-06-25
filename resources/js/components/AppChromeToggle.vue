<template>
    <button
        type="button"
        class="app-chrome-toggle inline-flex items-center justify-center rounded-lg border p-2.5 shadow-sm transition"
        :class="floating ? 'fixed right-4 top-4 z-[60] shadow-lg' : ''"
        :aria-label="label"
        :title="label"
        @click="$emit('toggle')"
    >
        <component :is="chromeVisible ? Minimize2 : Maximize2" class="h-4 w-4 shrink-0" />
    </button>
</template>

<script setup>
import { Maximize2, Minimize2 } from 'lucide-vue-next';

defineProps({
    chromeVisible: { type: Boolean, required: true },
    label: { type: String, required: true },
    floating: { type: Boolean, default: false },
});

defineEmits(['toggle']);
</script>
