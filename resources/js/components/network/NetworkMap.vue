<template>
    <div class="network-map-shell relative isolate z-0 h-full overflow-hidden rounded-2xl border border-theme shadow-sm">
        <div ref="mapEl" class="network-map h-full w-full" />

        <div
            v-if="loading"
            class="absolute inset-0 z-20 flex items-center justify-center bg-theme-card/80 backdrop-blur-sm"
        >
            <LoaderCircle class="h-6 w-6 animate-spin text-theme-muted" />
        </div>

        <div
            v-if="selectedPointId && draggable && interactionMode === 'view'"
            class="pointer-events-none absolute bottom-3 left-1/2 z-10 -translate-x-1/2 rounded-full bg-theme-card/95 px-4 py-2 text-xs font-medium text-theme-body shadow-lg backdrop-blur"
        >
            {{ $t('map.dragHint') }}
        </div>

        <div
            v-if="interactionMode === 'add' && draftPoint"
            class="pointer-events-none absolute bottom-3 left-1/2 z-10 max-w-[90%] -translate-x-1/2 rounded-full bg-theme-card/95 px-4 py-2 text-center text-xs font-medium text-theme-body shadow-lg backdrop-blur sm:text-sm"
        >
            {{ $t('map.repositionPointHint') }}
        </div>

        <div
            v-else-if="interactionMode === 'cable' && cableDraft?.nodes?.length"
            class="pointer-events-none absolute bottom-3 left-1/2 z-10 max-w-[90%] -translate-x-1/2 rounded-full bg-theme-card/95 px-4 py-2 text-center text-xs font-medium text-theme-body shadow-lg backdrop-blur sm:text-sm"
        >
            {{ $t('map.addBendHint') }}
        </div>

        <div
            class="absolute left-3 top-3 z-10 flex rounded-lg border border-theme bg-theme-card p-1 shadow-sm"
            role="group"
            :aria-label="$t('map.baseMapLabel')"
        >
            <button
                type="button"
                class="rounded-md px-2.5 py-1.5 text-xs font-medium transition"
                :class="baseMapStyle === 'street'
                    ? 'bg-theme-primary text-white shadow-sm'
                    : 'text-theme-body hover:bg-theme-muted/40'"
                :aria-pressed="baseMapStyle === 'street'"
                @click="setBaseMapStyle('street')"
            >
                {{ $t('map.baseMapStreet') }}
            </button>
            <button
                type="button"
                class="rounded-md px-2.5 py-1.5 text-xs font-medium transition"
                :class="baseMapStyle === 'satellite'
                    ? 'bg-theme-primary text-white shadow-sm'
                    : 'text-theme-body hover:bg-theme-muted/40'"
                :aria-pressed="baseMapStyle === 'satellite'"
                @click="setBaseMapStyle('satellite')"
            >
                {{ $t('map.baseMapSatellite') }}
            </button>
        </div>

        <div v-if="enableLiveLocation" class="absolute right-3 top-3 z-10 flex flex-col items-end gap-2">
            <button
                type="button"
                class="btn-secondary inline-flex items-center justify-center rounded-lg border bg-theme-card p-2.5 shadow-sm transition"
                :class="liveLocationActive ? 'ring-2 ring-theme-primary ring-offset-2' : ''"
                :aria-pressed="liveLocationActive"
                :title="liveLocationActive ? $t('map.stopMyLocation') : $t('map.myLocation')"
                @click="toggleLiveLocation"
            >
                <LocateFixed class="h-4 w-4" :class="liveLocationActive ? 'text-theme-primary' : 'text-theme-body'" />
            </button>
            <p
                v-if="locationError"
                class="max-w-[12rem] rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-right text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200"
            >
                {{ locationError }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { LoaderCircle, LocateFixed } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import {
    buildMarkerHtml,
    buildSignalPathCoordinates,
    cablePathCoordinates,
    cableTypeColor,
    cableTypeLabel,
    cableMapDistanceM,
    cableRoutePointIds,
    buildCableRenderChunks,
    buildEdgeGroups,
    buildSharedEdgeBadges,
    cableSharedEdgeMeta,
    cableCoreFlows,
    collectPointFlowPorts,
    colocatedPointsCount,
    findPointsAtLocation,
    formatDistanceM,
    DEFAULT_CENTER,
    DEFAULT_ZOOM,
    normalizePointTypes,
    offsetLatLng,
    offsetPolylineCoordinates,
    pointColor,
    pointTypeLabel,
    pointTypeLabels,
    positionAlongPath,
    routeCoordinatesFromNodes,
    primaryPointType,
} from '../../utils/networkMap';

const props = defineProps({
    points: { type: Array, default: () => [] },
    cables: { type: Array, default: () => [] },
    selectedPointId: { type: [Number, String, null], default: null },
    selectedCableId: { type: [Number, String, null], default: null },
    loading: { type: Boolean, default: false },
    draggable: { type: Boolean, default: false },
    canCreate: { type: Boolean, default: false },
    fitOnUpdate: { type: Boolean, default: true },
    interactionMode: { type: String, default: 'view' },
    draftPoint: { type: Object, default: null },
    cableDraft: { type: Object, default: null },
    cableType: { type: String, default: 'fiber' },
    enableLiveLocation: { type: Boolean, default: true },
    layoutVisible: { type: Boolean, default: true },
    signalTrace: { type: Object, default: null },
});

const emit = defineEmits([
    'select-point',
    'select-cable',
    'add-point',
    'cable-start',
    'cable-add-point',
    'cable-waypoint',
    'cable-waypoint-move',
    'interaction-mode-change',
    'draft-move',
]);

const mapEl = ref(null);
const draggingPointId = ref(null);
const hasInitialFit = ref(false);
const liveLocationActive = ref(false);
const locationError = ref('');
const hasCenteredOnUser = ref(false);
const baseMapStyle = ref('street');

const { t } = useI18n();

let map = null;
let osmLayer = null;
let satelliteLayer = null;
let resizeHandler = null;
let markersLayer = null;
let cablesLayer = null;
let flowLayer = null;
let cableDraftLayer = null;
let highlightLayer = null;
let signalTraceLayer = null;
let previewLayer = null;
let draftMarker = null;
let draftCircle = null;
let isDraggingDraft = false;
let lastDraftVisualKey = '';
const markerRegistry = new Map();
const draftWaypointRegistry = new Map();

const pointsById = computed(() => {
    const mapById = {};

    props.points.forEach((point) => {
        mapById[point.id] = point;
    });

    return mapById;
});

const selectedPoint = computed(() => {
    if (!props.selectedPointId) {
        return null;
    }

    return props.points.find((point) => String(point.id) === String(props.selectedPointId)) ?? null;
});

function markerIcon(point, options = {}) {
    const selected = options.preview || String(point.id) === String(props.selectedPointId);
    const size = options.preview ? 52 : (selected ? 48 : 32);
    const draftPointIds = (props.cableDraft?.nodes ?? [])
        .filter((node) => node.type === 'point')
        .map((node) => Number(node.point_id));
    const lastDraftPointId = draftPointIds[draftPointIds.length - 1];
    const isCableStart = draftPointIds.length === 1 && String(point.id) === String(lastDraftPointId);
    const isCableNextPoint = draftPointIds.length >= 1
        && String(point.id) !== String(lastDraftPointId);

    return L.divIcon({
        className: 'network-point-marker-wrap',
        html: buildMarkerHtml(point, {
            selected,
            preview: options.preview,
            dragging: options.dragging,
            cableTarget: isCableStart || isCableNextPoint,
            stackCount: colocatedPointsCount(props.points, point),
        }),
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
    });
}

function openColocatedPointPicker(marker, clickedPoint) {
    const colocated = findPointsAtLocation(props.points, clickedPoint.latitude, clickedPoint.longitude)
        .sort((left, right) => String(left.name ?? '').localeCompare(String(right.name ?? '')));

    const container = document.createElement('div');
    container.className = 'network-popup network-popup--stack';

    const title = document.createElement('strong');
    title.textContent = t('map.colocatedPointsTitle', { count: colocated.length });
    container.appendChild(title);

    const list = document.createElement('ul');
    list.className = 'network-popup__stack-list';

    colocated.forEach((point) => {
        const item = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'network-popup__stack-btn';
        button.textContent = `${point.name ?? `Point #${point.id}`} · ${pointTypeLabels(point).join(', ')}`;
        button.addEventListener('click', () => {
            marker.closePopup();
            emit('select-point', point);
        });
        item.appendChild(button);
        list.appendChild(item);
    });

    container.appendChild(list);

    marker.bindPopup(container, { className: 'network-point-popup network-point-popup--stack' }).openPopup();
}

function waypointIcon() {
    const color = cableTypeColor(props.cableType);

    return L.divIcon({
        className: 'network-cable-waypoint-wrap',
        html: `<span class="network-cable-waypoint" style="--waypoint-color:${color}"></span>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    });
}

function updateMarkerAppearance(pointId, dragging = false) {
    const entry = markerRegistry.get(pointId);

    if (!entry) {
        return;
    }

    entry.marker.setIcon(markerIcon(entry.point, { dragging }));
    entry.marker.setZIndexOffset(String(pointId) === String(props.selectedPointId) ? 1000 : 0);
}

function renderHighlight() {
    if (!highlightLayer) {
        return;
    }

    highlightLayer.clearLayers();

    const point = selectedPoint.value;

    if (!point || props.interactionMode !== 'view' || props.draftPoint) {
        return;
    }

    const latlng = [point.latitude, point.longitude];
    const color = pointColor(primaryPointType(point));

    highlightLayer.addLayer(L.circle(latlng, {
        radius: 55,
        color,
        weight: 2,
        opacity: 0.55,
        fillColor: color,
        fillOpacity: 0.12,
        className: 'network-highlight-circle',
    }));

    highlightLayer.addLayer(L.circle(latlng, {
        radius: 85,
        color: '#3b82f6',
        weight: 2,
        opacity: 0.35,
        dashArray: '8 8',
        fillOpacity: 0,
        className: 'network-highlight-ring',
    }));
}

function signalArrowIcon(bearing = 0, opacity = 1) {
    return L.divIcon({
        className: 'network-signal-arrow-wrap',
        html: `<span class="network-signal-arrow-shell" style="transform: rotate(${bearing}deg); opacity:${opacity}" aria-hidden="true"><span class="network-signal-arrow"></span></span>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
    });
}

function pathScreenBearing(coords, progress) {
    if (!map || coords.length < 2) {
        return 0;
    }

    const epsilon = 0.003;
    const forwardProgress = Math.min(1, progress + epsilon);
    const backwardProgress = Math.max(0, progress - epsilon);
    const useForward = forwardProgress > progress;
    const current = positionAlongPath(coords, progress);
    const adjacent = positionAlongPath(coords, useForward ? forwardProgress : backwardProgress);

    if (!current || !adjacent) {
        return 0;
    }

    const from = useForward ? current : adjacent;
    const to = useForward ? adjacent : current;
    const fromPx = map.latLngToContainerPoint(L.latLng(from.position[0], from.position[1]));
    const toPx = map.latLngToContainerPoint(L.latLng(to.position[0], to.position[1]));

    if (fromPx.distanceTo(toPx) < 0.5) {
        return 0;
    }

    return Math.atan2(toPx.y - fromPx.y, toPx.x - fromPx.x) * (180 / Math.PI);
}

function addSignalFlowArrows(pathCoords, pathProgress) {
    const SIGNAL_ARROW_COUNT = 8;

    for (let arrowIndex = 0; arrowIndex < SIGNAL_ARROW_COUNT; arrowIndex += 1) {
        const arrowProgress = (pathProgress - (arrowIndex / SIGNAL_ARROW_COUNT) + 1) % 1;
        const placement = positionAlongPath(pathCoords, arrowProgress);

        if (!placement) {
            continue;
        }

        const bearing = pathScreenBearing(pathCoords, arrowProgress);
        const opacity = Math.max(0.22, 1 - (arrowIndex / (SIGNAL_ARROW_COUNT - 1 || 1)) * 0.78);

        signalTraceLayer.addLayer(L.marker(placement.position, {
            icon: signalArrowIcon(bearing, opacity),
            interactive: false,
            zIndexOffset: 2600 + (SIGNAL_ARROW_COUNT - arrowIndex),
        }));
    }
}

function handleMapViewChange() {
    if (props.signalTrace?.active) {
        renderSignalTrace();
    }
}

function renderSignalTrace() {
    if (!signalTraceLayer) {
        return;
    }

    signalTraceLayer.clearLayers();

    const trace = props.signalTrace;

    if (!trace?.active || !trace?.flowHops?.length) {
        return;
    }

    const activeIndex = trace.activeHopIndex ?? 0;
    const pathProgress = trace.pathProgress ?? 0;
    const pathCoords = buildSignalPathCoordinates(trace.flowHops, props.cables, props.points);

    if (pathCoords.length >= 2) {
        signalTraceLayer.addLayer(L.polyline(pathCoords, {
            color: '#10b981',
            weight: 5,
            opacity: 0.35,
            interactive: false,
            className: 'network-signal-trace-line',
        }));

        const traveledCoords = [];
        const placement = positionAlongPath(pathCoords, pathProgress);

        if (placement) {
            const targetLength = pathProgress * pathCoords.reduce((total, coord, index) => {
                if (index === 0) {
                    return total;
                }

                const previous = pathCoords[index - 1];

                return total + Math.hypot(coord[0] - previous[0], coord[1] - previous[1]);
            }, 0);

            let accumulated = 0;

            for (let index = 0; index < pathCoords.length; index += 1) {
                traveledCoords.push(pathCoords[index]);

                if (index === pathCoords.length - 1) {
                    break;
                }

                const segmentLength = Math.hypot(
                    pathCoords[index + 1][0] - pathCoords[index][0],
                    pathCoords[index + 1][1] - pathCoords[index][1],
                );

                if (accumulated + segmentLength >= targetLength) {
                    traveledCoords.push(placement.position);
                    break;
                }

                accumulated += segmentLength;
            }

            if (traveledCoords.length >= 2) {
                signalTraceLayer.addLayer(L.polyline(traveledCoords, {
                    color: '#059669',
                    weight: 7,
                    opacity: 0.95,
                    interactive: false,
                    className: 'network-signal-trace-line network-signal-trace-line--active',
                }));
            }

            addSignalFlowArrows(pathCoords, pathProgress);
        }
    }

    trace.flowHops.forEach((hop, index) => {
        if (hop.kind !== 'point') {
            return;
        }

        const point = pointsById.value[hop.pointId];

        if (!point) {
            return;
        }

        const isActive = index === activeIndex;
        const isPassed = index < activeIndex;

        signalTraceLayer.addLayer(L.circle([point.latitude, point.longitude], {
            radius: isActive ? 48 : 42,
            color: '#059669',
            weight: isActive ? 3 : 2,
            fillColor: '#10b981',
            fillOpacity: isActive ? 0.28 : (isPassed ? 0.2 : 0.14),
            interactive: false,
            className: [
                'network-signal-trace-point',
                isActive ? 'network-signal-trace-point--active' : '',
            ].filter(Boolean).join(' '),
        }));
    });

    const activeHop = trace.flowHops[activeIndex];

    if (!activeHop || pathCoords.length >= 2) {
        return;
    }

    if (activeHop.kind === 'cable') {
        const cable = props.cables.find((item) => String(item.id) === String(activeHop.cableId));

        if (!cable) {
            return;
        }

        const coords = cablePathCoordinates(cable, pointsById.value);

        if (coords.length < 2) {
            return;
        }

        signalTraceLayer.addLayer(L.polyline(coords, {
            color: '#059669',
            weight: 8,
            opacity: 0.95,
            interactive: false,
            className: 'network-signal-trace-line network-signal-trace-line--active',
        }));

        const endCoord = coords[coords.length - 1];

        if (endCoord) {
            addSignalFlowArrows(coords, pathProgress);
        }

        return;
    }

    if (activeHop.kind === 'point') {
        const point = pointsById.value[activeHop.pointId];

        if (!point) {
            return;
        }

        signalTraceLayer.addLayer(L.circle([point.latitude, point.longitude], {
            radius: 42,
            color: '#059669',
            weight: 3,
            fillColor: '#10b981',
            fillOpacity: 0.22,
            interactive: false,
            className: 'network-signal-trace-point',
        }));
    }
}

function draftVisualKey(draft) {
    if (!draft?.latitude || !draft?.longitude) {
        return '';
    }

    const types = normalizePointTypes(draft).join(',');

    return `${draft.latitude}:${draft.longitude}:${types}`;
}

function repositionDraftCircle(latlng, draft) {
    if (!previewLayer || !draft) {
        return;
    }

    const color = pointColor(primaryPointType(draft));

    if (draftCircle) {
        previewLayer.removeLayer(draftCircle);
        draftCircle = null;
    }

    draftCircle = L.circle(latlng, {
        radius: 60,
        color,
        weight: 2,
        opacity: 0.65,
        dashArray: '6 6',
        fillColor: color,
        fillOpacity: 0.14,
    });

    previewLayer.addLayer(draftCircle);

    if (draftMarker) {
        previewLayer.removeLayer(draftMarker);
        previewLayer.addLayer(draftMarker);
        draftMarker.setZIndexOffset(2000);
    }
}

function attachDraftMarkerHandlers() {
    if (!draftMarker) {
        return;
    }

    draftMarker.off('dragstart');
    draftMarker.off('drag');
    draftMarker.off('dragend');

    draftMarker.on('dragstart', () => {
        isDraggingDraft = true;
    });

    draftMarker.on('drag', () => {
        if (!draftMarker) {
            return;
        }

        repositionDraftCircle(draftMarker.getLatLng(), props.draftPoint);
    });

    draftMarker.on('dragend', () => {
        isDraggingDraft = false;

        if (!draftMarker) {
            return;
        }

        const pos = draftMarker.getLatLng();
        repositionDraftCircle(pos, props.draftPoint);
        emit('draft-move', { latitude: pos.lat, longitude: pos.lng });
    });
}

function syncDraftMarker() {
    if (!previewLayer || !map) {
        return;
    }

    const draft = props.draftPoint;

    if (!draft?.latitude || !draft?.longitude) {
        clearDraftMarker();

        return;
    }

    if (isDraggingDraft) {
        return;
    }

    const latlng = L.latLng(Number(draft.latitude), Number(draft.longitude));
    const visualKey = draftVisualKey(draft);

    if (draftMarker && visualKey === lastDraftVisualKey) {
        return;
    }

    const color = pointColor(primaryPointType(draft));
    const isNewDraft = !draftMarker;

    if (draftMarker) {
        draftMarker.setLatLng(latlng);
        draftMarker.setIcon(markerIcon(draft, { preview: true }));
    } else {
        draftMarker = L.marker(latlng, {
            icon: markerIcon(draft, { preview: true }),
            draggable: true,
            autoPan: true,
            zIndexOffset: 2000,
        });

        attachDraftMarkerHandlers();
        previewLayer.addLayer(draftMarker);
    }

    repositionDraftCircle(latlng, draft);
    lastDraftVisualKey = visualKey;

    if (isNewDraft) {
        map.flyTo(latlng, Math.max(map.getZoom(), 16), { animate: true, duration: 0.55 });
    }
}

function clearDraftMarker() {
    if (previewLayer) {
        previewLayer.clearLayers();
    }

    draftMarker = null;
    draftCircle = null;
    isDraggingDraft = false;
    lastDraftVisualKey = '';
}

function focusSelectedPoint() {
    const point = selectedPoint.value;

    if (!map || !point) {
        return;
    }

    renderHighlight();

    map.flyTo([point.latitude, point.longitude], Math.max(map.getZoom(), 15), {
        animate: true,
        duration: 0.6,
    });
}

function attachMarker(point) {
    const isSelected = String(point.id) === String(props.selectedPointId);

    const marker = L.marker([point.latitude, point.longitude], {
        icon: markerIcon(point),
        draggable: props.draggable && props.interactionMode === 'view',
        autoPan: true,
        riseOnHover: true,
        zIndexOffset: isSelected ? 1000 : 0,
    });

    marker.bindPopup(`
        <div class="network-popup">
            <strong>${escapeHtml(point.name)}</strong>
            <div class="network-popup__meta">${escapeHtml(pointTypeLabels(point).join(' · '))} · ${escapeHtml(point.status)}</div>
            ${point.area ? `<div class="network-popup__area">${escapeHtml(point.area)}</div>` : ''}
            ${props.draggable ? `<div class="network-popup__hint">${escapeHtml('Drag marker to move')}</div>` : ''}
        </div>
    `, { className: 'network-point-popup' });

    marker.on('click', () => {
        if (props.interactionMode === 'cable') {
            handleCableClick(point);

            return;
        }

        if (props.interactionMode === 'add') {
            return;
        }

        const colocated = findPointsAtLocation(props.points, point.latitude, point.longitude);

        if (colocated.length > 1) {
            openColocatedPointPicker(marker, point);

            return;
        }

        emit('select-point', point);
    });

    if (props.draggable) {
        marker.on('dragstart', () => {
            draggingPointId.value = point.id;
            updateMarkerAppearance(point.id, true);
            marker.closePopup();
        });

        marker.on('drag', () => {
            if (String(point.id) === String(props.selectedPointId)) {
                const { lat, lng } = marker.getLatLng();
                renderHighlightAt(lat, lng, primaryPointType(point));
            }
        });

        marker.on('dragend', () => {
            draggingPointId.value = null;
            updateMarkerAppearance(point.id, false);
            const { lat, lng } = marker.getLatLng();
            emit('select-point', { ...point, latitude: lat, longitude: lng, _dragged: true });
        });
    }

    markersLayer.addLayer(marker);
    markerRegistry.set(point.id, { marker, point });
}

function renderHighlightAt(lat, lng, type) {
    if (!highlightLayer) {
        return;
    }

    highlightLayer.clearLayers();
    const color = pointColor(type);

    highlightLayer.addLayer(L.circle([lat, lng], {
        radius: 55,
        color,
        weight: 2,
        opacity: 0.55,
        fillColor: color,
        fillOpacity: 0.12,
    }));
}

function syncMarkers() {
    if (!markersLayer) {
        return;
    }

    const nextIds = new Set(props.points.map((point) => point.id));

    markerRegistry.forEach((entry, id) => {
        if (!nextIds.has(id)) {
            markersLayer.removeLayer(entry.marker);
            markerRegistry.delete(id);
        }
    });

    props.points.forEach((point) => {
        const existing = markerRegistry.get(point.id);

        if (!existing) {
            attachMarker(point);

            return;
        }

        existing.point = point;
        const latlng = L.latLng(point.latitude, point.longitude);

        if (!existing.marker.getLatLng().equals(latlng) && draggingPointId.value !== point.id) {
            existing.marker.setLatLng(latlng);
        }

        existing.marker.setIcon(markerIcon(point, { dragging: draggingPointId.value === point.id }));
        existing.marker.dragging?.[props.draggable && props.interactionMode === 'view' ? 'enable' : 'disable']();
        existing.marker.setZIndexOffset(String(point.id) === String(props.selectedPointId) ? 1000 : 0);
    });

    renderHighlight();
}

function cableBundleBadgeIcon(count) {
    return L.divIcon({
        className: 'network-cable-bundle-wrap',
        html: `<span class="network-cable-bundle-badge" title="${count} cables">${count}</span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
    });
}

function buildCablePopupHtml(cable, {
    mapDistanceLabel,
    actualLengthLabel,
    routePointIds,
    groupIndex,
    groupSize,
}) {
    const bundleLine = groupSize > 1
        ? `<br><span class="network-popup__bundle">${escapeHtml(t('map.cableInBundle', { current: groupIndex + 1, total: groupSize }))}</span>`
        : '';

    return `
        <strong>${escapeHtml(cable.name || 'Cable')}</strong><br>
        ${escapeHtml(cableTypeLabel(cable.cable_type))} · ${escapeHtml(cable.status)}<br>
        ${escapeHtml('Map')}: ${escapeHtml(mapDistanceLabel)} · ${escapeHtml('Actual')}: ${escapeHtml(actualLengthLabel)}
        ${routePointIds.length > 2 ? `<br>${routePointIds.length} points` : ''}
        ${Array.isArray(cable.path) && cable.path.length ? `<br>${cable.path.length} bend${cable.path.length === 1 ? '' : 's'}` : ''}
        ${buildCableFlowPopupLine(cable)}
        ${bundleLine}
    `;
}

function buildCableFlowPopupLine(cable) {
    const flows = cableCoreFlows(cable);

    if (!flows.length) {
        return '';
    }

    const summary = flows
        .slice(0, 3)
        .map((flow) => `#${flow.coreNumber} ${flow.direction === 'forward' ? '→' : '←'}`)
        .join(', ');

    const suffix = flows.length > 3 ? ` +${flows.length - 3}` : '';

    return `<br><span class="network-popup__flow">${escapeHtml(t('map.signalFlow'))}: ${escapeHtml(summary)}${suffix}</span>`;
}

function flowPortIcon(port) {
    const direction = port.direction === 'input' ? 'in' : 'out';
    const label = port.direction === 'input' ? 'IN' : 'OUT';
    const parts = [label];

    if (port.label) {
        parts.push(port.label);
    }

    if (port.cableName) {
        parts.push(`${port.cableName} · ${port.coreCount ?? '?'}C · Core ${port.coreNumber} · ${port.cableSide} end`);
    }

    if (port.peerLabel && port.peerLabel !== '—') {
        parts.push(`→ ${port.peerLabel}`);
    }

    const title = parts.join(' · ');

    return L.divIcon({
        className: 'network-flow-port-wrap',
        html: `<span class="network-flow-port network-flow-port--${direction}" style="--port-color:${port.color}" title="${escapeHtml(title)}">${label}</span>`,
        iconSize: [30, 18],
        iconAnchor: [15, 9],
    });
}

function renderCableFlows(edgeGroups) {
    if (!flowLayer) {
        return;
    }

    flowLayer.clearLayers();

    if (props.interactionMode !== 'view') {
        return;
    }

    const portsByPoint = collectPointFlowPorts(props.cables);
    const renderedPortKeys = new Set();

    props.cables.forEach((cable) => {
        const flows = cableCoreFlows(cable);

        if (!flows.length) {
            return;
        }

        const baseCoords = cablePathCoordinates(cable, pointsById.value);

        if (baseCoords.length < 2) {
            return;
        }

        const chunks = buildCableRenderChunks(cable, pointsById.value, edgeGroups);
        const isSelected = String(cable.id) === String(props.selectedCableId);
        const routePointIds = cableRoutePointIds(cable);
        const isConnectedToSelectedPoint = selectedPoint.value
            && routePointIds.includes(Number(selectedPoint.value.id));
        const emphasize = isSelected || isConnectedToSelectedPoint;

        flows.forEach((flow, flowIndex) => {
            const flowOffsetMeters = (flowIndex - ((flows.length - 1) / 2)) * 1.4;

            chunks.forEach((chunk) => {
                if (chunk.coords.length < 2) {
                    return;
                }

                let coords = chunk.coords;

                if (flow.direction === 'reverse') {
                    coords = [...chunk.coords].reverse();
                }

                const totalOffset = chunk.offsetMeters + flowOffsetMeters;
                const flowCoords = totalOffset === 0
                    ? coords
                    : offsetPolylineCoordinates(coords, totalOffset);

                flowLayer.addLayer(L.polyline(flowCoords, {
                    color: flow.color,
                    weight: emphasize ? 5 : 3,
                    opacity: emphasize ? 0.95 : 0.72,
                    lineCap: 'round',
                    className: [
                        'network-cable-flow',
                        `network-cable-flow--${flow.direction}`,
                        emphasize ? 'network-cable-flow--emphasized' : '',
                    ].filter(Boolean).join(' '),
                    interactive: false,
                }));
            });
        });
    });

    portsByPoint.forEach((ports, pointId) => {
        const point = pointsById.value[pointId];

        if (!point) {
            return;
        }

        ports.forEach((port, index) => {
            const portKey = `${pointId}:${port.cableId}:${port.coreNumber}:${port.direction}`;

            if (renderedPortKeys.has(portKey)) {
                return;
            }

            renderedPortKeys.add(portKey);

            const bearing = 135 + (index * 36);
            const [lat, lng] = offsetLatLng(point.latitude, point.longitude, bearing, 16);

            flowLayer.addLayer(L.marker([lat, lng], {
                icon: flowPortIcon(port),
                interactive: false,
                zIndexOffset: 1100 + index,
            }));
        });
    });
}

function buildCableBundlePopupHtml(groupCables, pointsById) {
    const items = groupCables.map((cable) => {
        const label = escapeHtml(cable.name || `${cableTypeLabel(cable.cable_type)} #${cable.id}`);
        const distance = formatDistanceM(cableMapDistanceM(cable, pointsById));

        return `<button type="button" class="network-popup__bundle-item" data-cable-id="${cable.id}">${label}<span>${escapeHtml(distance)}</span></button>`;
    }).join('');

    return `
        <div class="network-popup network-popup--bundle">
            <strong>${escapeHtml(t('map.cableBundleTitle'))}</strong>
            <p class="network-popup__bundle-count">${escapeHtml(t('map.cableBundleCount', { count: groupCables.length }))}</p>
            <div class="network-popup__bundle-list">${items}</div>
        </div>
    `;
}

function attachCableBundlePopupHandlers(marker, groupCables) {
    marker.on('popupopen', () => {
        const popupEl = marker.getPopup()?.getElement()?.querySelector('.network-popup--bundle');

        if (!popupEl) {
            return;
        }

        popupEl.querySelectorAll('[data-cable-id]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const cableId = button.getAttribute('data-cable-id');
                const cable = groupCables.find((item) => String(item.id) === String(cableId));

                if (cable) {
                    map.closePopup();
                    emit('select-cable', cable);
                }
            });
        });
    });
}

