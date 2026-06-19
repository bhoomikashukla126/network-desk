export const POINT_COLORS = {
    uplink: '#dc2626',
    bras: '#ea580c',
    switch: '#0284c7',
    odf: '#059669',
    dwdm: '#7c3aed',
    router: '#2563eb',
    splitter: '#9333ea',
    junction: '#0891b2',
    cabinet: '#0d9488',
    pole: '#d97706',
    customer: '#db2777',
};

export const STATUS_COLORS = {
    active: '#22c55e',
    planned: '#3b82f6',
    maintenance: '#f59e0b',
    inactive: '#94a3b8',
    damaged: '#ef4444',
};

export const DEFAULT_CENTER = [20.5937, 78.9629];
export const DEFAULT_ZOOM = 5;

export function pointColor(type) {
    return POINT_COLORS[type] ?? '#64748b';
}

export function normalizePointTypes(point) {
    if (Array.isArray(point?.types) && point.types.length) {
        return [...new Set(point.types.filter(Boolean))];
    }

    if (point?.type) {
        return [point.type];
    }

    return ['junction'];
}

const LOCATION_EPSILON = 1e-5;

export function isSameLocation(left, right, epsilon = LOCATION_EPSILON) {
    return Math.abs(Number(left?.latitude) - Number(right?.latitude)) < epsilon
        && Math.abs(Number(left?.longitude) - Number(right?.longitude)) < epsilon;
}

export function findPointsAtLocation(points, latitude, longitude, epsilon = LOCATION_EPSILON) {
    const target = { latitude, longitude };

    return (points ?? []).filter((point) => isSameLocation(point, target, epsilon));
}

export function colocatedPointsCount(points, point, epsilon = LOCATION_EPSILON) {
    if (!point) {
        return 0;
    }

    return findPointsAtLocation(points, point.latitude, point.longitude, epsilon).length;
}

export function primaryPointType(point) {
    return normalizePointTypes(point)[0];
}

export function pointTypeLabels(point, labels = {}) {
    return normalizePointTypes(point).map((type) => labels[type] ?? pointTypeLabel(type));
}

export function pointTypeLabel(type) {
    const labels = {
        uplink: 'Uplink',
        bras: 'BRAS',
        switch: 'Switch',
        odf: 'ODF',
        dwdm: 'DWDM',
        router: 'OLT',
        splitter: 'Splitter',
        junction: 'Junction',
        cabinet: 'Cabinet',
        pole: 'Pole',
        customer: 'Customer',
    };

    return labels[type] ?? type;
}

export function pointTypeIcon(type) {
    const icons = {
        uplink: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M12 2v4"/><path d="M12 18v4"/></svg>',
        bras: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v3"/><path d="M8 8 6.5 6.5"/><path d="M16 8l1.5-1.5"/><circle cx="12" cy="16" r="2"/></svg>',
        switch: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>',
        odf: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v10H4z"/><path d="M8 7v10M12 7v10M16 7v10"/></svg>',
        dwdm: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12c2-4 6-6 10-6s8 2 10 6"/><path d="M2 12c2 4 6 6 10 6s8-2 10-6"/></svg>',
        router: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v3"/><path d="M8 8l-1.5-1.5"/><path d="M16 8l1.5-1.5"/><circle cx="12" cy="16" r="2"/></svg>',
        splitter: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="12" cy="18" r="2"/><path d="M7.5 7.5 10.5 15"/><path d="M16.5 7.5 13.5 15"/></svg>',
        junction: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>',
        cabinet: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h6"/></svg>',
        pole: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v15"/><path d="M8 7h8"/><path d="M7 21h10"/></svg>',
        customer: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21V12h6v9"/></svg>',
    };

    return icons[type] ?? icons.junction;
}

