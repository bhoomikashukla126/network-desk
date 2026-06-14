<template>
    <component
        :is="iconComponent"
        class="shrink-0"
        :class="iconClass"
        aria-hidden="true"
    />
</template>

<script setup>
import { computed } from 'vue';
import {
    Box,
    Cable,
    GitBranch,
    Globe,
    Home,
    Network,
    Router,
    Server,
    Signpost,
    Waves,
} from 'lucide-vue-next';

const props = defineProps({
    type: { type: String, default: 'junction' },
    sizeClass: { type: String, default: 'h-3.5 w-3.5' },
});

const icons = {
    uplink: Globe,
    bras: Router,
    switch: Network,
    odf: Cable,
    dwdm: Waves,
    router: Router,
    splitter: GitBranch,
    junction: Box,
    cabinet: Server,
    pole: Signpost,
    customer: Home,
};

const iconComponent = computed(() => icons[props.type] ?? Box);
const iconClass = computed(() => props.sizeClass);
</script>