function renderCables() {
    if (!cablesLayer) {
        return;
    }

    cablesLayer.clearLayers();

    const { groups: edgeGroups } = buildEdgeGroups(props.cables, pointsById.value);
    const sharedBadges = buildSharedEdgeBadges(props.cables, pointsById.value);
    const renderedBundleBadges = new Set();

    props.cables.forEach((cable) => {
        const baseCoords = cablePathCoordinates(cable, pointsById.value);

        if (baseCoords.length < 2) {
            return;
        }

        const chunks = buildCableRenderChunks(cable, pointsById.value, edgeGroups);
        const meta = cableSharedEdgeMeta(cable, edgeGroups);
        const isSelected = String(cable.id) === String(props.selectedCableId);
        const routePointIds = cableRoutePointIds(cable);
        const isConnectedToSelectedPoint = selectedPoint.value
            && routePointIds.includes(Number(selectedPoint.value.id));

        const color = cableTypeColor(cable.cable_type);
        const weight = isSelected ? 7 : (isConnectedToSelectedPoint ? 5 : 4);
        const opacity = isSelected || isConnectedToSelectedPoint ? 1 : 0.85;
        const mapDistance = cableMapDistanceM(cable, pointsById.value);
        const mapDistanceLabel = formatDistanceM(mapDistance);
        const actualLengthLabel = cable.length_m != null ? formatDistanceM(Number(cable.length_m)) : '—';
        const popupHtml = buildCablePopupHtml(cable, {
            mapDistanceLabel,
            actualLengthLabel,
            routePointIds,
            groupIndex: meta.groupIndex,
            groupSize: meta.groupSize,
        });
        const hasSharedSegments = chunks.some((chunk) => chunk.offsetMeters !== 0);

        if (props.interactionMode === 'view') {
            const hitLine = L.polyline(baseCoords, {
                color: 'transparent',
                weight: Math.max(18, 12 + meta.groupSize * 2),
                opacity: 0,
                interactive: true,
            });

            hitLine.on('click', (event) => {
                L.DomEvent.stopPropagation(event);
                emit('select-cable', cable);
            });

            cablesLayer.addLayer(hitLine);
        }

        chunks.forEach((chunk) => {
            if (chunk.coords.length < 2) {
                return;
            }

            const coords = chunk.offsetMeters === 0
                ? chunk.coords
                : offsetPolylineCoordinates(chunk.coords, chunk.offsetMeters);

            const line = L.polyline(coords, {
                color,
                weight,
                opacity,
                dashArray: cable.status === 'planned' ? '8 8' : null,
                className: isSelected
                    ? 'network-cable-line network-cable-line--selected'
                    : (hasSharedSegments ? 'network-cable-line network-cable-line--bundled' : 'network-cable-line'),
            });

            line.bindPopup(popupHtml, { className: 'network-cable-popup' });

            if (props.interactionMode === 'view') {
                line.on('click', (event) => {
                    L.DomEvent.stopPropagation(event);
                    emit('select-cable', cable);
                });
            }

            cablesLayer.addLayer(line);
        });
    });

    sharedBadges.forEach((badge) => {
        if (renderedBundleBadges.has(badge.edgeKey)) {
            return;
        }

        renderedBundleBadges.add(badge.edgeKey);

        const badgeMarker = L.marker(badge.midpoint, {
            icon: cableBundleBadgeIcon(badge.groupCables.length),
            interactive: true,
            zIndexOffset: 1200,
        });

        badgeMarker.bindPopup(
            buildCableBundlePopupHtml(badge.groupCables, pointsById.value),
            { className: 'network-cable-popup', maxWidth: 260 },
        );
        attachCableBundlePopupHandlers(badgeMarker, badge.groupCables);

        if (props.interactionMode === 'view') {
            badgeMarker.on('click', (event) => {
                L.DomEvent.stopPropagation(event);
            });
        }

        cablesLayer.addLayer(badgeMarker);
    });

    renderCableFlows(edgeGroups);
}