export function buildMarkerHtml(point, options = {}) {
    const { selected = false, dragging = false, cableTarget = false, preview = false, stackCount = 0 } = options;
    const types = normalizePointTypes(point);
    const primaryType = types[0];
    const color = pointColor(primaryType);
    const name = String(point.name ?? '').slice(0, 32);
    const typeLabel = pointTypeLabels(point).join(', ');
    const classes = [
        'network-marker',
        selected || preview ? 'network-marker--selected' : '',
        preview ? 'network-marker--preview' : '',
        dragging ? 'network-marker--dragging' : '',
        cableTarget ? 'network-marker--cable-target' : '',
        types.length > 1 ? 'network-marker--multi-type' : '',
        `network-marker--type-${primaryType || 'junction'}`,
    ].filter(Boolean).join(' ');

    const labelText = preview
        ? (name || typeLabel)
        : name;

    const label = (selected || preview) && labelText
        ? `<span class="network-marker__label">${preview && !name ? `<span class="network-marker__badge">New</span> ` : ''}${escapeHtml(labelText)}</span>`
        : '';

    const multiBadge = types.length > 1
        ? `<span class="network-marker__multi" title="${escapeHtml(typeLabel)}">${types.length}</span>`
        : '';

    const stackBadge = stackCount > 1
        ? `<span class="network-marker__stack" title="${stackCount} points here">${stackCount}</span>`
        : '';

    return `
        <div class="${classes}" title="${escapeHtml(name || typeLabel)}">
            ${(selected || preview) ? '<span class="network-marker__ring"></span><span class="network-marker__ring network-marker__ring--delayed"></span>' : ''}
            <span class="network-marker__pin" style="--marker-color:${color}">
                <span class="network-marker__icon">${pointTypeIcon(primaryType || 'junction')}</span>
                ${multiBadge}
                ${stackBadge}
            </span>
            ${label}
        </div>
    `;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

export const CABLE_TYPE_COLORS = {
    fiber: '#8b5cf6',
    coax: '#f97316',
    ethernet: '#0ea5e9',
    wireless: '#10b981',
};

const DEFAULT_CABLE_TYPE_LABELS = {
    fiber: 'Fiber optic',
    coax: 'Coaxial',
    ethernet: 'Ethernet',
    wireless: 'Wireless link',
};

let cableTypeLabels = { ...DEFAULT_CABLE_TYPE_LABELS };
let cableTypeColors = { ...CABLE_TYPE_COLORS };

export function setCableTypeCatalog(types = {}, colors = {}) {
    cableTypeLabels = { ...DEFAULT_CABLE_TYPE_LABELS, ...types };
    cableTypeColors = { ...CABLE_TYPE_COLORS, ...colors };
}

export function cableTypeColor(type) {
    if (cableTypeColors[type]) {
        return cableTypeColors[type];
    }

    return hashColor(String(type ?? 'cable'));
}

export function cableTypeLabel(type) {
    return cableTypeLabels[type] ?? String(type ?? 'Cable');
}

function hashColor(value) {
    let hash = 0;

    for (let index = 0; index < value.length; index += 1) {
        hash = value.charCodeAt(index) + ((hash << 5) - hash);
    }

    const hue = Math.abs(hash) % 360;

    return `hsl(${hue} 62% 42%)`;
}

export function statusColor(status) {
    return STATUS_COLORS[status] ?? '#64748b';
}

export function cableRouteNodes(cable) {
    if (Array.isArray(cable?.route) && cable.route.length >= 2) {
        return cable.route;
    }

    const nodes = [{ type: 'point', point_id: cable.from_point_id }];

    if (Array.isArray(cable?.path)) {
        cable.path.forEach((pair) => {
            if (Array.isArray(pair) && pair.length === 2) {
                nodes.push({ type: 'bend', lat: Number(pair[0]), lng: Number(pair[1]) });
            }
        });
    }

    nodes.push({ type: 'point', point_id: cable.to_point_id });

    return nodes;
}

export function cableRoutePointIds(cable) {
    return cableRouteNodes(cable)
        .filter((node) => node.type === 'point' && node.point_id != null)
        .map((node) => Number(node.point_id));
}

/**
 * A fiber core's start end lives at the route origin; its end end at the route terminus.
 */
export function isCoreSideAtRoutePoint(cable, side, pointId) {
    const routePointIds = cableRoutePointIds(cable);

    if (routePointIds.length < 2) {
        return false;
    }

    const first = Number(routePointIds[0]);
    const last = Number(routePointIds[routePointIds.length - 1]);
    const target = Number(pointId);

    if (side === 'start') {
        return target === first;
    }

    if (side === 'end') {
        return target === last;
    }

    return false;
}

export function resolvedCoreEndPointId(cable, side) {
    const routePointIds = cableRoutePointIds(cable);

    if (routePointIds.length < 2) {
        return null;
    }

    return side === 'start'
        ? Number(routePointIds[0])
        : Number(routePointIds[routePointIds.length - 1]);
}

export function routeCoordinatesFromNodes(nodes, pointsById) {
    const coords = [];

    nodes.forEach((node) => {
        if (node.type === 'point') {
            const point = pointsById[node.point_id];

            if (point) {
                coords.push([Number(point.latitude), Number(point.longitude)]);
            }

            return;
        }

        if (node.type === 'bend') {
            coords.push([Number(node.lat), Number(node.lng)]);
        }
    });

    return coords;
}

export function cablePathCoordinates(cable, pointsById) {
    return routeCoordinatesFromNodes(cableRouteNodes(cable), pointsById);
}

export const CABLE_PARALLEL_SPACING_M = 5;

export function cableRouteVertices(cable, pointsById) {
    const vertices = [];

    cableRouteNodes(cable).forEach((node) => {
        if (node.type === 'point') {
            const point = pointsById[node.point_id];

            if (!point) {
                return;
            }

            vertices.push({
                key: `p:${node.point_id}`,
                lat: Number(point.latitude),
                lng: Number(point.longitude),
            });

            return;
        }

        if (node.type === 'bend') {
            vertices.push({
                key: `b:${Number(node.lat).toFixed(5)}:${Number(node.lng).toFixed(5)}`,
                lat: Number(node.lat),
                lng: Number(node.lng),
            });
        }
    });

    return vertices;
}

export function edgeSignature(fromKey, toKey) {
    return fromKey < toKey ? `${fromKey}|${toKey}` : `${toKey}|${fromKey}`;
}

export function buildEdgeGroups(cables, pointsById) {
    const groups = new Map();
    const edgeCoords = new Map();

    cables.forEach((cable) => {
        const vertices = cableRouteVertices(cable, pointsById);

        for (let index = 0; index < vertices.length - 1; index += 1) {
            const from = vertices[index];
            const to = vertices[index + 1];
            const key = edgeSignature(from.key, to.key);

            if (!groups.has(key)) {
                groups.set(key, []);
            }

            if (!groups.get(key).some((item) => Number(item.id) === Number(cable.id))) {
                groups.get(key).push(cable);
            }

            if (!edgeCoords.has(key)) {
                edgeCoords.set(key, {
                    from: [from.lat, from.lng],
                    to: [to.lat, to.lng],
                });
            }
        }
    });

    groups.forEach((group) => {
        group.sort((left, right) => Number(left.id) - Number(right.id));
    });

    return { groups, edgeCoords };
}

export function cableOffsetOnEdge(cable, edgeKey, edgeGroups) {
    const group = edgeGroups.get(edgeKey) ?? [cable];

    if (group.length === 1) {
        return 0;
    }

    const index = group.findIndex((item) => Number(item.id) === Number(cable.id));

    return (index - ((group.length - 1) / 2)) * CABLE_PARALLEL_SPACING_M;
}

export function buildCableRenderChunks(cable, pointsById, edgeGroups) {
    const vertices = cableRouteVertices(cable, pointsById);

    if (vertices.length < 2) {
        return [];
    }

    const chunks = [];
    let chunkStart = 0;
    let currentOffset = cableOffsetOnEdge(
        cable,
        edgeSignature(vertices[0].key, vertices[1].key),
        edgeGroups,
    );

    for (let index = 1; index < vertices.length - 1; index += 1) {
        const nextOffset = cableOffsetOnEdge(
            cable,
            edgeSignature(vertices[index].key, vertices[index + 1].key),
            edgeGroups,
        );

        if (nextOffset !== currentOffset) {
            chunks.push({
                coords: vertices.slice(chunkStart, index + 1).map((vertex) => [vertex.lat, vertex.lng]),
                offsetMeters: currentOffset,
            });
            chunkStart = index;
            currentOffset = nextOffset;
        }
    }

    chunks.push({
        coords: vertices.slice(chunkStart).map((vertex) => [vertex.lat, vertex.lng]),
        offsetMeters: currentOffset,
    });

    return chunks;
}

export function buildSharedEdgeBadges(cables, pointsById) {
    const { groups, edgeCoords } = buildEdgeGroups(cables, pointsById);
    const badges = [];

    groups.forEach((groupCables, edgeKey) => {
        if (groupCables.length <= 1) {
            return;
        }

        const coords = edgeCoords.get(edgeKey);

        if (!coords) {
            return;
        }

        badges.push({
            edgeKey,
            groupCables,
            midpoint: [
                (coords.from[0] + coords.to[0]) / 2,
                (coords.from[1] + coords.to[1]) / 2,
            ],
        });
    });

    return badges;
}

export function cableEdgeKeys(cable) {
    const keys = cableRouteNodes(cable).map((node) => {
        if (node.type === 'point') {
            return `p:${node.point_id}`;
        }

        return `b:${Number(node.lat).toFixed(5)}:${Number(node.lng).toFixed(5)}`;
    });
    const edges = [];

    for (let index = 0; index < keys.length - 1; index += 1) {
        edges.push(edgeSignature(keys[index], keys[index + 1]));
    }

    return edges;
}

export function cableSharedEdgeMeta(cable, edgeGroups) {
    let largestGroup = [cable];

    cableEdgeKeys(cable).forEach((edgeKey) => {
        const group = edgeGroups.get(edgeKey) ?? [cable];

        if (group.length > largestGroup.length) {
            largestGroup = group;
        }
    });

    if (largestGroup.length <= 1) {
        return { groupSize: 1, groupIndex: 0, groupCables: [cable] };
    }

    const groupIndex = largestGroup.findIndex((item) => Number(item.id) === Number(cable.id));

    return {
        groupSize: largestGroup.length,
        groupIndex: groupIndex === -1 ? 0 : groupIndex,
        groupCables: largestGroup,
    };
}

export function cableRouteSignature(cable) {
    const nodes = cableRouteNodes(cable);
    const pointIds = cableRoutePointIds(cable);
    const bendCount = nodes.filter((node) => node.type === 'bend').length;

    if (pointIds.length === 2 && bendCount === 0) {
        const [first, second] = pointIds;

        return first < second ? `p:${first}|p:${second}` : `p:${second}|p:${first}`;
    }

    return nodes.map((node) => {
        if (node.type === 'point') {
            return `p:${node.point_id}`;
        }

        return `b:${Number(node.lat).toFixed(5)}:${Number(node.lng).toFixed(5)}`;
    }).join('|');
}

export function buildCableGroupMeta(cables) {
    const groups = new Map();

    cables.forEach((cable) => {
        const signature = cableRouteSignature(cable);

        if (!groups.has(signature)) {
            groups.set(signature, []);
        }

        groups.get(signature).push(cable);
    });

    const meta = new Map();

    groups.forEach((group, signature) => {
        const sorted = [...group].sort((left, right) => Number(left.id) - Number(right.id));
        const groupSize = sorted.length;

        sorted.forEach((cable, groupIndex) => {
            const offsetMeters = groupSize === 1
                ? 0
                : (groupIndex - ((groupSize - 1) / 2)) * CABLE_PARALLEL_SPACING_M;

            meta.set(Number(cable.id), {
                signature,
                groupIndex,
                groupSize,
                offsetMeters,
                groupCables: sorted,
            });
        });
    });

    return meta;
}

function bearingBetween([lat1, lng1], [lat2, lng2]) {
    const toRad = (degrees) => degrees * (Math.PI / 180);
    const toDeg = (radians) => radians * (180 / Math.PI);
    const phi1 = toRad(lat1);
    const phi2 = toRad(lat2);
    const deltaLng = toRad(lng2 - lng1);
    const y = Math.sin(deltaLng) * Math.cos(phi2);
    const x = Math.cos(phi1) * Math.sin(phi2) - Math.sin(phi1) * Math.cos(phi2) * Math.cos(deltaLng);

    return (toDeg(Math.atan2(y, x)) + 360) % 360;
}

function averageBearing(first, second) {
    const toRad = (degrees) => degrees * (Math.PI / 180);
    const toDeg = (radians) => radians * (180 / Math.PI);
    const x = Math.cos(toRad(first)) + Math.cos(toRad(second));
    const y = Math.sin(toRad(first)) + Math.sin(toRad(second));

    return (toDeg(Math.atan2(y, x)) + 360) % 360;
}

export function offsetLatLng(lat, lng, bearingDeg, distanceM) {
    const earthRadiusM = 6371000;
    const bearing = bearingDeg * (Math.PI / 180);
    const lat1 = lat * (Math.PI / 180);
    const lng1 = lng * (Math.PI / 180);
    const angularDistance = distanceM / earthRadiusM;
    const lat2 = Math.asin(
        Math.sin(lat1) * Math.cos(angularDistance)
        + Math.cos(lat1) * Math.sin(angularDistance) * Math.cos(bearing),
    );
    const lng2 = lng1 + Math.atan2(
        Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(lat1),
        Math.cos(angularDistance) - Math.sin(lat1) * Math.sin(lat2),
    );

    return [lat2 * (180 / Math.PI), lng2 * (180 / Math.PI)];
}

export function offsetPolylineCoordinates(coordinates, offsetMeters) {
    if (coordinates.length < 2 || offsetMeters === 0) {
        return coordinates;
    }

    return coordinates.map((coordinate, index) => {
        let bearing;

        if (index === 0) {
            bearing = bearingBetween(coordinate, coordinates[index + 1]);
        } else if (index === coordinates.length - 1) {
            bearing = bearingBetween(coordinates[index - 1], coordinate);
        } else {
            bearing = averageBearing(
                bearingBetween(coordinates[index - 1], coordinate),
                bearingBetween(coordinate, coordinates[index + 1]),
            );
        }

        return offsetLatLng(coordinate[0], coordinate[1], bearing + 90, offsetMeters);
    });
}

export function polylineMidpoint(coordinates) {
    if (!coordinates.length) {
        return null;
    }

    if (coordinates.length === 1) {
        return coordinates[0];
    }

    const total = pathDistanceM(coordinates);
    const target = total / 2;
    let walked = 0;

    for (let index = 1; index < coordinates.length; index += 1) {
        const previous = coordinates[index - 1];
        const current = coordinates[index];
        const segmentLength = haversineDistanceM(
            previous[0],
            previous[1],
            current[0],
            current[1],
        );

        if (walked + segmentLength >= target) {
            const ratio = segmentLength === 0 ? 0 : (target - walked) / segmentLength;

            return [
                previous[0] + ((current[0] - previous[0]) * ratio),
                previous[1] + ((current[1] - previous[1]) * ratio),
            ];
        }

        walked += segmentLength;
    }

    return coordinates[coordinates.length - 1];
}

export function pointAlongPolyline(coordinates, fraction = 0.5) {
    if (!coordinates?.length) {
        return null;
    }

    if (coordinates.length === 1) {
        return {
            lat: coordinates[0][0],
            lng: coordinates[0][1],
            bearing: 0,
        };
    }

    const total = pathDistanceM(coordinates);
    const target = total * Math.max(0, Math.min(1, fraction));
    let walked = 0;

    for (let index = 1; index < coordinates.length; index += 1) {
        const previous = coordinates[index - 1];
        const current = coordinates[index];
        const segmentLength = haversineDistanceM(
            previous[0],
            previous[1],
            current[0],
            current[1],
        );
        const bearing = bearingBetween(previous, current);

        if (walked + segmentLength >= target) {
            const ratio = segmentLength === 0 ? 0 : (target - walked) / segmentLength;

            return {
                lat: previous[0] + ((current[0] - previous[0]) * ratio),
                lng: previous[1] + ((current[1] - previous[1]) * ratio),
                bearing,
            };
        }

        walked += segmentLength;
    }

    const previous = coordinates[coordinates.length - 2];
    const current = coordinates[coordinates.length - 1];

    return {
        lat: current[0],
        lng: current[1],
        bearing: bearingBetween(previous, current),
    };
}

export function resolveCoreFlowDirection(core) {
    const start = core?.ends?.start;
    const end = core?.ends?.end;
    let forwardScore = 0;
    let reverseScore = 0;

    if (start?.connection_type === 'device' && start.device_port_direction) {
        if (start.device_port_direction === 'output') {
            forwardScore += 1;
        }

        if (start.device_port_direction === 'input') {
            reverseScore += 1;
        }
    }

    if (end?.connection_type === 'device' && end.device_port_direction) {
        if (end.device_port_direction === 'input') {
            forwardScore += 1;
        }

        if (end.device_port_direction === 'output') {
            reverseScore += 1;
        }
    }

    if (forwardScore > reverseScore) {
        return 'forward';
    }

    if (reverseScore > forwardScore) {
        return 'reverse';
    }

    return null;
}

export function cableCoreFlows(cable) {
    return (cable?.cores ?? [])
        .map((core) => {
            const direction = resolveCoreFlowDirection(core);

            if (!direction) {
                return null;
            }

            return {
                coreNumber: core.core_number,
                color: core.color || fiberCoreColorForNumber(core.core_number),
                direction,
                label: core.label,
                startEnd: core.ends?.start ?? null,
                endEnd: core.ends?.end ?? null,
            };
        })
        .filter(Boolean);
}

function describeCoreEndConnection(end) {
    if (!end) {
        return '—';
    }

    if (end.connection_label) {
        return end.connection_label;
    }

    if (end.connection_type === 'device') {
        const direction = end.device_port_direction === 'input' ? 'in' : 'out';

        return `${end.device_port_label ?? 'Port'} (${direction})`;
    }

    if (end.connection_type === 'core_end') {
        return `Splice #${end.connected_core_end_id ?? '?'}`;
    }

    return 'Unconnected';
}

export function collectPointFlowPorts(cables) {
    const portsByPoint = new Map();

    cables.forEach((cable) => {
        const routePointIds = cableRoutePointIds(cable);

        if (routePointIds.length < 2) {
            return;
        }

        const startPointId = routePointIds[0];
        const endPointId = routePointIds[routePointIds.length - 1];

        (cable.cores ?? []).forEach((core) => {
            const color = core.color || fiberCoreColorForNumber(core.core_number);
            const start = core.ends?.start;
            const end = core.ends?.end;

            if (start?.connection_type === 'device' && start.device_port_direction) {
                appendPointFlowPort(portsByPoint, startPointId, {
                    direction: start.device_port_direction,
                    color,
                    label: start.device_port_label,
                    coreNumber: core.core_number,
                    coreCount: cable.core_count,
                    cableId: cable.id,
                    cableName: cable.name,
                    cableSide: 'start',
                    peerLabel: describeCoreEndConnection(end),
                });
            }

            if (end?.connection_type === 'device' && end.device_port_direction) {
                appendPointFlowPort(portsByPoint, endPointId, {
                    direction: end.device_port_direction,
                    color,
                    label: end.device_port_label,
                    coreNumber: core.core_number,
                    coreCount: cable.core_count,
                    cableId: cable.id,
                    cableName: cable.name,
                    cableSide: 'end',
                    peerLabel: describeCoreEndConnection(start),
                });
            }
        });
    });

    return portsByPoint;
}

function appendPointFlowPort(map, pointId, port) {
    const key = Number(pointId);

    if (!map.has(key)) {
        map.set(key, []);
    }

    map.get(key).push(port);
}

export function buildQuery(filters) {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.set(key, value);
        }
    });

    const query = params.toString();

    return query ? `?${query}` : '';
}

