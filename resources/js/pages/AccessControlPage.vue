<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-theme-heading">{{ t('access.title') }}</h1>
            <p class="mt-1 text-sm text-theme-muted">{{ t('access.description') }}</p>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-theme pb-1">
            <button
                v-if="canManageRoles"
                type="button"
                class="rounded-t-lg px-4 py-2 text-sm font-medium transition"
                :class="activeTab === 'roles' ? 'app-tab-active' : 'app-tab-inactive'"
                @click="activeTab = 'roles'"
            >
                {{ t('access.tabs.roles') }}
            </button>
            <button
                v-if="canManageMembers"
                type="button"
                class="rounded-t-lg px-4 py-2 text-sm font-medium transition"
                :class="activeTab === 'members' ? 'app-tab-active' : 'app-tab-inactive'"
                @click="activeTab = 'members'"
            >
                {{ t('access.tabs.members') }}
            </button>
        </div>

        <RolesPanel v-if="activeTab === 'roles' && canManageRoles" />
        <MembersPanel v-if="activeTab === 'members' && canManageMembers" />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import RolesPanel from '../components/access/RolesPanel.vue';
import MembersPanel from '../components/access/MembersPanel.vue';

const props = defineProps({
    session: { type: Object, default: null },
});

const { t } = useI18n();

const canManageRoles = computed(() => props.session?.can_manage_roles);
const canManageMembers = computed(() => props.session?.can_manage_members);
const activeTab = ref(canManageRoles.value ? 'roles' : 'members');
</script>