function renderCableDraft() {
    if (!cableDraftLayer) {
        return;
    }

    cableDraftLayer.clearLayers();
    draftWaypointRegistry.clear();

    const draft = props.cableDraft;

    if (!draft?.nodes?.length || props.interactionMode !== 'cable') {
        return;
    }

    const coords = routeCoordinatesFromNodes(draft.nodes, pointsById.value);
    const bendNodes = draft.nodes
        .map((node, index) => ({ node, index }))
        .filter(({ node }) => node.type === 'bend');

    const color = cableTypeColor(props.cableType);

    if (coords.length >= 2) {
        cableDraftLayer.addLayer(L.polyline(coords, {
            color,
            weight: 5,
            opacity: 0.9,
            dashArray: bendNodes.length ? null : '8 10',
        }));
    } else if (coords.length === 1) {
        cableDraftLayer.addLayer(L.polyline(coords, {
            color,
            weight: 5,
            opacity: 0.9,
            dashArray: '8 10',
        }));
    }

    bendNodes.forEach(({ node, index }) => {
        const marker = L.marker([Number(node.lat), Number(node.lng)], {
            icon: waypointIcon(),
            draggable: true,
            autoPan: true,
            zIndexOffset: 1500,
        });

        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            emit('cable-waypoint-move', {
                index,
                latitude: pos.lat,
                longitude: pos.lng,
            });
        });

        cableDraftLayer.addLayer(marker);
        draftWaypointRegistry.set(index, marker);
    });
}