const EARTH_RADIUS_M = 6371000;

export function haversineDistanceM(lat1, lng1, lat2, lng2) {
    const toRad = (degrees) => degrees * (Math.PI / 180);
    const lat1Rad = toRad(lat1);
    const lat2Rad = toRad(lat2);
    const deltaLat = toRad(lat2 - lat1);
    const deltaLng = toRad(lng2 - lng1);
    const a = Math.sin(deltaLat / 2) ** 2
        + Math.cos(lat1Rad) * Math.cos(lat2Rad) * Math.sin(deltaLng / 2) ** 2;

    return 2 * EARTH_RADIUS_M * Math.asin(Math.min(1, Math.sqrt(a)));
}

export function pathDistanceM(coordinates) {
    let total = 0;

    for (let index = 1; index < coordinates.length; index += 1) {
        const previous = coordinates[index - 1];
        const current = coordinates[index];
        total += haversineDistanceM(previous[0], previous[1], current[0], current[1]);
    }

    return total;
}

export function cableMapDistanceM(cable, pointsById) {
    const coordinates = cablePathCoordinates(cable, pointsById);

    return coordinates.length >= 2 ? pathDistanceM(coordinates) : null;
}

export function cableMapDistanceFromRelations(cable) {
    if (!cable) {
        return null;
    }

    const pointsById = {
        [cable.from_point_id]: cable.from_point ?? cable.fromPoint,
        [cable.to_point_id]: cable.to_point ?? cable.toPoint,
    };

    return cableMapDistanceM(cable, pointsById);
}

export function formatDistanceM(meters) {
    if (meters == null || Number.isNaN(meters)) {
        return '—';
    }

    if (meters >= 1000) {
        return `${(meters / 1000).toFixed(2)} km`;
    }

    return `${Math.round(meters)} m`;
}

export const FIBER_CORE_COLOR_OPTIONS = [
    { value: '#007bff', nameKey: 'blue' },
    { value: '#fd7e14', nameKey: 'orange' },
    { value: '#28a745', nameKey: 'green' },
    { value: '#8b4513', nameKey: 'brown' },
    { value: '#6c757d', nameKey: 'slate' },
    { value: '#f8f9fa', nameKey: 'white' },
    { value: '#dc3545', nameKey: 'red' },
    { value: '#212529', nameKey: 'black' },
    { value: '#ffc107', nameKey: 'yellow' },
    { value: '#6f42c1', nameKey: 'violet' },
    { value: '#e83e8c', nameKey: 'rose' },
    { value: '#17a2b8', nameKey: 'aqua' },
];

export const FIBER_CORE_COLORS = FIBER_CORE_COLOR_OPTIONS.map((option) => option.value);

export function fiberCoreColorName(hex, options = FIBER_CORE_COLOR_OPTIONS) {
    const normalized = String(hex ?? '').toLowerCase();
    const match = options.find((option) => option.value.toLowerCase() === normalized);

    return match?.nameKey ?? null;
}

export function fiberCoreColorForNumber(coreNumber, palette = FIBER_CORE_COLORS) {
    const colors = palette?.length ? palette : FIBER_CORE_COLORS;
    const index = (Number(coreNumber) - 1) % colors.length;

    return colors[index];
}

export function emptyCoreEndForm() {
    return {
        connection_type: '',
        network_point_port_id: null,
        network_point_device_id: null,
        device_type: '',
        device_label: '',
        device_port_label: '',
        device_port_direction: 'output',
        connected_core_end_id: null,
    };
}

export function coreEndFormFromApi(end) {
    if (!end) {
        return emptyCoreEndForm();
    }

    return {
        connection_type: end.connection_type ?? '',
        network_point_port_id: end.network_point_port_id ?? null,
        network_point_device_id: end.network_point_device_id ?? null,
        device_type: end.device_type ?? '',
        device_label: end.device_label ?? '',
        device_port_label: end.device_port_label ?? '',
        device_port_direction: end.device_port_direction ?? 'output',
        connected_core_end_id: end.connected_core_end_id ?? null,
    };
}

export function coreFormFromApi(core, palette = FIBER_CORE_COLORS) {
    return {
        core_number: core.core_number,
        color: core.color ?? fiberCoreColorForNumber(core.core_number, palette),
        label: core.label ?? '',
        ends: {
            start: coreEndFormFromApi(core.ends?.start),
            end: coreEndFormFromApi(core.ends?.end),
        },
    };
}

export function buildCoresForm(count, existingCores = [], palette = FIBER_CORE_COLORS) {
    const byNumber = {};

    existingCores.forEach((core) => {
        byNumber[core.core_number] = core;
    });

    const cores = [];

    for (let number = 1; number <= count; number += 1) {
        const existing = byNumber[number];

        cores.push(existing ?? {
            core_number: number,
            color: fiberCoreColorForNumber(number, palette),
            label: '',
            ends: {
                start: emptyCoreEndForm(),
                end: emptyCoreEndForm(),
            },
        });
    }

    return cores;
}

export function preferredPortDirectionForCoreSide(sideId) {
    return sideId === 'start' ? 'output' : 'input';
}

export function isCoreEndConfigured(end) {
    if (!end?.connection_type) {
        return false;
    }

    if (end.connection_type === 'device') {
        return Boolean(end.network_point_port_id || String(end.device_port_label ?? '').trim());
    }

    if (end.connection_type === 'core_end') {
        return Boolean(end.connected_core_end_id);
    }

    return false;
}

export function listAvailableDevicePortsAtPoint(point, { direction = null, usedPortIds = null } = {}) {
    const used = usedPortIds ?? new Set();
    const matches = [];

    for (const device of point?.devices ?? []) {
        for (const port of device.ports ?? []) {
            if (!port?.id || !String(port.label ?? '').trim()) {
                continue;
            }

            if (used.has(Number(port.id))) {
                continue;
            }

            if (direction && port.direction !== direction) {
                continue;
            }

            matches.push({ device, port });
        }
    }

    return matches;
}

export function suggestDevicePortEnd(sideId, point, usedPortIds) {
    const preferred = preferredPortDirectionForCoreSide(sideId);
    let match = listAvailableDevicePortsAtPoint(point, { direction: preferred, usedPortIds }).at(0);

    if (!match) {
        match = listAvailableDevicePortsAtPoint(point, { usedPortIds }).at(0);
    }

    if (!match) {
        return null;
    }

    const { device, port } = match;

    return {
        connection_type: 'device',
        network_point_port_id: port.id,
        network_point_device_id: device.id ?? null,
        device_type: device.type ?? '',
        device_label: device.label ?? '',
        device_port_label: port.label ?? '',
        device_port_direction: port.direction ?? preferred,
        connected_core_end_id: null,
    };
}

