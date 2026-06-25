<template>
    <div class="map-editor-page flex min-h-0 flex-1 flex-col gap-4">
        <section class="app-card shrink-0 rounded-2xl border p-4 shadow-sm sm:p-6">
            <div>
                <h1 class="text-2xl font-bold text-theme-heading">{{ $t('map.title') }}</h1>
                <p class="mt-1 text-sm text-theme-muted">{{ $t('map.description') }}</p>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-theme-muted" />
                    <input
                        v-model="search"
                        type="search"
                        class="app-input w-full rounded-xl border py-2.5 pl-9 text-sm shadow-sm"
                        :class="search ? 'pr-9' : 'pr-3.5'"
                        :placeholder="$t('map.searchPlaceholder')"
                        @input="debouncedLoad"
                    >
                    <button
                        v-if="search"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-theme-muted transition hover:bg-theme-background hover:text-theme-heading"
                        :aria-label="$t('map.clearSearch')"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div v-if="permissions.create" class="flex shrink-0 flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn-primary inline-flex items-center justify-center gap-2 rounded-lg px-2.5 py-2.5 text-sm font-semibold shadow-sm transition sm:px-4"
                        :class="interactionMode === 'add' ? 'ring-2 ring-theme-primary ring-offset-2' : ''"
                        @click="setInteractionMode(interactionMode === 'add' ? 'view' : 'add')"
                    >
                        <MapPinPlus class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:inline">{{ interactionMode === 'add' ? $t('map.cancelAdd') : $t('map.addPoint') }}</span>
                    </button>
                    <button
                        type="button"
                        class="btn-secondary inline-flex items-center justify-center gap-2 rounded-lg border px-2.5 py-2.5 text-sm font-semibold shadow-sm transition sm:px-4"
                        :class="interactionMode === 'cable' ? 'ring-2 ring-theme-primary ring-offset-2' : ''"
                        @click="setInteractionMode(interactionMode === 'cable' ? 'view' : 'cable')"
                    >
                        <Cable class="h-4 w-4 shrink-0" />
                        <span class="hidden sm:inline">{{ interactionMode === 'cable' ? $t('map.cancelCable') : $t('map.drawCable') }}</span>
                    </button>
                </div>
            </div>

            <p v-if="search.trim() && !loading" class="mt-2 text-xs text-theme-muted">
                {{ $t('map.searchResults', { count: points.length }) }}
            </p>
        </section>

        <div
            v-if="!permissions.create"
            class="shrink-0 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
        >
            <p class="font-medium">{{ $t('map.viewOnlyTitle') }}</p>
            <p class="mt-1 opacity-90">{{ $t('map.viewOnlyHint') }}</p>
        </div>

        <div
            v-if="showSidePanel"
            class="grid shrink-0 grid-cols-2 gap-2 xl:hidden"
            role="tablist"
            :aria-label="$t('map.mobileViewTabs')"
        >
            <button
                type="button"
                role="tab"
                class="rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                :class="mobileActiveTab === 'map' ? 'app-tab-active' : 'app-tab-inactive border border-theme'"
                :aria-selected="mobileActiveTab === 'map'"
                @click="mobileActiveTab = 'map'"
            >
                {{ $t('map.mobileTabMap') }}
            </button>
            <button
                type="button"
                role="tab"
                class="rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                :class="mobileActiveTab === 'panel' ? 'app-tab-active' : 'app-tab-inactive border border-theme'"
                :aria-selected="mobileActiveTab === 'panel'"
                @click="mobileActiveTab = 'panel'"
            >
                {{ mobilePanelTabLabel }}
            </button>
        </div>

        <div class="grid min-h-0 flex-1 items-stretch gap-4" :class="showSidePanel ? 'xl:grid-cols-[minmax(0,1fr)_380px]' : ''">
            <div
                class="map-editor-map relative min-h-0 h-full"
                :class="mapPanelClass"
            >
                <button
                    v-if="interactionMode === 'cable' && canFinishCable"
                    type="button"
                    class="absolute bottom-4 left-1/2 z-[1000] -translate-x-1/2 rounded-full bg-theme-primary px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:opacity-90 disabled:opacity-60 xl:hidden"
                    :disabled="savingCable"
                    @click="finishCable"
                >
                    {{ savingCable ? $t('common.loading') : $t('map.finishCable') }}
                </button>
                <NetworkMap
                    :points="points"
                    :cables="cables"
                    :selected-point-id="selectedPoint?.id"
                    :selected-cable-id="selectedCable?.id"
                    :draft-point="draftPreview"
                    :loading="loading"
                    :draggable="permissions.edit"
                    :can-create="permissions.create"
                    :interaction-mode="interactionMode"
                    :cable-draft="cableDraft"
                    :cable-type="cableType"
                    :fit-on-update="!selectedPoint?.id"
                    :layout-visible="mapLayoutVisible"
                    :signal-trace="signalTraceState"
                    @select-point="handleSelectPoint"
                    @select-cable="handleSelectCable"
                    @add-point="startNewPoint"
                    @cable-start="startCable"
                    @cable-add-point="addCablePoint"
                    @cable-waypoint="addCableWaypoint"
                    @cable-waypoint-move="moveCableWaypoint"
                    @interaction-mode-change="interactionMode = $event"
                    @draft-move="onDraftMove"
                />
            </div>

            <div
                v-if="showSidePanel"
                class="map-editor-panel min-h-0 h-full"
                :class="sidePanelClass"
                @mousedown.stop
                @pointerdown.stop
                @click.stop
            >
                <template v-if="selectedCable">
                    <CableDetailPanel
                        :cable="selectedCable"
                        :meta="meta"
                        :points="points"
                        :cables="cables"
                        :connection-options="coreConnectionOptions"
                        :split-options="cableSplitOptions"
                        :join-candidates="cableJoinCandidates"
                        :route-operating="routeOperating"
                        :route-error="routeError"
                        :can-edit="permissions.edit"
                        :can-delete="permissions.delete"
                        :saving="savingCable"
                        :saving-cable-type="savingCableType"
                        :cable-type-error="cableTypeError"
                        :save-error="cableSaveError"
                        @close="closePanel"
                        @save="saveCable"
                        @delete="deleteCable"
                        @upload-image="uploadCableImage"
                        @delete-image="deleteCableImage"
                        @add-cable-type="addCableType"
                        @remove-cable-type="removeCableType"
                        @split-cable="splitCable"
                        @join-cable="joinCable"
                        @panel-interaction="focusMobilePanel"
                    />
                </template>

                <PointDetailPanel
                    v-else-if="panelOpen"
                    ref="pointPanelRef"
                    :point="selectedPoint"
                    :points="points"
                    :cables="cables"
                    :meta="meta"
                    :areas="areas"
                    :can-edit="canEditSelected"
                    :can-delete="permissions.delete && !!selectedPoint?.id"
                    :saving="saving"
                    @close="closePanel"
                    @save="savePoint"
                    @delete="deletePoint"
                    @upload-image="uploadImage"
                    @delete-image="deleteImage"
                    @draft-change="onDraftChange"
                    @signal-animate="handleSignalAnimate"
                />

                <div v-else-if="interactionMode === 'cable'" class="app-card flex h-full min-h-0 flex-col rounded-2xl border p-6 shadow-sm">
                    <div class="flex flex-1 flex-col items-center justify-center text-center">
                        <Cable class="h-10 w-10 text-theme-primary opacity-80" />
                        <p class="mt-4 max-w-xs text-base font-medium text-theme-body">{{ cableDrawingHint }}</p>
                        <p v-if="cableDraftPointCount > 0" class="mt-2 text-sm text-theme-muted">
                            {{ $t('map.cablePointsSelected', { count: cableDraftPointCount }) }}
                        </p>
                        <p v-if="cableSaveError" class="mt-3 max-w-xs text-sm text-rose-600">{{ cableSaveError }}</p>
                        <button
                            v-if="cableDraft?.nodes?.length"
                            type="button"
                            class="btn-secondary mt-4 rounded-lg border px-4 py-2 text-sm font-semibold transition"
                            @click="undoCableNode"
                        >
                            {{ $t('map.undoLastStep') }}
                        </button>
                    </div>
                    <div class="shrink-0 space-y-2 border-t border-theme pt-4">
                        <button
                            v-if="canFinishCable"
                            type="button"
                            class="btn-primary w-full rounded-lg px-4 py-2 text-sm font-semibold transition disabled:opacity-60"
                            :disabled="savingCable"
                            @click="finishCable"
                        >
                            {{ savingCable ? $t('common.loading') : $t('map.finishCable') }}
                        </button>
                        <button
                            type="button"
                            class="btn-secondary w-full rounded-lg border px-4 py-2 text-sm font-semibold transition"
                            @click="setInteractionMode('view')"
                        >
                            {{ $t('map.cancelCable') }}
                        </button>
                    </div>
                </div>

                <div v-else-if="interactionMode === 'add'" class="app-card flex h-full min-h-0 flex-col rounded-2xl border p-6 shadow-sm">
                    <div class="flex flex-1 flex-col items-center justify-center text-center">
                        <MapPinPlus class="h-10 w-10 text-theme-primary opacity-80" />
                        <p class="mt-4 max-w-xs text-base font-medium text-theme-body">{{ $t('map.selectOrAdd') }}</p>
                        <p class="mt-2 max-w-xs text-sm text-theme-muted">{{ $t('map.clickToPlace') }}</p>
                    </div>
                    <div class="shrink-0 border-t border-theme pt-4">
                        <button
                            type="button"
                            class="btn-secondary w-full rounded-lg border px-4 py-2 text-sm font-semibold transition"
                            @click="setInteractionMode('view')"
                        >
                            {{ $t('map.cancelAdd') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { Cable, MapPinPlus, Search, X } from 'lucide-vue-next';
import { api } from '../api/client';
import { confirmAction } from '../composables/useConfirm';
import { setCableTypeCatalog, normalizePointTypes } from '../utils/networkMap';
import NetworkMap from '../components/network/NetworkMap.vue';
import PointDetailPanel from '../components/network/PointDetailPanel.vue';
import CableDetailPanel from '../components/network/CableDetailPanel.vue';

const props = defineProps({
    session: { type: Object, default: null },
});

const route = useRoute();
const router = useRouter();
const confirm = confirmAction;
const { t } = useI18n();

const loading = ref(true);
const saving = ref(false);
const savingCable = ref(false);
const search = ref('');
const points = ref([]);
const cables = ref([]);
const areas = ref([]);
const meta = ref({ types: {}, statuses: {}, cable_types: {}, cable_type_colors: {}, custom_cable_types: [], cable_statuses: {} });
const permissions = ref(permissionsFromSession(props.session));
const selectedPoint = ref(null);
const selectedCable = ref(null);
const panelOpen = ref(false);
const interactionMode = ref('view');
const cableDraft = ref(null);
const cableType = ref('fiber');
const draftPreview = ref(null);
const savingCableType = ref(false);
const cableTypeError = ref('');
const cableSaveError = ref('');
const coreConnectionOptions = ref([]);
const cableSplitOptions = ref([]);
const cableJoinCandidates = ref([]);
const routeOperating = ref(false);
const routeError = ref('');
const mobileActiveTab = ref('map');
const signalTraceState = ref(null);
const pointPanelRef = ref(null);
const isWideLayout = ref(false);
let wideLayoutMediaQuery = null;
let syncWideLayout = null;

function permissionsFromSession(session) {
    const keys = session?.permissions ?? [];

    return {
        view: keys.includes('network.view'),
        create: keys.includes('network.create'),
        edit: keys.includes('network.edit'),
        delete: keys.includes('network.delete'),
    };
}

function clearPanelState() {
    panelOpen.value = false;
    selectedPoint.value = null;
    selectedCable.value = null;
    draftPreview.value = null;

    if (route.query.point) {
        router.replace({ name: 'map.index' });
    }
}

function closePanel() {
    clearPanelState();
    clearSignalTrace();
    coreConnectionOptions.value = [];
    cableSplitOptions.value = [];
    cableJoinCandidates.value = [];
    routeError.value = '';
    interactionMode.value = 'view';
    focusMobileMap();
}

function setInteractionMode(mode) {
    if (mode === 'view') {
        if (interactionMode.value === 'add') {
            closePanel();

            return;
        }

        interactionMode.value = 'view';
        cableDraft.value = null;
        cableTypeError.value = '';
        focusMobileMap();

        return;
    }

    interactionMode.value = mode;

    if (mode === 'cable' || mode === 'add') {
        clearPanelState();
        cableDraft.value = null;
        cableTypeError.value = '';
        cableSaveError.value = '';
        focusMobileMap();
    }
}

const showSidePanel = computed(() => Boolean(
    selectedCable.value
    || panelOpen.value
    || interactionMode.value === 'cable'
    || interactionMode.value === 'add',
));

const cableDrawingHint = computed(() => {
    const nodes = cableDraft.value?.nodes ?? [];
    const pointCount = nodes.filter((node) => node.type === 'point').length;

    if (pointCount === 0) {
        return t('map.selectStartPoint');
    }

    if (pointCount === 1) {
        return t('map.cableDrawHintMulti');
    }

    return t('map.cableDrawWithPoints', { count: pointCount });
});

watch(selectedCable, (cable) => {
    if (cable) {
        focusMobilePanel();
        clearSignalTrace();
    }
}, { immediate: true });

watch(selectedPoint, () => {
    clearSignalTrace();
});

const cableDraftPointCount = computed(() => (
    cableDraft.value?.nodes?.filter((node) => node.type === 'point').length ?? 0
));

const mapLayoutVisible = computed(() => (
    !showSidePanel.value || isWideLayout.value || mobileActiveTab.value === 'map' || Boolean(selectedCable.value)
));

const mapPanelClass = computed(() => {
    if (selectedCable.value) {
        return 'hidden xl:block';
    }

    return showSidePanel.value && mobileActiveTab.value === 'panel' ? 'hidden xl:block' : '';
});

const sidePanelClass = computed(() => {
    if (selectedCable.value) {
        return '';
    }

    return mobileActiveTab.value === 'map' ? 'hidden xl:block' : '';
});

const canFinishCable = computed(() => cableDraftPointCount.value >= 2);

const mobilePanelTabLabel = computed(() => {
    if (selectedCable.value) {
        return t('map.mobileTabCable');
    }

    if (panelOpen.value && selectedPoint.value) {
        return t('map.mobileTabPoint');
    }

    if (interactionMode.value === 'cable') {
        return t('map.mobileTabDrawCable');
    }

    if (interactionMode.value === 'add') {
        return t('map.mobileTabAddPoint');
    }

    return t('map.mobileTabDetails');
});

function focusMobilePanel() {
    mobileActiveTab.value = 'panel';
}

function focusMobileMap() {
    mobileActiveTab.value = 'map';
}

function handleSignalAnimate(payload) {
    signalTraceState.value = payload;
}

function clearSignalTrace() {
    signalTraceState.value = null;
}

let debounceTimer = null;

const canEditSelected = computed(() => {
    if (!selectedPoint.value?.id) {
        return permissions.value.create;
    }

    return permissions.value.edit;
});

async function loadMeta() {
    meta.value = await api('/api/network/meta');
    setCableTypeCatalog(meta.value.cable_types, meta.value.cable_type_colors);
}

function applyCableTypeCatalog(payload) {
    meta.value = {
        ...meta.value,
        cable_types: payload.cable_types,
        cable_type_colors: payload.cable_type_colors,
        custom_cable_types: payload.custom_cable_types,
    };
    setCableTypeCatalog(payload.cable_types, payload.cable_type_colors);
}

async function addCableType(label) {
    const trimmed = String(label ?? '').trim();

    if (!trimmed) {
        return;
    }

    savingCableType.value = true;
    cableTypeError.value = '';

    try {
        const response = await api('/api/network/cable-types', {
            method: 'POST',
            body: JSON.stringify({ label: trimmed }),
        });

        applyCableTypeCatalog(response);

        if (selectedCable.value) {
            selectedCable.value = {
                ...selectedCable.value,
                cable_type: response.data.key,
            };
        }
    } catch (error) {
        cableTypeError.value = error.errors?.label?.[0] ?? error.message ?? t('map.addCableTypeFailed');
    } finally {
        savingCableType.value = false;
    }
}

async function removeCableType(type) {
    const confirmed = await confirm({
        title: t('map.removeCableTypeTitle'),
        message: t('map.removeCableTypeMessage', { name: type.label }),
        confirmLabel: t('common.delete'),
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await api(`/api/network/cable-types/${type.id}`, { method: 'DELETE' });
        applyCableTypeCatalog(response);

        if (cableType.value === type.key) {
            cableType.value = 'fiber';
        }

        if (selectedCable.value?.cable_type === type.key) {
            selectedCable.value = {
                ...selectedCable.value,
                cable_type: 'fiber',
            };
        }
    } catch (error) {
        cableTypeError.value = error.errors?.label?.[0] ?? error.message ?? t('map.removeCableTypeFailed');
    }
}

async function loadPoints() {
    loading.value = true;

    try {
        const params = new URLSearchParams({ all: '1' });
        if (search.value) {
            params.set('search', search.value);
        }
        const response = await api(`/api/network/points?${params.toString()}`);
        points.value = response.data ?? [];
        areas.value = response.areas ?? [];
        permissions.value = response.permissions ?? permissionsFromSession(props.session);
    } finally {
        loading.value = false;
    }
}

async function loadCableRouteOptions(cableId) {
    if (!cableId) {
        cableSplitOptions.value = [];
        cableJoinCandidates.value = [];

        return;
    }

    try {
        const [splitResponse, joinResponse] = await Promise.all([
            api(`/api/network/cables/${cableId}/split-options`),
            api(`/api/network/cables/${cableId}/join-candidates`),
        ]);
        cableSplitOptions.value = splitResponse.data ?? [];
        cableJoinCandidates.value = joinResponse.data ?? [];
    } catch {
        cableSplitOptions.value = [];
        cableJoinCandidates.value = [];
    }
}

async function loadCoreConnectionOptions(cableId) {
    if (!cableId) {
        coreConnectionOptions.value = [];

        return;
    }

    try {
        const response = await api(`/api/network/cables/${cableId}/core-connections`);
        coreConnectionOptions.value = response.data ?? [];
    } catch {
        coreConnectionOptions.value = [];
    }
}

async function loadCables() {
    const response = await api('/api/network/cables');
    cables.value = response.data ?? [];

    if (selectedCable.value?.id) {
        const refreshed = cables.value.find(
            (item) => String(item.id) === String(selectedCable.value.id),
        );

        if (refreshed) {
            selectedCable.value = refreshed;
        }
    }
}

async function loadAll() {
    await Promise.all([loadMeta(), loadPoints(), loadCables()]);
    openPointFromQuery();
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadPoints, 250);
}

function clearSearch() {
    search.value = '';
    loadPoints();
}

function openPointFromQuery() {
    const pointId = route.query.point;

    if (!pointId) {
        return;
    }

    const point = points.value.find((item) => String(item.id) === String(pointId));

    if (point) {
        selectedPoint.value = point;
        selectedCable.value = null;
        panelOpen.value = true;
        focusMobilePanel();
    }
}

function handleSelectCable(cable) {
    if (interactionMode.value === 'add') {
        return;
    }

    if (interactionMode.value === 'cable') {
        cableDraft.value = null;
        cableSaveError.value = '';
        interactionMode.value = 'view';
    } else if (interactionMode.value !== 'view') {
        return;
    }

    selectedCable.value = cable;
    selectedPoint.value = null;
    draftPreview.value = null;
    panelOpen.value = true;
    loadCoreConnectionOptions(cable.id);
    loadCableRouteOptions(cable.id);
    routeError.value = '';
    focusMobilePanel();
}

function handleSelectPoint(point) {
    if (point._dragged && permissions.value.edit && point.id) {
        savePoint({
            name: point.name,
            types: normalizePointTypes(point),
            status: point.status,
            area: point.area,
            latitude: point.latitude,
            longitude: point.longitude,
            address: point.address,
            notes: point.notes,
            contact_name: point.contact_name,
            contact_phone: point.contact_phone,
            port_count: point.port_count,
        }, point.id);

        return;
    }

    selectedPoint.value = point;
    selectedCable.value = null;
    panelOpen.value = true;
    focusMobilePanel();
}

function hasUnsavedDraft() {
    return Boolean(selectedPoint.value && !selectedPoint.value.id);
}

function repositionDraftPoint({ latitude, longitude }) {
    onDraftMove({ latitude, longitude });
    focusMobileMap();
}

function startNewPoint({ latitude, longitude }) {
    if (hasUnsavedDraft()) {
        repositionDraftPoint({ latitude, longitude });

        return;
    }

    selectedCable.value = null;
    draftPreview.value = {
        name: '',
        types: ['junction'],
        status: 'active',
        area: '',
        latitude,
        longitude,
        address: '',
        notes: '',
        contact_name: '',
        contact_phone: '',
        port_count: null,
    };
    selectedPoint.value = {
        ...draftPreview.value,
        images: [],
    };
    panelOpen.value = true;
    focusMobileMap();
}

function onDraftChange(draft) {
    if (!draftPreview.value) {
        draftPreview.value = { ...draft };

        return;
    }

    Object.assign(draftPreview.value, {
        name: draft.name,
        types: [...draft.types],
        status: draft.status,
        area: draft.area,
        latitude: draft.latitude,
        longitude: draft.longitude,
        address: draft.address,
        notes: draft.notes,
        contact_name: draft.contact_name,
        contact_phone: draft.contact_phone,
        port_count: draft.port_count,
    });
}

function onDraftMove({ latitude, longitude }) {
    pointPanelRef.value?.syncCoordinates(latitude, longitude);

    if (draftPreview.value) {
        draftPreview.value.latitude = latitude;
        draftPreview.value.longitude = longitude;
    }
}

async function savePoint(payload, existingId = null) {
    saving.value = true;

    try {
        const id = existingId ?? selectedPoint.value?.id;

        if (id) {
            const response = await api(`/api/network/points/${id}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            selectedPoint.value = response.data;
        } else {
            const response = await api('/api/network/points', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            draftPreview.value = null;
            interactionMode.value = 'view';
            selectedPoint.value = response.data;
        }

        await Promise.all([loadPoints(), loadCables()]);
        panelOpen.value = true;
    } finally {
        saving.value = false;
    }
}

async function deletePoint() {
    if (!selectedPoint.value?.id) {
        return;
    }

    const confirmed = await confirm({
        title: 'Delete point',
        message: 'Delete this network point and connected references?',
        confirmLabel: 'Delete',
    });

    if (!confirmed) {
        return;
    }

    await api(`/api/network/points/${selectedPoint.value.id}`, { method: 'DELETE' });
    closePanel();
    await Promise.all([loadPoints(), loadCables()]);
}

async function createCable(payload) {
    savingCable.value = true;
    cableSaveError.value = '';

    try {
        const response = await api('/api/network/cables', {
            method: 'POST',
            body: JSON.stringify({
                ...payload,
                status: 'active',
            }),
        });

        interactionMode.value = 'view';
        cableDraft.value = null;
        selectedCable.value = response.data;
        selectedPoint.value = null;
        panelOpen.value = true;
        await loadCoreConnectionOptions(response.data.id);
        focusMobilePanel();
        await loadCables();
    } catch (error) {
        cableSaveError.value = error.errors?.route?.[0]
            ?? error.errors?.cable_type?.[0]
            ?? error.message
            ?? t('map.saveCableFailed');
        focusMobilePanel();
        throw error;
    } finally {
        savingCable.value = false;
    }
}

async function saveCable(payload) {
    if (!selectedCable.value?.id) {
        return;
    }

    savingCable.value = true;
    cableSaveError.value = '';

    try {
        const response = await api(`/api/network/cables/${selectedCable.value.id}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });
        selectedCable.value = response.data;
        await Promise.all([loadCables(), loadCoreConnectionOptions(selectedCable.value.id)]);
    } catch (error) {
        cableSaveError.value = error.errors?.route?.[0]
            ?? error.errors?.cores?.[0]
            ?? error.errors?.core_count?.[0]
            ?? error.message
            ?? t('map.saveCableFailed');
        focusMobilePanel();
        throw error;
    } finally {
        savingCable.value = false;
    }
}

async function splitCable(splitPointId) {
    if (!selectedCable.value?.id || routeOperating.value) {
        return;
    }

    const pointLabel = cableSplitOptions.value.find((item) => Number(item.point_id) === Number(splitPointId))?.name
        ?? `#${splitPointId}`;

    const confirmed = await confirm({
        title: t('cables.splitConfirmTitle'),
        message: t('cables.splitConfirmMessage', { point: pointLabel }),
        confirmLabel: t('cables.splitCableAction'),
    });

    if (!confirmed) {
        return;
    }

    routeOperating.value = true;
    routeError.value = '';

    try {
        const response = await api(`/api/network/cables/${selectedCable.value.id}/split`, {
            method: 'POST',
            body: JSON.stringify({ split_point_id: splitPointId }),
        });
        selectedCable.value = response.data.first;
        await Promise.all([
            loadCables(),
            loadCoreConnectionOptions(selectedCable.value.id),
            loadCableRouteOptions(selectedCable.value.id),
        ]);
    } catch (error) {
        routeError.value = error.message ?? t('cables.splitFailed');
    } finally {
        routeOperating.value = false;
    }
}

async function joinCable(otherCableId) {
    if (!selectedCable.value?.id || routeOperating.value) {
        return;
    }

    const candidate = cableJoinCandidates.value.find((item) => Number(item.id) === Number(otherCableId));
    const joinLabel = candidate?.label ?? `Cable #${otherCableId}`;

    const confirmed = await confirm({
        title: t('cables.joinConfirmTitle'),
        message: t('cables.joinConfirmMessage', { cable: joinLabel }),
        confirmLabel: t('cables.joinCableAction'),
    });

    if (!confirmed) {
        return;
    }

    routeOperating.value = true;
    routeError.value = '';

    try {
        const response = await api(`/api/network/cables/${selectedCable.value.id}/join`, {
            method: 'POST',
            body: JSON.stringify({ other_cable_id: otherCableId }),
        });
        selectedCable.value = response.data;
        await Promise.all([
            loadCables(),
            loadCoreConnectionOptions(selectedCable.value.id),
            loadCableRouteOptions(selectedCable.value.id),
        ]);
    } catch (error) {
        routeError.value = error.message ?? t('cables.joinFailed');
    } finally {
        routeOperating.value = false;
    }
}

async function deleteCable() {
    if (!selectedCable.value?.id) {
        return;
    }

    const confirmed = await confirm({
        title: t('cables.deleteTitle'),
        message: t('cables.deleteMessage'),
        confirmLabel: t('common.delete'),
    });

    if (!confirmed) {
        return;
    }

    await api(`/api/network/cables/${selectedCable.value.id}`, { method: 'DELETE' });
    closePanel();
    await loadCables();
}

async function uploadCableImage(file) {
    if (!selectedCable.value?.id) {
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch(`/api/network/cables/${selectedCable.value.id}/images`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            Accept: 'application/json',
        },
        body: formData,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Upload failed');
    }

    const json = await response.json();
    selectedCable.value = {
        ...selectedCable.value,
        images: [...(selectedCable.value.images ?? []), json.data],
    };

    await loadCables();
}

async function deleteCableImage(image) {
    if (!selectedCable.value?.id) {
        return;
    }

    await api(`/api/network/cables/${selectedCable.value.id}/images/${image.id}`, { method: 'DELETE' });

    selectedCable.value = {
        ...selectedCable.value,
        images: (selectedCable.value.images ?? []).filter((item) => item.id !== image.id),
    };

    await loadCables();
}

function startCable(fromPointId) {
    cableDraft.value = {
        nodes: [{ type: 'point', point_id: fromPointId }],
    };
}

function addCablePoint(pointId) {
    if (!cableDraft.value?.nodes?.length) {
        startCable(pointId);

        return;
    }

    cableDraft.value = {
        ...cableDraft.value,
        nodes: [...cableDraft.value.nodes, { type: 'point', point_id: pointId }],
    };
}

function addCableWaypoint({ latitude, longitude }) {
    if (!cableDraft.value?.nodes?.length) {
        return;
    }

    cableDraft.value = {
        ...cableDraft.value,
        nodes: [...cableDraft.value.nodes, { type: 'bend', lat: latitude, lng: longitude }],
    };
}

function moveCableWaypoint({ index, latitude, longitude }) {
    if (!cableDraft.value?.nodes?.[index] || cableDraft.value.nodes[index].type !== 'bend') {
        return;
    }

    const nodes = [...cableDraft.value.nodes];
    nodes[index] = { type: 'bend', lat: latitude, lng: longitude };

    cableDraft.value = {
        ...cableDraft.value,
        nodes,
    };
}

function undoCableNode() {
    if (!cableDraft.value?.nodes?.length) {
        return;
    }

    cableDraft.value = {
        ...cableDraft.value,
        nodes: cableDraft.value.nodes.slice(0, -1),
    };
}

async function finishCable() {
    const nodes = cableDraft.value?.nodes ?? [];
    const pointCount = nodes.filter((node) => node.type === 'point').length;

    if (pointCount < 2 || savingCable.value) {
        return;
    }

    cableSaveError.value = '';

    try {
        await createCable({
            route: nodes,
            cable_type: cableType.value,
        });
    } catch {
        // Error message shown via cableSaveError.
    }
}

async function uploadImage(file) {
    if (!selectedPoint.value?.id) {
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch(`/api/network/points/${selectedPoint.value.id}/images`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            Accept: 'application/json',
        },
        body: formData,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Upload failed');
    }

    const json = await response.json();
    selectedPoint.value = {
        ...selectedPoint.value,
        images: [...(selectedPoint.value.images ?? []), json.data],
    };

    await loadPoints();
}

async function deleteImage(image) {
    if (!selectedPoint.value?.id) {
        return;
    }

    await api(`/api/network/points/${selectedPoint.value.id}/images/${image.id}`, { method: 'DELETE' });

    selectedPoint.value = {
        ...selectedPoint.value,
        images: (selectedPoint.value.images ?? []).filter((item) => item.id !== image.id),
    };

    await loadPoints();
}

watch(() => props.session, (session) => {
    permissions.value = permissionsFromSession(session);
}, { deep: true });

watch(() => route.query.point, openPointFromQuery);

onMounted(() => {
    wideLayoutMediaQuery = window.matchMedia('(min-width: 1280px)');
    syncWideLayout = () => {
        isWideLayout.value = wideLayoutMediaQuery.matches;
    };

    syncWideLayout();
    wideLayoutMediaQuery.addEventListener('change', syncWideLayout);
    loadAll();
});

onBeforeUnmount(() => {
    if (wideLayoutMediaQuery && syncWideLayout) {
        wideLayoutMediaQuery.removeEventListener('change', syncWideLayout);
    }
});
</script>