function fitBoundsIfNeeded() {
    if (!map || !props.fitOnUpdate || !props.points.length || hasInitialFit.value) {
        return;
    }

    const bounds = L.latLngBounds(props.points.map((point) => [point.latitude, point.longitude]));
    map.fitBounds(bounds.pad(0.15), { maxZoom: 16 });
    hasInitialFit.value = true;
}

watch(() => props.interactionMode, (mode) => {
    if (mode !== 'cable') {
        renderCableDraft();
    }

    syncMarkers();
    syncDraftMarker();
    renderHighlight();
});

watch(() => props.points, () => {
    syncMarkers();
    renderCables();
    renderCableDraft();
    fitBoundsIfNeeded();
}, { deep: true });

watch(() => props.cables, renderCables, { deep: true });

watch(() => props.cableDraft, () => {
    renderCableDraft();
    syncMarkers();
}, { deep: true });

watch(() => props.cableType, () => {
    renderCables();
    renderCableDraft();
});

watch(
    () => props.draftPoint,
    (draft) => {
        if (!draft?.latitude || !draft?.longitude) {
            clearDraftMarker();

            return;
        }

        syncDraftMarker();
    },
);

watch(
    () => draftVisualKey(props.draftPoint),
    (key, previousKey) => {
        if (!key || key === previousKey) {
            return;
        }

        syncDraftMarker();
    },
);