/**
 * Auto-fill unconfigured core ends from route start/end points and availability.
 */
export function applyAutoCoreEndDefaults(cores, cable, cables, pointsById, { preserveExisting = true } = {}) {
    const routeIds = cableRoutePointIds(cable);

    if (routeIds.length < 2) {
        return cores;
    }

    const startPointId = routeIds[0];
    const endPointId = routeIds[routeIds.length - 1];
    const dbUsedPorts = collectUsedPortIds(cables);
    const formUsedPorts = new Set();

    for (const core of cores) {
        for (const sideId of ['start', 'end']) {
            const end = core.ends?.[sideId];

            if (!end) {
                continue;
            }

            if (preserveExisting && isCoreEndConfigured(end)) {
                if (end.network_point_port_id) {
                    formUsedPorts.add(Number(end.network_point_port_id));
                }

                continue;
            }

            // Leave multi-core sides open for manual wiring (e.g. two fibers at customer ONT).
            if (cores.length > 1) {
                continue;
            }

            const pointId = sideId === 'start' ? startPointId : endPointId;
            const point = pointsById[pointId];
            const usedPortIds = new Set([...dbUsedPorts.keys(), ...formUsedPorts]);
            const deviceEnd = suggestDevicePortEnd(sideId, point, usedPortIds);

            if (deviceEnd) {
                Object.assign(end, deviceEnd);
                formUsedPorts.add(Number(deviceEnd.network_point_port_id));
                continue;
            }

            const spliceOptions = collectCoreConnectionOptionsAtPoint(cables, pointId, {})
                .filter((option) => isCoreEndAvailableForSplice(option, {}));
            const spliceOption = spliceOptions[Number(core.core_number) - 1];

            if (spliceOption) {
                Object.assign(end, {
                    ...emptyCoreEndForm(),
                    connection_type: 'core_end',
                    connected_core_end_id: spliceOption.id,
                });
                continue;
            }

            Object.assign(end, emptyCoreEndForm());
        }
    }

    return cores;
}

export function hasDefinedPortsAtPoint(point, direction = null) {
    for (const device of point?.devices ?? []) {
        for (const port of device.ports ?? []) {
            if (!String(port?.label ?? '').trim()) {
                continue;
            }

            if (direction && port.direction !== direction) {
                continue;
            }

            return true;
        }
    }

    return false;
}

export function sideConnectionModes(point, cables, pointId, sideId = null) {
    const modes = [];
    const preferred = sideId ? preferredPortDirectionForCoreSide(sideId) : null;
    const hasPreferredPorts = preferred ? hasDefinedPortsAtPoint(point, preferred) : false;
    const hasAnyPorts = hasDefinedPortsAtPoint(point, null);

    if (hasPreferredPorts || hasAnyPorts) {
        modes.push('device');

        return modes;
    }

    const hasSplices = collectCoreConnectionOptionsAtPoint(cables, pointId, {})
        .some((option) => isCoreEndAvailableForSplice(option, {}));

    if (hasSplices) {
        modes.push('core_end');
    }

    return modes;
}

export function collectCoreConnectionOptionsAtPoint(cables, pointId, { excludeEndId = null } = {}) {
    if (!pointId) {
        return [];
    }

    const options = [];

    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            for (const side of ['start', 'end']) {
                const end = core.ends?.[side];

                if (!end?.id) {
                    continue;
                }

                if (excludeEndId && Number(end.id) === Number(excludeEndId)) {
                    continue;
                }

                if (!isCoreSideAtRoutePoint(cable, side, pointId)) {
                    continue;
                }

                const endPointId = resolvedCoreEndPointId(cable, side);

                if (endPointId == null || Number(endPointId) !== Number(pointId)) {
                    continue;
                }

                options.push({
                    id: end.id,
                    cable_id: cable.id,
                    cable_name: cable.name ?? `Cable #${cable.id}`,
                    cable_core_count: cable.core_count ?? null,
                    core_number: core.core_number,
                    core_label: core.label ?? '',
                    side: end.side ?? side,
                    network_point_id: endPointId,
                    network_point_name: end.network_point_name ?? null,
                    connection_type: end.connection_type ?? null,
                    connected_core_end_id: end.connected_core_end_id ?? null,
                    connection_label: end.connection_label ?? null,
                });
            }
        }
    }

    return options.sort((left, right) => {
        const cableCompare = String(left.cable_name).localeCompare(String(right.cable_name));

        if (cableCompare !== 0) {
            return cableCompare;
        }

        if (left.core_number !== right.core_number) {
            return left.core_number - right.core_number;
        }

        if (left.side === right.side) {
            return 0;
        }

        return left.side === 'start' ? -1 : 1;
    });
}

export function formatCoreConnectionOption(option, translate = null) {
    const cable = option.cable_name ?? `Cable #${option.cable_id}`;
    const coreCount = option.cable_core_count ? `${option.cable_core_count}C · ` : '';
    const coreName = option.core_label?.trim()
        ? `Core ${option.core_number} (${option.core_label.trim()})`
        : `Core ${option.core_number}`;
    const sideKey = option.side === 'start' ? 'cables.endStart' : 'cables.endFinish';
    const sideLabel = translate?.(sideKey) ?? (option.side === 'start' ? 'Start end' : 'End end');
    const point = option.network_point_name ? ` · ${option.network_point_name}` : '';
    let label = `${cable} · ${coreCount}${coreName} · ${sideLabel}${point}`;

    if (option.connection_type === 'core_end' && option.connection_label) {
        const linkedLabel = translate?.('cables.coreEndLinked', { target: option.connection_label })
            ?? ` → ${option.connection_label}`;

        label += linkedLabel;
    } else if (option.connection_type === 'device' && option.connection_label) {
        const linkedLabel = translate?.('cables.coreEndLinked', { target: option.connection_label })
            ?? ` → ${option.connection_label}`;

        label += linkedLabel;
    }

    return label;
}

export function formatDevicePortOption(port, device, translate = null, pointName = null, typeLabels = {}) {
    const direction = port.direction === 'input'
        ? (translate?.('cables.portInput') ?? 'Input')
        : (translate?.('cables.portOutput') ?? 'Output');
    const typeLabel = typeLabels[device?.type] ?? device?.type ?? '';
    const deviceName = device?.label?.trim() || (translate?.('cables.devicePortLabel') ?? 'Device');
    const parts = [pointName, typeLabel, deviceName, `${port.label} · ${direction}`].filter(Boolean);

    return parts.join(' · ');
}

export function formatDeviceConnectionSummary(end, side, typeLabels = {}, translate = null) {
    if (end?.connection_type !== 'device') {
        return null;
    }

    const direction = end.device_port_direction === 'input'
        ? (translate?.('cables.portInput') ?? 'Input')
        : (translate?.('cables.portOutput') ?? 'Output');
    const typeLabel = typeLabels[end.device_type] ?? end.device_type_label ?? end.device_type ?? '';
    const parts = [
        side?.pointLabel,
        typeLabel,
        end.device_label,
        end.device_port_label ? `${end.device_port_label} · ${direction}` : direction,
    ].filter(Boolean);

    return parts.join(' · ');
}

export function serializeCoreEndForm(end) {
    if (end.connection_type === 'device') {
        const portId = end.network_point_port_id ? Number(end.network_point_port_id) : null;

        if (portId) {
            return {
                connection_type: 'device',
                network_point_port_id: portId,
            };
        }

        return {
            connection_type: 'device',
            device_port_label: end.device_port_label?.trim() || null,
            device_port_direction: end.device_port_direction || 'output',
            device_type: end.device_type?.trim() || null,
            device_label: end.device_label?.trim() || null,
        };
    }

    if (end.connection_type === 'core_end' && end.connected_core_end_id) {
        return {
            connection_type: 'core_end',
            connected_core_end_id: Number(end.connected_core_end_id),
        };
    }

    return {
        connection_type: null,
    };
}

function buildPointsById(points) {
    const map = {};

    (points ?? []).forEach((point) => {
        map[point.id] = point;
    });

    return map;
}

function portLabelFromEnd(end, pointsById) {
    if (!end) {
        return '—';
    }

    const point = pointsById[end.network_point_id];
    const device = point?.devices?.find((item) => (
        item.ports?.some((port) => Number(port.id) === Number(end.network_point_port_id))
    ));
    const port = device?.ports?.find((item) => Number(item.id) === Number(end.network_point_port_id));

    return port?.label ?? end.device_port_label ?? '—';
}

function pointNameFromId(pointId, pointsById) {
    return pointsById[pointId]?.name ?? `Point #${pointId}`;
}

function coreEndOppositeSide(side) {
    return side === 'start' ? 'end' : 'start';
}

function findDeviceConnectedCoreEnd(core, pointId, portId, portDirection) {
    for (const side of ['start', 'end']) {
        const deviceEnd = core.ends?.[side];

        if (
            deviceEnd?.connection_type === 'device'
            && Number(deviceEnd.network_point_id) === Number(pointId)
            && Number(deviceEnd.network_point_port_id) === Number(portId)
            && deviceEnd.device_port_direction === portDirection
        ) {
            const farSide = coreEndOppositeSide(side);

            return {
                side,
                deviceEnd,
                farEnd: core.ends?.[farSide],
            };
        }
    }

    return null;
}

function findIncomingCoreAtPort(cables, pointId, portId) {
    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            const match = findDeviceConnectedCoreEnd(core, pointId, portId, 'input');

            if (match) {
                return {
                    cable,
                    core,
                    deviceSide: match.side,
                    deviceEnd: match.deviceEnd,
                    farEnd: match.farEnd,
                };
            }
        }
    }

    return null;
}

function findOutgoingCoresFromPort(cables, pointId, portId) {
    const matches = [];

    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            const match = findDeviceConnectedCoreEnd(core, pointId, portId, 'output');

            if (match) {
                matches.push({
                    cable,
                    core,
                    deviceSide: match.side,
                    deviceEnd: match.deviceEnd,
                    end: match.farEnd,
                });
            }
        }
    }

    return matches;
}