watch(() => props.selectedPointId, () => {
    syncMarkers();
    renderCables();
    focusSelectedPoint();
});

watch(() => props.selectedCableId, () => {
    renderCables();
});

watch(() => props.signalTrace, () => {
    renderSignalTrace();
}, { deep: true });

watch(() => props.draggable, () => syncMarkers());

watch(() => props.layoutVisible, (visible) => {
    if (!visible || !map) {
        return;
    }

    nextTick(() => {
        map.invalidateSize();
    });
});

watch(() => props.loading, (isLoading, wasLoading) => {
    if (isLoading || wasLoading === undefined || !map) {
        return;
    }

    nextTick(() => {
        map.invalidateSize();
        fitBoundsIfNeeded();
    });
});

function handleCableClick(point) {
    if (!props.cableDraft?.nodes?.length) {
        emit('cable-start', point.id);

        return;
    }

    const lastNode = props.cableDraft.nodes[props.cableDraft.nodes.length - 1];

    if (lastNode.type === 'point' && String(lastNode.point_id) === String(point.id)) {
        return;
    }

    emit('cable-add-point', point.id);
}

function handleMapClick(event) {
    if (props.interactionMode === 'cable' && props.cableDraft?.nodes?.length) {
        emit('cable-waypoint', {
            latitude: event.latlng.lat,
            longitude: event.latlng.lng,
        });

        return;
    }

    const placingPoint = props.interactionMode === 'add'
        || (props.draftPoint?.latitude != null && props.draftPoint?.longitude != null);

    if (placingPoint && props.canCreate) {
        emit('add-point', {
            latitude: event.latlng.lat,
            longitude: event.latlng.lng,
        });
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function onLocationFound(event) {
    locationError.value = '';

    if (!hasCenteredOnUser.value) {
        hasCenteredOnUser.value = true;
        map.setView(event.latlng, Math.max(map.getZoom(), 16));
    }
}

function onLocationError(error) {
    const messages = {
        1: t('map.locationPermissionDenied'),
        2: t('map.locationUnavailable'),
        3: t('map.locationTimeout'),
    };

    locationError.value = messages[error.code] ?? t('map.locationError');
    liveLocationActive.value = false;
    map?.stopLocate();
}

function startLiveLocation() {
    if (!map) {
        return;
    }

    if (!('geolocation' in navigator)) {
        locationError.value = t('map.locationUnsupported');

        return;
    }

    locationError.value = '';
    liveLocationActive.value = true;
    hasCenteredOnUser.value = false;

    map.on('locationfound', onLocationFound);
    map.on('locationerror', onLocationError);
    map.locate({
        watch: true,
        setView: false,
        enableHighAccuracy: true,
        maxZoom: 17,
    });
}

function stopLiveLocation() {
    liveLocationActive.value = false;

    if (!map) {
        return;
    }

    map.off('locationfound', onLocationFound);
    map.off('locationerror', onLocationError);
    map.stopLocate();
}

function toggleLiveLocation() {
    if (liveLocationActive.value) {
        stopLiveLocation();
        locationError.value = '';

        return;
    }

    startLiveLocation();
}

function setBaseMapStyle(style) {
    if (!map || baseMapStyle.value === style) {
        return;
    }

    const currentLayer = baseMapStyle.value === 'satellite' ? satelliteLayer : osmLayer;
    const nextLayer = style === 'satellite' ? satelliteLayer : osmLayer;

    if (currentLayer) {
        map.removeLayer(currentLayer);
    }

    if (nextLayer) {
        nextLayer.addTo(map);
    }

    baseMapStyle.value = style;
}

onMounted(() => {
    map = L.map(mapEl.value, { zoomControl: false }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    });

    satelliteLayer = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community',
            maxZoom: 19,
        },
    );

    osmLayer.addTo(map);

    cablesLayer = L.layerGroup().addTo(map);
    flowLayer = L.layerGroup().addTo(map);
    cableDraftLayer = L.layerGroup().addTo(map);
    highlightLayer = L.layerGroup().addTo(map);
    signalTraceLayer = L.layerGroup().addTo(map);
    previewLayer = L.layerGroup().addTo(map);
    markersLayer = L.layerGroup().addTo(map);

    map.on('click', handleMapClick);
    map.on('move zoom zoomend moveend', handleMapViewChange);

    syncMarkers();
    renderCables();
    fitBoundsIfNeeded();

    resizeHandler = () => {
        if (map) {
            map.invalidateSize();
        }
    };

    window.addEventListener('resize', resizeHandler);
});

onBeforeUnmount(() => {
    stopLiveLocation();
    markerRegistry.clear();

    if (resizeHandler) {
        window.removeEventListener('resize', resizeHandler);
    }

    if (map) {
        map.off();
        map.remove();
    }
});
</script>

<style scoped>
.network-map-shell :deep(.leaflet-container) {
    font-family: inherit;
    background: #e2e8f0;
    z-index: 0;
}

.network-map-shell :deep(.network-point-marker-wrap) {
    background: transparent;
    border: none;
}

.network-map-shell :deep(.network-marker) {
    position: relative;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.network-map-shell :deep(.network-marker--selected),
.network-map-shell :deep(.network-marker--preview) {
    width: 48px;
    height: 48px;
}

.network-map-shell :deep(.network-marker--preview) {
    width: 52px;
    height: 52px;
}

.network-map-shell :deep(.network-marker__pin) {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 9999px;
    background: var(--marker-color);
    border: 2.5px solid #fff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease;
    color: #fff;
}

.network-map-shell :deep(.network-marker__icon) {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 12px;
    height: 12px;
}

.network-map-shell :deep(.network-marker__icon svg) {
    width: 12px;
    height: 12px;
}

.network-map-shell :deep(.network-marker--selected .network-marker__pin),
.network-map-shell :deep(.network-marker--preview .network-marker__pin) {
    width: 28px;
    height: 28px;
    border-width: 3px;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.45);
}