function findTrunkIntoSplitter(cables, splitterPointId) {
    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            for (const side of ['start', 'end']) {
                const deviceEnd = core.ends?.[side];

                if (
                    deviceEnd?.connection_type === 'device'
                    && Number(deviceEnd.network_point_id) === Number(splitterPointId)
                    && deviceEnd.device_port_direction === 'input'
                    && String(deviceEnd.device_port_label ?? '').toUpperCase() === 'IN'
                ) {
                    const farSide = coreEndOppositeSide(side);

                    return {
                        cable,
                        core,
                        deviceSide: side,
                        deviceEnd,
                        farEnd: core.ends?.[farSide],
                    };
                }
            }
        }
    }

    return null;
}

function buildHop(
    pointId,
    portLabel,
    portDirection,
    pointsById,
    deviceLabel = null,
    pointType = null,
    deviceType = null,
) {
    return {
        kind: 'point',
        pointId: Number(pointId),
        pointName: pointNameFromId(pointId, pointsById),
        pointType: pointType ?? null,
        portLabel,
        portDirection,
        deviceLabel: deviceLabel?.trim() || null,
        deviceType: deviceType ?? null,
    };
}

function signalPortDirectionLabel(direction, translate = null) {
    return direction === 'input'
        ? (translate?.('cables.portInput') ?? 'Input')
        : (translate?.('cables.portOutput') ?? 'Output');
}

function resolveTypeLabel(type, typeLabels = {}) {
    if (!type) {
        return null;
    }

    return typeLabels[type] ?? pointTypeLabel(type);
}

function formatPointCaption(point, typeLabels = {}) {
    const name = point?.name ?? (point?.id ? `Point #${point.id}` : 'Point');
    const type = resolveTypeLabel(primaryPointType(point), typeLabels);

    return type ? `${name} (${type})` : name;
}

function formatDeviceCaption(device, typeLabels = {}) {
    if (!device) {
        return null;
    }

    const type = resolveTypeLabel(device.type, typeLabels);

    if (device.label?.trim() && type) {
        return `${device.label.trim()} (${type})`;
    }

    return device.label?.trim() || type || null;
}

function formatHopPointCaption(hop, typeLabels = {}) {
    if (!hop || hop.kind !== 'point') {
        return hop?.label ?? '';
    }

    const type = resolveTypeLabel(hop.pointType, typeLabels);
    const name = hop.pointName ?? `Point #${hop.pointId}`;

    return type ? `${name} (${type})` : name;
}

function formatHopDeviceCaption(hop, typeLabels = {}) {
    if (!hop?.deviceLabel && !hop?.deviceType) {
        return null;
    }

    const type = resolveTypeLabel(hop.deviceType, typeLabels);

    if (hop.deviceLabel && type) {
        return `${hop.deviceLabel} (${type})`;
    }

    return hop.deviceLabel || type || null;
}

/**
 * Short label for signal path cards — device name and point/device type only.
 */
function signalPathCardLabel(hop, pointsById = {}, typeLabels = {}) {
    if (!hop || hop.kind !== 'point') {
        return hop?.label ?? 'Path';
    }

    const point = pointsById[hop.pointId];
    const deviceName = hop.deviceLabel?.trim();
    const type = resolveTypeLabel(
        hop.deviceType ?? hop.pointType ?? primaryPointType(point),
        typeLabels,
    );

    if (deviceName && type) {
        return `${deviceName} (${type})`;
    }

    if (deviceName) {
        return deviceName;
    }

    if (type) {
        return type;
    }

    return hop.pointName ?? `Point #${hop.pointId}`;
}

/**
 * Human-readable caption for a signal path point hop.
 */
export function formatSignalPointHop(hop, typeLabels = {}, translate = null) {
    if (hop?.kind !== 'point') {
        return hop?.label ?? '';
    }

    const parts = [formatHopPointCaption(hop, typeLabels)];
    const deviceCaption = formatHopDeviceCaption(hop, typeLabels);

    if (deviceCaption) {
        parts.push(deviceCaption);
    }

    parts.push(`${hop.portLabel ?? '—'} (${signalPortDirectionLabel(hop.portDirection, translate)})`);

    return parts.join(' · ');
}

function formatSignalEndpointCaption(hop, typeLabels = {}, translate = null, includePortDirection = true) {
    if (!hop || hop.kind !== 'point') {
        return hop?.label ?? '';
    }

    const parts = [formatHopPointCaption(hop, typeLabels)];
    const deviceCaption = formatHopDeviceCaption(hop, typeLabels);

    if (deviceCaption) {
        parts.push(deviceCaption);
    }

    if (hop.portLabel) {
        if (includePortDirection) {
            parts.push(`${hop.portLabel} (${signalPortDirectionLabel(hop.portDirection, translate)})`);
        } else {
            parts.push(hop.portLabel);
        }
    }

    return parts.join(' · ');
}

function buildHopFromPort(pointId, portId, pointsById) {
    const point = pointsById[pointId];
    const port = portFromPoint(point, portId);
    const device = deviceForPort(point, portId);

    return buildHop(
        pointId,
        port?.label ?? '—',
        port?.direction ?? 'input',
        pointsById,
        device?.label ?? null,
        primaryPointType(point),
        device?.type ?? null,
    );
}

function buildHopFromCoreEnd(end, pointsById) {
    const point = pointsById[end.network_point_id];
    const device = deviceForPort(point, end.network_point_port_id);

    return buildHop(
        Number(end.network_point_id),
        portLabelFromEnd(end, pointsById),
        end.device_port_direction ?? 'output',
        pointsById,
        end.device_label ?? device?.label ?? null,
        primaryPointType(point),
        end.device_type ?? device?.type ?? null,
    );
}

function signalEndpointLabel(hop, typeLabels = {}, translate = null) {
    return signalPathCardLabel(hop, {}, typeLabels);
}

function findCoreEndById(cables, endId) {
    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            for (const side of ['start', 'end']) {
                const end = core.ends?.[side];

                if (end && Number(end.id) === Number(endId)) {
                    return { cable, core, side, end };
                }
            }
        }
    }

    return null;
}

function connectionText(end, pointsById = null) {
    return formatCoreEndHopLabel(end, pointsById);
}

function formatCoreEndHopLabel(end, pointsById) {
    if (!end) {
        return '—';
    }

    if (end.connection_type === 'device') {
        return end.connection_label ?? describeCoreEndConnection(end);
    }

    if (end.connection_type === 'core_end' && pointsById) {
        return `${pointNameFromId(end.network_point_id, pointsById)} · splice`;
    }

    return end.connection_label ?? describeCoreEndConnection(end);
}

/**
 * Map of port ID → connection info for ports already wired to a cable core.
 */
export function collectUsedPortIds(cables, { excludeEndId = null } = {}) {
    const used = new Map();

    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            for (const side of ['start', 'end']) {
                const end = core.ends?.[side];

                if (
                    end?.connection_type !== 'device'
                    || !end.network_point_port_id
                ) {
                    continue;
                }

                if (excludeEndId && Number(end.id) === Number(excludeEndId)) {
                    continue;
                }

                used.set(Number(end.network_point_port_id), {
                    endId: end.id,
                    cableId: cable.id,
                    cableName: cable.name ?? `Cable #${cable.id}`,
                    coreNumber: core.core_number,
                    side: end.side ?? side,
                });
            }
        }
    }

    return used;
}

export function formatPortCableConnection(connection, translate = null) {
    if (!connection) {
        return null;
    }

    const sideKey = connection.side === 'start' ? 'cables.endStart' : 'cables.endFinish';
    const sideLabel = translate?.(sideKey) ?? (connection.side === 'start' ? 'Start end' : 'End end');
    const coreName = connection.coreLabel?.trim()
        ? `Core ${connection.coreNumber} (${connection.coreLabel.trim()})`
        : `Core ${connection.coreNumber}`;
    const count = connection.coreCount ? `${connection.coreCount}C · ` : '';

    return `${connection.cableName} · ${count}${coreName} · ${sideLabel}`;
}

/**
 * Find the cable core wired to a device port at a point (at most one).
 */
export function findPortConnection(cables, pointId, portId) {
    const matches = findPortConnections(cables, pointId, portId);

    return matches[0] ?? null;
}

/**
 * Find cable core connections wired to a device port at a point.
 *
 * @returns {Array<object>}
 */
export function findPortConnections(cables, pointId, portId) {
    const matches = [];

    for (const cable of cables ?? []) {
        for (const core of cable.cores ?? []) {
            for (const side of ['start', 'end']) {
                const end = core.ends?.[side];

                if (
                    end?.connection_type === 'device'
                    && Number(end.network_point_id) === Number(pointId)
                    && Number(end.network_point_port_id) === Number(portId)
                ) {
                    const otherSide = side === 'start' ? 'end' : 'start';

                    matches.push({
                        endId: end.id,
                        cableId: cable.id,
                        cableName: cable.name ?? `Cable #${cable.id}`,
                        coreCount: cable.core_count ?? null,
                        coreNumber: core.core_number,
                        coreLabel: core.label ?? '',
                        side: end.side ?? side,
                        peerLabel: describeCoreEndConnection(core.ends?.[otherSide]),
                    });
                }
            }
        }
    }

    return matches.slice(0, 1);
}

export function isCoreEndAvailableForSplice(option, { currentEndId = null, currentPartnerId = null } = {}) {
    if (currentPartnerId && Number(option.id) === Number(currentPartnerId)) {
        return true;
    }

    if (currentEndId && Number(option.id) === Number(currentEndId)) {
        return false;
    }

    if (option.connection_type === 'device') {
        if (!option.network_point_port_id && !String(option.device_port_label ?? '').trim()) {
            return true;
        }

        return false;
    }

    if (option.connection_type === 'core_end') {
        if (!option.connected_core_end_id) {
            return true;
        }

        if (currentEndId && option.connected_core_end_id && Number(option.connected_core_end_id) === Number(currentEndId)) {
            return true;
        }

        return false;
    }

    return true;
}

/**
 * Count open (unspliced) core ends for a cable at a route point.
 */
export function countAvailableCoreEndsForCableAtPoint(cables, cableId, pointId, {
    reservedEndIds = new Set(),
    currentEndId = null,
    currentPartnerId = null,
} = {}) {
    if (!cableId || !pointId) {
        return 0;
    }

    const cable = (cables ?? []).find((item) => Number(item.id) === Number(cableId));

    if (!cable) {
        return 0;
    }

    let count = 0;

    for (const core of cable.cores ?? []) {
        for (const side of ['start', 'end']) {
            const end = core.ends?.[side];

            if (!end?.id) {
                continue;
            }

            if (!isCoreSideAtRoutePoint(cable, side, pointId)) {
                continue;
            }

            const endPointId = resolvedCoreEndPointId(cable, side);

            if (endPointId == null || Number(endPointId) !== Number(pointId)) {
                continue;
            }

            if (reservedEndIds.has(Number(end.id))) {
                continue;
            }

            if (isCoreEndAvailableForSplice({
                ...end,
                id: end.id,
                cable_id: cable.id,
            }, { currentEndId, currentPartnerId })) {
                count += 1;
            }
        }
    }

    return count;
}

/**
 * How many cores on this cable side still need a splice or device connection.
 */
export function countCoresNeedingSpliceOnSide(cores, sideId) {
    return (cores ?? []).filter((core) => !isCoreEndConfigured(core.ends?.[sideId])).length;
}

/**
 * Keep only splice targets on cables with enough remaining open cores at the junction.
 */
export function filterCoreSpliceOptionsByCableCapacity(options, {
    cables,
    pointId,
    coresNeedingSplice = 1,
    reservedEndIds = new Set(),
    currentEndId = null,
    currentPartnerId = null,
} = {}) {
    const needed = Math.max(1, Number(coresNeedingSplice) || 1);

    return (options ?? []).filter((option) => {
        if (!isCoreEndAvailableForSplice(option, { currentEndId, currentPartnerId })) {
            return false;
        }

        if (reservedEndIds.has(Number(option.id))) {
            return Number(option.id) === Number(currentPartnerId);
        }

        const availableOnCable = countAvailableCoreEndsForCableAtPoint(
            cables,
            option.cable_id,
            pointId,
            { reservedEndIds, currentEndId, currentPartnerId },
        );

        return availableOnCable >= needed || Number(option.id) === Number(currentPartnerId);
    });
}

/**
 * @returns {Array<{ cable_id: number, cable_name: string, available: number, options: Array<object> }>}
 */
export function groupCoreConnectionOptionsByCable(options, cables, pointId, {
    reservedEndIds = new Set(),
    currentEndId = null,
    currentPartnerId = null,
} = {}) {
    const groups = new Map();

    for (const option of options ?? []) {
        if (!groups.has(option.cable_id)) {
            groups.set(option.cable_id, {
                cable_id: option.cable_id,
                cable_name: option.cable_name ?? `Cable #${option.cable_id}`,
                available: countAvailableCoreEndsForCableAtPoint(
                    cables,
                    option.cable_id,
                    pointId,
                    { reservedEndIds, currentEndId, currentPartnerId },
                ),
                options: [],
            });
        }

        groups.get(option.cable_id).options.push(option);
    }

    return Array.from(groups.values()).sort((left, right) => (
        String(left.cable_name).localeCompare(String(right.cable_name))
    ));
}

function buildSpliceHop(fromCable, fromCore, toCable, toCore, pointsById, junctionPointId) {
    return {
        kind: 'splice',
        pointName: pointNameFromId(junctionPointId, pointsById),
        label: `${fromCable.name ?? 'Cable'} · Core ${fromCore.core_number} ↔ ${toCable.name ?? 'Cable'} · Core ${toCore.core_number}`,
    };
}

function buildCableHop(cable, core, pointsById) {
    const start = core.ends?.start;
    const end = core.ends?.end;

    return {
        kind: 'cable',
        cableId: cable.id,
        cableName: cable.name ?? `Cable #${cable.id}`,
        coreNumber: core.core_number,
        coreColor: core.color,
        coreCount: cable.core_count,
        fromName: pointNameFromId(start?.network_point_id, pointsById),
        toName: pointNameFromId(end?.network_point_id, pointsById),
        fromPort: formatCoreEndHopLabel(start, pointsById),
        toPort: formatCoreEndHopLabel(end, pointsById),
    };
}

function portFromPoint(point, portId) {
    for (const device of point?.devices ?? []) {
        const port = (device.ports ?? []).find((item) => Number(item.id) === Number(portId));

        if (port) {
            return port;
        }
    }

    return null;
}

function deviceForPort(point, portId) {
    for (const device of point?.devices ?? []) {
        if ((device.ports ?? []).some((port) => Number(port.id) === Number(portId))) {
            return device;
        }
    }

    return null;
}

function inputPortsForPoint(point) {
    return (point?.devices ?? []).flatMap((device) => (
        (device.ports ?? [])
            .filter((port) => port.direction === 'input')
            .map((port) => ({ port, device }))
    ));
}
function findPortByLabel(point, label) {
    for (const device of point?.devices ?? []) {
        const match = (device.ports ?? []).find((port) => String(port.label).toUpperCase() === String(label).toUpperCase());

        if (match) {
            return match;
        }
    }

    return null;
}

function isPointType(point, type) {
    const types = point?.types ?? (point?.type ? [point.type] : []);

    return types.includes(type);
}

function isControlRoomPoint(point) {
    if (!point) {
        return false;
    }

    const types = normalizePointTypes(point);
    const name = String(point?.name ?? '').toLowerCase();

    return types.some((type) => ['odf', 'cabinet', 'uplink', 'bras', 'switch'].includes(type))
        || name.includes('control room')
        || name.includes('noc');
}

/** Ultimate ISP handoff — top of the signal chain. */
function isUplinkHeadEndPoint(point) {
    return isPointType(point, 'uplink');
}

/** Fiber distribution head-end (ODF / MUX at control room). */
function isFiberHeadEndPoint(point) {
    return isPointType(point, 'odf');
}

/** Legacy / named control-room cabinet (without upstream chain). */
function isNetworkHeadEndPoint(point) {
    if (!point) {
        return false;
    }

    if (isUplinkHeadEndPoint(point)) {
        return true;
    }

    const name = String(point.name ?? '').toLowerCase();

    if (isPointType(point, 'cabinet') && (name.includes('noc') || name.includes('control room'))) {
        return true;
    }

    return (name.includes('noc') || name.includes('control room')) && !isPointType(point, 'odf');
}

/** IP aggregation layer — continue tracing upstream toward RailTel. */
function isUpstreamInfrastructurePoint(point) {
    if (!point) {
        return false;
    }

    return isPointType(point, 'bras')
        || isPointType(point, 'switch')
        || isPointType(point, 'dwdm')
        || isPointType(point, 'cabinet');
}

/** Active sources (OLT/router) — continue tracing upstream toward the NOC when possible. */
function isActiveSignalSourcePoint(point) {
    if (!point) {
        return false;
    }

    const name = String(point.name ?? '').toLowerCase();

    return isPointType(point, 'router') || name.includes('olt');
}

function isSignalOriginPoint(point) {
    return isNetworkHeadEndPoint(point) || isActiveSignalSourcePoint(point);
}

function isPassiveDistributionPoint(point) {
    return isPointType(point, 'splitter') || isPointType(point, 'junction');
}

function findUpstreamTrunkPort(cables, pointId, pointsById) {
    const point = pointsById[pointId];
    const inputPorts = (point?.devices ?? [])
        .flatMap((device) => device.ports ?? [])
        .filter((port) => port.direction === 'input');

    for (const port of inputPorts) {
        const incoming = findIncomingCoreAtPort(cables, pointId, port.id);

        if (incoming) {
            return { port, incoming };
        }
    }

    return null;
}

function appendCableUpstreamSegment(incoming, hops, visited, pointsById, cables) {
    hops.push(buildCableHop(incoming.cable, incoming.core, pointsById));

    let sourceEnd = incoming.farEnd;

    while (sourceEnd?.connection_type === 'core_end' && sourceEnd.connected_core_end_id) {
        const linked = findCoreEndById(cables, sourceEnd.connected_core_end_id);

        if (!linked || visited.has(`e:${sourceEnd.id}:${linked.end.id}`)) {
            break;
        }

        visited.add(`e:${sourceEnd.id}:${linked.end.id}`);

        hops.push(buildSpliceHop(
            incoming.cable,
            incoming.core,
            linked.cable,
            linked.core,
            pointsById,
            sourceEnd.network_point_id,
        ));

        hops.push(buildCableHop(linked.cable, linked.core, pointsById));

        const continueSide = coreEndOppositeSide(linked.side);
        sourceEnd = linked.core.ends?.[continueSide];
        incoming = { cable: linked.cable, core: linked.core, farEnd: sourceEnd };
    }

    return sourceEnd;
}

function tryAdvanceThroughTrunkPort(cables, pointsById, pointId, hops, visited) {
    const trunkPort = findUpstreamTrunkPort(cables, pointId, pointsById);

    if (!trunkPort) {
        return null;
    }

    const inputVisitKey = `p:${pointId}:${trunkPort.port.id}`;

    if (visited.has(inputVisitKey)) {
        return null;
    }

    visited.add(inputVisitKey);
    pushPointHop(hops, pointId, trunkPort.port.id, pointsById);

    let sourceEnd = appendCableUpstreamSegment(trunkPort.incoming, hops, visited, pointsById, cables);
    const sourcePointId = Number(sourceEnd?.network_point_id);
    const sourcePoint = pointsById[sourcePointId];

    if (sourceEnd?.connection_type === 'device') {
        pushDeviceEndHop(hops, sourceEnd, pointsById);
    }

    return { sourceEnd, sourcePointId, sourcePoint };
}