.network-map-shell :deep(.network-marker--preview .network-marker__pin) {
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.55);
    outline: 2px dashed rgba(255, 255, 255, 0.85);
    outline-offset: 2px;
}

.network-map-shell :deep(.network-marker--selected .network-marker__icon),
.network-map-shell :deep(.network-marker--preview .network-marker__icon) {
    width: 14px;
    height: 14px;
}

.network-map-shell :deep(.network-marker--selected .network-marker__icon svg),
.network-map-shell :deep(.network-marker--preview .network-marker__icon svg) {
    width: 14px;
    height: 14px;
}

.network-map-shell :deep(.network-marker--dragging .network-marker__pin) {
    transform: scale(1.15);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.55);
    cursor: grabbing;
}

.network-map-shell :deep(.network-marker__ring) {
    position: absolute;
    inset: 4px;
    border-radius: 9999px;
    border: 2px solid rgba(59, 130, 246, 0.7);
    animation: network-marker-pulse 1.8s ease-out infinite;
    z-index: 1;
}

.network-map-shell :deep(.network-marker__ring--delayed) {
    animation-delay: 0.6s;
    border-color: rgba(59, 130, 246, 0.35);
}

.network-map-shell :deep(.network-marker__label) {
    position: absolute;
    top: -28px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 3;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 3px 8px;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.88);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
    pointer-events: none;
}

.network-map-shell :deep(.network-marker__badge) {
    display: inline-block;
    margin-right: 4px;
    padding: 1px 5px;
    border-radius: 9999px;
    background: #3b82f6;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.network-map-shell :deep(.network-marker__multi) {
    position: absolute;
    top: -4px;
    right: -4px;
    z-index: 2;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 9999px;
    background: #0f172a;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.35);
    pointer-events: none;
}

.network-map-shell :deep(.network-marker__stack) {
    position: absolute;
    bottom: -4px;
    left: -4px;
    z-index: 2;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 9999px;
    background: #2563eb;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    box-shadow: 0 1px 4px rgba(37, 99, 235, 0.35);
    pointer-events: none;
}

.network-map-shell :deep(.network-popup--stack) {
    display: grid;
    gap: 8px;
    min-width: 180px;
}

.network-map-shell :deep(.network-popup__stack-list) {
    display: grid;
    gap: 4px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.network-map-shell :deep(.network-popup__stack-btn) {
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.96);
    padding: 6px 8px;
    text-align: left;
    font-size: 12px;
    line-height: 1.35;
    color: #0f172a;
    cursor: pointer;
}

.network-map-shell :deep(.network-popup__stack-btn:hover) {
    border-color: #2563eb;
    background: #eff6ff;
}

.network-map-shell :deep(.network-cable-line--selected) {
    filter: drop-shadow(0 0 4px rgba(59, 130, 246, 0.55));
}

.network-map-shell :deep(.network-cable-line--bundled) {
    filter: drop-shadow(0 0 1px rgba(15, 23, 42, 0.25));
}

.network-map-shell :deep(.network-cable-flow) {
    pointer-events: none;
}

.network-map-shell :deep(.network-cable-flow path) {
    stroke-dasharray: 10 18;
    stroke-linecap: round;
}

.network-map-shell :deep(.network-cable-flow--forward path) {
    animation: network-cable-flow-forward 1.35s linear infinite;
}

.network-map-shell :deep(.network-cable-flow--reverse path) {
    animation: network-cable-flow-reverse 1.35s linear infinite;
}

.network-map-shell :deep(.network-cable-flow--emphasized path) {
    stroke-dasharray: 12 14;
    filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.45));
}

.network-map-shell :deep(.network-flow-port-wrap) {
    background: transparent;
    border: none;
}

.network-map-shell :deep(.network-flow-port) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 18px;
    padding: 0 6px;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.88);
    color: #fff;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.04em;
    border: 2px solid var(--port-color);
    box-shadow: 0 1px 6px rgba(15, 23, 42, 0.35);
    pointer-events: none;
}

.network-map-shell :deep(.network-flow-port--in) {
    background: color-mix(in srgb, var(--port-color) 22%, rgba(15, 23, 42, 0.92));
}

.network-map-shell :deep(.network-flow-port--out) {
    background: color-mix(in srgb, var(--port-color) 38%, rgba(15, 23, 42, 0.92));
}

.network-map-shell :deep(.network-popup__flow) {
    display: inline-block;
    margin-top: 4px;
    font-size: 11px;
    color: #2563eb;
    font-weight: 600;
}

.network-map-shell :deep(.network-cable-bundle-wrap) {
    background: transparent;
    border: none;
}

.network-map-shell :deep(.network-cable-bundle-badge) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.88);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    border: 2px solid #fff;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.35);
    cursor: pointer;
}

.network-map-shell :deep(.network-cable-popup .leaflet-popup-content) {
    margin: 10px 12px;
}

.network-map-shell :deep(.network-popup__bundle) {
    display: inline-block;
    margin-top: 4px;
    font-size: 11px;
    color: #64748b;
}

.network-map-shell :deep(.network-popup--bundle .network-popup__bundle-count) {
    margin: 4px 0 8px;
    font-size: 12px;
    color: #64748b;
}

.network-map-shell :deep(.network-popup__bundle-list) {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.network-map-shell :deep(.network-popup__bundle-item) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    border: 1px solid rgba(148, 163, 184, 0.45);
    border-radius: 8px;
    background: rgba(248, 250, 252, 0.95);
    padding: 6px 8px;
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    text-align: left;
}

.network-map-shell :deep(.network-popup__bundle-item span) {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
}

.network-map-shell :deep(.network-popup__bundle-item:hover) {
    border-color: rgba(59, 130, 246, 0.55);
    background: rgba(239, 246, 255, 0.95);
}

.network-map-shell :deep(.network-marker--cable-target .network-marker__pin) {
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.45);
}

.network-map-shell :deep(.network-cable-waypoint) {
    display: block;
    width: 14px;
    height: 14px;
    border-radius: 9999px;
    background: var(--waypoint-color);
    border: 2px solid #fff;
    box-shadow: 0 1px 6px rgba(15, 23, 42, 0.35);
    cursor: grab;
}

.network-map-shell :deep(.network-cable-waypoint-wrap) {
    background: transparent;
    border: none;
}

.network-map-shell :deep(.leaflet-marker-draggable) {
    cursor: grab;
}

.network-map-shell :deep(.leaflet-marker-draggable:active) {
    cursor: grabbing;
}

.network-map-shell :deep(.network-point-popup .leaflet-popup-content) {
    margin: 10px 12px;
    line-height: 1.4;
}

.network-map-shell :deep(.network-popup__meta) {
    margin-top: 4px;
    font-size: 12px;
    color: #64748b;
    text-transform: capitalize;
}

.network-map-shell :deep(.network-popup__area) {
    margin-top: 2px;
    font-size: 12px;
    color: #475569;
}

.network-map-shell :deep(.network-popup__hint) {
    margin-top: 6px;
    font-size: 11px;
    color: #3b82f6;
    font-weight: 500;
}

@keyframes network-marker-pulse {
    0% {
        transform: scale(0.75);
        opacity: 0.9;
    }
    70% {
        transform: scale(1.35);
        opacity: 0;
    }
    100% {
        transform: scale(1.35);
        opacity: 0;
    }
}

@keyframes network-cable-flow-forward {
    from {
        stroke-dashoffset: 28;
    }

    to {
        stroke-dashoffset: 0;
    }
}

@keyframes network-cable-flow-reverse {
    from {
        stroke-dashoffset: 0;
    }

    to {
        stroke-dashoffset: 28;
    }
}

.network-map-shell :deep(.network-signal-arrow-wrap) {
    background: transparent;
    border: none;
}

.network-map-shell :deep(.network-signal-arrow-shell) {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    transform-origin: center center;
}

.network-map-shell :deep(.network-signal-arrow) {
    display: block;
    width: 0;
    height: 0;
    margin-left: 2px;
    border-top: 5px solid transparent;
    border-bottom: 5px solid transparent;
    border-left: 10px solid #059669;
    filter: drop-shadow(0 0 3px rgba(16, 185, 129, 0.6));
}

.network-map-shell :deep(.network-signal-trace-point) {
    pointer-events: none;
}

.network-map-shell :deep(.network-signal-trace-point--active) {
    animation: network-signal-point-pulse 1.1s ease-in-out infinite;
}

.network-map-shell :deep(.network-signal-trace-line--active) {
    animation: network-signal-dash 0.8s linear infinite;
}

@keyframes network-signal-pulse {
    0%, 100% {
        opacity: 1;
    }

    50% {
        opacity: 0.72;
    }
}

@keyframes network-signal-point-pulse {
    0%, 100% {
        opacity: 1;
    }

    50% {
        opacity: 0.72;
    }
}

@keyframes network-signal-dash {
    from {
        stroke-dashoffset: 0;
    }

    to {
        stroke-dashoffset: 24;
    }
}

</style>