function headEndTraceResultIfReached(hops, sourcePoint) {
    if (isUplinkHeadEndPoint(sourcePoint)) {
        return {
            hops,
            receivingSignal: true,
            originName: sourcePoint?.name ?? null,
            partialPath: false,
        };
    }

    if (isNetworkHeadEndPoint(sourcePoint) && ! isFiberHeadEndPoint(sourcePoint)) {
        return {
            hops,
            receivingSignal: true,
            originName: sourcePoint?.name ?? null,
            partialPath: false,
        };
    }

    return null;
}

function pushPointHop(hops, pointId, portId, pointsById) {
    hops.push(buildHopFromPort(pointId, portId, pointsById));
}

function pushDeviceEndHop(hops, sourceEnd, pointsById) {
    hops.push(buildHopFromCoreEnd(sourceEnd, pointsById));
}

function finishUpstreamTrace(hops, pointsById) {
    const reversed = [...hops].reverse();
    const uplinkHop = reversed.find((hop) => (
        hop.kind === 'point' && isUplinkHeadEndPoint(pointsById[hop.pointId])
    ));

    if (uplinkHop) {
        return {
            hops,
            receivingSignal: true,
            originName: uplinkHop.pointName ?? null,
            partialPath: false,
        };
    }

    const fiberHeadHop = reversed.find((hop) => (
        hop.kind === 'point' && isFiberHeadEndPoint(pointsById[hop.pointId])
    ));

    if (fiberHeadHop) {
        return {
            hops,
            receivingSignal: true,
            originName: fiberHeadHop.pointName ?? null,
            partialPath: false,
        };
    }

    const legacyHeadHop = reversed.find((hop) => (
        hop.kind === 'point' && isNetworkHeadEndPoint(pointsById[hop.pointId])
    ));

    if (legacyHeadHop) {
        return {
            hops,
            receivingSignal: true,
            originName: legacyHeadHop.pointName ?? null,
            partialPath: false,
        };
    }

    const activeHop = reversed.find((hop) => (
        hop.kind === 'point' && isActiveSignalSourcePoint(pointsById[hop.pointId])
    ));

    return {
        hops,
        receivingSignal: false,
        originName: activeHop?.pointName ?? null,
        partialPath: hops.length > 0,
    };
}

export function signalFlowOrder(hops) {
    return [...(hops ?? [])].reverse();
}

export function oltHopFromFlowHops(flowHops, points) {
    const pointsById = buildPointsById(points);

    return (flowHops ?? []).find((hop) => (
        hop.kind === 'point' && isActiveSignalSourcePoint(pointsById[hop.pointId])
    )) ?? null;
}

export function isOltNetworkPoint(point) {
    if (!point) {
        return false;
    }

    const name = String(point.name ?? '').toLowerCase();

    return isPointType(point, 'router') || name.includes('olt');
}

export function isOltDevice(device) {
    if (!device) {
        return false;
    }

    const name = String(device.label ?? '').toLowerCase();

    return device.type === 'router' || name.includes('olt');
}

export function oltDevicesOnPoint(point) {
    const devices = (point?.devices ?? []).filter(isOltDevice);

    if (devices.length) {
        return devices;
    }

    if (isOltNetworkPoint(point)) {
        return point?.devices ?? [];
    }

    return [];
}

function outputPortsWithDevice(point) {
    return (point?.devices ?? []).flatMap((device) => (
        (device.ports ?? [])
            .filter((port) => port.direction === 'output')
            .map((port) => ({ port, device }))
    ));
}

function buildUpstreamPathEntry(pointId, port, device, cables, points, pointsById, typeLabels) {
    const point = pointsById[pointId];
    const result = traceSignalUpstreamFromPort(pointId, port.id, cables, points);
    const flowHops = signalFlowOrder(result.hops);
    const remotePointHops = flowHops.filter((hop) => (
        hop.kind === 'point' && hop.pointId !== Number(pointId)
    ));
    const destinationHop = remotePointHops.find((hop) => isUplinkHeadEndPoint(pointsById[hop.pointId]))
        ?? remotePointHops.find((hop) => isFiberHeadEndPoint(pointsById[hop.pointId]))
        ?? remotePointHops.find((hop) => isNetworkHeadEndPoint(pointsById[hop.pointId]))
        ?? remotePointHops.find((hop) => isActiveSignalSourcePoint(pointsById[hop.pointId]))
        ?? remotePointHops[0];

    return {
        label: upstreamPathLabel(point, device, typeLabels),
        destinationLabel: destinationHop
            ? signalPathCardLabel(destinationHop, pointsById, typeLabels)
            : null,
        portId: port.id,
        deviceId: device?.id ?? null,
        deviceLabel: device?.label ?? null,
        deviceType: device?.type ?? null,
        unwired: false,
        ...result,
    };
}

function createUnwiredUpstreamPathEntry(point, device, typeLabels) {
    return {
        label: upstreamPathLabel(point, device, typeLabels),
        destinationLabel: null,
        portId: null,
        deviceId: device?.id ?? null,
        deviceLabel: device?.label ?? null,
        deviceType: device?.type ?? null,
        hops: [],
        receivingSignal: false,
        originName: null,
        partialPath: false,
        unwired: true,
    };
}

export function isHeadEndNetworkPoint(point) {
    if (!point) {
        return false;
    }

    const types = normalizePointTypes(point);
    const name = String(point.name ?? '').toLowerCase();

    if (isOltNetworkPoint(point)) {
        return false;
    }

    return types.some((type) => ['cabinet', 'uplink', 'bras', 'switch', 'odf', 'dwdm'].includes(type))
        || name.includes('control room')
        || name.includes('noc')
        || name.includes('railtel');
}

export function traceMetaFromHops(hops) {
    const flowHops = signalFlowOrder(hops);

    return {
        flowHops,
        cableIds: flowHops.filter((hop) => hop.kind === 'cable').map((hop) => hop.cableId),
        pointIds: flowHops.filter((hop) => hop.kind === 'point').map((hop) => hop.pointId),
    };
}

function appendPathCoord(path, coord) {
    if (!coord) {
        return;
    }

    const last = path[path.length - 1];

    if (last && Math.abs(last[0] - coord[0]) < 1e-7 && Math.abs(last[1] - coord[1]) < 1e-7) {
        return;
    }

    path.push(coord);
}

export function buildSignalPathCoordinates(flowHops, cables, points) {
    const pointsById = buildPointsById(points);
    const path = [];

    (flowHops ?? []).forEach((hop, index) => {
        if (hop.kind === 'point') {
            const point = pointsById[hop.pointId];

            if (point) {
                appendPathCoord(path, [Number(point.latitude), Number(point.longitude)]);
            }

            return;
        }

        if (hop.kind !== 'cable') {
            return;
        }

        const cable = (cables ?? []).find((item) => String(item.id) === String(hop.cableId));

        if (!cable) {
            return;
        }

        let coords = cablePathCoordinates(cable, pointsById);

        if (coords.length < 2) {
            return;
        }

        const prevHop = flowHops[index - 1];
        const nextHop = flowHops[index + 1];
        const routeIds = cableRoutePointIds(cable);
        const prevPointId = prevHop?.kind === 'point' ? Number(prevHop.pointId) : null;
        const nextPointId = nextHop?.kind === 'point' ? Number(nextHop.pointId) : null;

        if (prevPointId && routeIds.length) {
            const startIdx = routeIds.indexOf(prevPointId);
            const endIdx = nextPointId ? routeIds.indexOf(nextPointId) : -1;

            if (startIdx >= 0 && endIdx >= 0 && startIdx > endIdx) {
                coords = [...coords].reverse();
            } else if (prevPointId === routeIds[routeIds.length - 1]) {
                coords = [...coords].reverse();
            }
        }

        coords.forEach((coord) => appendPathCoord(path, coord));
    });

    return path;
}

export function positionAlongPath(coords, progress) {
    if (!coords?.length) {
        return null;
    }

    if (coords.length === 1) {
        return { position: coords[0], bearing: 0 };
    }

    const clamped = Math.max(0, Math.min(1, progress));
    const segments = [];
    let total = 0;

    for (let index = 0; index < coords.length - 1; index += 1) {
        const from = coords[index];
        const to = coords[index + 1];
        const length = Math.hypot(to[0] - from[0], to[1] - from[1]);

        segments.push({ length, from, to });
        total += length;
    }

    if (total === 0) {
        return { position: coords[0], bearing: 0 };
    }

    let remaining = clamped * total;

    for (const segment of segments) {
        if (remaining <= segment.length || segment === segments[segments.length - 1]) {
            const ratio = segment.length ? remaining / segment.length : 0;
            const lat = segment.from[0] + (segment.to[0] - segment.from[0]) * ratio;
            const lng = segment.from[1] + (segment.to[1] - segment.from[1]) * ratio;
            const bearing = Math.atan2(segment.to[1] - segment.from[1], segment.to[0] - segment.from[0]) * (180 / Math.PI);

            return { position: [lat, lng], bearing };
        }

        remaining -= segment.length;
    }

    return { position: coords[coords.length - 1], bearing: 0 };
}

/**
 * Trace signal upstream starting from a specific input port on a point.
 *
 * @returns {{ hops: Array<object>, receivingSignal: boolean, originName: string|null, partialPath: boolean }}
 */
export function traceSignalUpstreamFromPort(pointId, startPortId, cables, points) {
    const pointsById = buildPointsById(points);
    const hops = [];
    let currentPointId = Number(pointId);
    let currentPortId = Number(startPortId);
    const visited = new Set();

    if (! currentPortId) {
        return { hops, receivingSignal: false, originName: null, partialPath: false };
    }

    for (let step = 0; step < 48; step += 1) {
        const visitKey = `p:${currentPointId}:${currentPortId}`;

        if (visited.has(visitKey)) {
            break;
        }

        visited.add(visitKey);
        pushPointHop(hops, currentPointId, currentPortId, pointsById);

        const incoming = findIncomingCoreAtPort(cables, currentPointId, currentPortId);

        if (!incoming) {
            break;
        }

        let sourceEnd = appendCableUpstreamSegment(incoming, hops, visited, pointsById, cables);
        let sourcePointId = Number(sourceEnd?.network_point_id);
        let sourcePoint = pointsById[sourcePointId];

        if (sourceEnd?.connection_type === 'device') {
            pushDeviceEndHop(hops, sourceEnd, pointsById);
        }

        let headEndResult = headEndTraceResultIfReached(hops, sourcePoint);

        if (headEndResult) {
            return headEndResult;
        }

        const needsTrunkAdvance = (
            (
                isPassiveDistributionPoint(sourcePoint)
                || isActiveSignalSourcePoint(sourcePoint)
                || isFiberHeadEndPoint(sourcePoint)
                || isUpstreamInfrastructurePoint(sourcePoint)
            )
            && sourceEnd?.connection_type === 'device'
            && sourceEnd.device_port_direction === 'output'
        );

        if (needsTrunkAdvance) {
            let walkedTrunk = false;

            while (
                (
                    isPassiveDistributionPoint(sourcePoint)
                    || isActiveSignalSourcePoint(sourcePoint)
                    || isFiberHeadEndPoint(sourcePoint)
                    || isUpstreamInfrastructurePoint(sourcePoint)
                )
                && sourceEnd?.connection_type === 'device'
                && sourceEnd.device_port_direction === 'output'
            ) {
                const advanced = tryAdvanceThroughTrunkPort(cables, pointsById, sourcePointId, hops, visited);

                if (! advanced) {
                    break;
                }

                walkedTrunk = true;
                sourceEnd = advanced.sourceEnd;
                sourcePointId = advanced.sourcePointId;
                sourcePoint = advanced.sourcePoint;

                headEndResult = headEndTraceResultIfReached(hops, sourcePoint);

                if (headEndResult) {
                    return headEndResult;
                }
            }

            if (! walkedTrunk) {
                break;
            }

            break;
        }

        if (sourceEnd?.connection_type === 'device') {
            currentPointId = sourcePointId;
            currentPortId = Number(sourceEnd.network_point_port_id);
            continue;
        }

        break;
    }

    return finishUpstreamTrace(hops, pointsById);
}

/**
 * Trace upstream paths from each input port on a point (one path per device input).
 *
 * @returns {Array<{ label: string, hops: Array<object>, receivingSignal: boolean, originName: string|null, partialPath: boolean, portId: number|null, deviceLabel: string|null }>}
 */
export function traceSignalUpstreamPaths(pointId, cables, points, typeLabels = {}) {
    const pointsById = buildPointsById(points);
    const point = pointsById[pointId];

    if (!point) {
        return [];
    }

    const paths = inputPortsForPoint(point).map(({ port, device }) => (
        buildUpstreamPathEntry(pointId, port, device, cables, points, pointsById, typeLabels)
    ));

    if (isOltNetworkPoint(point)) {
        oltDevicesOnPoint(point).forEach((device) => {
            const devicePaths = paths.filter((path) => (
                path.deviceId != null && String(path.deviceId) === String(device.id)
            ));

            if (devicePaths.length) {
                return;
            }

            const inputPorts = (device.ports ?? []).filter((port) => port.direction === 'input');

            if (inputPorts.length === 0) {
                paths.push(createUnwiredUpstreamPathEntry(point, device, typeLabels));

                return;
            }

            inputPorts.forEach((port) => {
                if (!paths.some((path) => String(path.portId) === String(port.id))) {
                    paths.push(buildUpstreamPathEntry(
                        pointId,
                        port,
                        device,
                        cables,
                        points,
                        pointsById,
                        typeLabels,
                    ));
                }
            });
        });
    }

    return paths
        .filter((path) => path.unwired || path.hops.length > 0)
        .sort(rankUpstreamPath)
        .slice(0, 24);
}

/**
 * Trace signal path upstream from a point (e.g. customer ONT back to control room).
 * Uses the best result across all input ports when multiple devices exist.
 *
 * @returns {{ hops: Array<object>, receivingSignal: boolean, originName: string|null, partialPath: boolean }}
 */
export function traceSignalUpstream(pointId, cables, points) {
    const paths = traceSignalUpstreamPaths(pointId, cables, points);

    if (! paths.length) {
        return { hops: [], receivingSignal: false, originName: null, partialPath: false };
    }

    return paths[0];
}

function isCustomerPoint(point) {
    return isPointType(point, 'customer');
}

function outputPortsForPoint(point) {
    return (point?.devices ?? [])
        .flatMap((device) => device.ports ?? [])
        .filter((port) => port.direction === 'output');
}

function followForwardThroughSplices(cables, pointsById, currentEnd, currentCable, currentCore, hops, visited) {
    let farEnd = currentEnd;
    let farCable = currentCable;
    let farCore = currentCore;

    while (farEnd?.connection_type === 'core_end' && farEnd.connected_core_end_id) {
        const visitKey = `e:${farEnd.id}:${farEnd.connected_core_end_id}`;

        if (visited.has(visitKey)) {
            break;
        }

        visited.add(visitKey);

        const linked = findCoreEndById(cables, farEnd.connected_core_end_id);

        if (!linked) {
            break;
        }

        hops.push(buildSpliceHop(
            farCable,
            farCore,
            linked.cable,
            linked.core,
            pointsById,
            farEnd.network_point_id,
        ));
        hops.push(buildCableHop(linked.cable, linked.core, pointsById));

        farCable = linked.cable;
        farCore = linked.core;
        const otherSide = linked.side === 'start' ? 'end' : 'start';
        farEnd = linked.core.ends?.[otherSide];
    }

    return { end: farEnd, cable: farCable, core: farCore };
}

function pathLabelFromHops(hops, typeLabels = {}, pointsById = {}) {
    const pointHops = hops.filter((hop) => hop.kind === 'point');

    if (pointHops.length >= 2) {
        const start = signalPathCardLabel(pointHops[0], pointsById, typeLabels);
        const end = signalPathCardLabel(pointHops[pointHops.length - 1], pointsById, typeLabels);

        return `${start} → ${end}`;
    }

    return pointHops[0]
        ? signalPathCardLabel(pointHops[0], pointsById, typeLabels)
        : 'Path';
}

function upstreamPathLabel(point, device, typeLabels = {}) {
    return signalPathCardLabel({
        kind: 'point',
        pointId: point?.id,
        pointName: point?.name,
        pointType: primaryPointType(point),
        deviceLabel: device?.label ?? null,
        deviceType: device?.type ?? null,
    }, { [point?.id]: point }, typeLabels);
}

function rankUpstreamPath(left, right) {
    if (left.receivingSignal !== right.receivingSignal) {
        return Number(right.receivingSignal) - Number(left.receivingSignal);
    }

    if (left.partialPath !== right.partialPath) {
        return Number(right.partialPath) - Number(left.partialPath);
    }

    return right.hops.length - left.hops.length;
}

function extendDownstreamFromPort(
    cables,
    pointsById,
    pointId,
    port,
    prefixHops,
    visited,
    paths,
    maxPaths,
    typeLabels = {},
    sourceDevice = null,
    depth = 0,
) {
    if (paths.length >= maxPaths || depth > 24) {
        return;
    }

    const portVisitKey = `p:${pointId}:${port.id}:${prefixHops.length}`;

    if (visited.has(portVisitKey)) {
        return;
    }

    visited.add(portVisitKey);

    findOutgoingCoresFromPort(cables, pointId, port.id).forEach(({ cable, core, end }) => {
        if (paths.length >= maxPaths) {
            return;
        }

        const hops = [
            ...prefixHops,
            buildHopFromPort(pointId, port.id, pointsById),
            buildCableHop(cable, core, pointsById),
        ];

        const chain = followForwardThroughSplices(cables, pointsById, end, cable, core, hops, visited);
        const farEnd = chain.end;

        if (farEnd?.connection_type === 'device') {
            hops.push(buildHopFromCoreEnd(farEnd, pointsById));

            const endPoint = pointsById[farEnd.network_point_id];

            if (isCustomerPoint(endPoint)) {
                paths.push({
                    label: pathLabelFromHops(hops, typeLabels, pointsById),
                    hops,
                    sourceDeviceId: sourceDevice?.id ?? null,
                });

                return;
            }

            outputPortsForPoint(endPoint).forEach((nextPort) => {
                extendDownstreamFromPort(
                    cables,
                    pointsById,
                    farEnd.network_point_id,
                    nextPort,
                    hops,
                    visited,
                    paths,
                    maxPaths,
                    typeLabels,
                    sourceDevice,
                    depth + 1,
                );
            });

            return;
        }

        paths.push({
            label: pathLabelFromHops(hops, typeLabels, pointsById),
            hops,
            sourceDeviceId: sourceDevice?.id ?? null,
        });
    });
}

/**
 * Trace all downstream signal paths from a control room or OLT point.
 *
 * @returns {Array<{ label: string, hops: Array<object> }>}
 */
export function traceSignalDownstream(pointId, cables, points, typeLabels = {}) {
    const pointsById = buildPointsById(points);
    const point = pointsById[pointId];

    if (!point) {
        return [];
    }

    const paths = [];
    const visited = new Set();

    outputPortsWithDevice(point).forEach(({ port, device }) => {
        extendDownstreamFromPort(
            cables,
            pointsById,
            pointId,
            port,
            [],
            visited,
            paths,
            24,
            typeLabels,
            device,
        );
    });

    if (isOltNetworkPoint(point)) {
        oltDevicesOnPoint(point).forEach((device) => {
            const hasPath = paths.some((path) => String(path.sourceDeviceId) === String(device.id));
            const outputPorts = (device.ports ?? []).filter((port) => port.direction === 'output');

            if (hasPath || outputPorts.length === 0) {
                return;
            }

            paths.push({
                label: upstreamPathLabel(point, device, typeLabels),
                hops: [],
                sourceDeviceId: device.id,
                unwired: true,
            });
        });
    }

    return paths.slice(0, 24);
}

export function serializeCoresForm(coreCount, cores) {
    if (!coreCount || coreCount <= 0) {
        return { core_count: null, cores: [] };
    }

    return {
        core_count: coreCount,
        cores: cores.map((core) => ({
            core_number: core.core_number,
            color: core.color,
            label: core.label?.trim() || null,
            ends: {
                start: serializeCoreEndForm(core.ends.start),
                end: serializeCoreEndForm(core.ends.end),
            },
        })),
    };
}
