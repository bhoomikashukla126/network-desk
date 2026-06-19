<template>
    <aside class="app-card flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border p-4 shadow-sm sm:p-5">
        <div class="mb-3 flex shrink-0 items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-theme-heading">
                    {{ form.name || $t('points.newPoint') }}
                </h2>
                <p class="mt-0.5 text-xs text-theme-muted">
                    {{ $t('points.detail') }}
                    <span v-if="!point?.id"> · {{ $t('points.unsaved') }}</span>
                </p>
                <div class="mt-2">
                    <NetworkPointTypeBadges :point="{ types: form.types }" :labels="meta.types" />
                </div>
            </div>
            <button
                type="button"
                class="btn-secondary inline-flex shrink-0 items-center justify-center rounded-lg border p-2 transition"
                :aria-label="$t('common.cancel')"
                @click="$emit('close')"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <div class="mb-3 shrink-0 border-b border-theme pb-1">
            <div class="flex gap-1 overflow-x-auto overscroll-x-contain" role="tablist">
                <button
                    v-for="tab in visibleTabs"
                    :key="tab.id"
                    type="button"
                    role="tab"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-t-lg px-2.5 py-2 transition"
                    :class="activeTab === tab.id ? 'app-tab-active' : 'app-tab-inactive'"
                    :aria-label="tab.label"
                    :aria-selected="activeTab === tab.id"
                    :title="tab.label"
                    @click="activeTab = tab.id"
                >
                    <NetworkPointTypeIcon v-if="tab.type" :type="tab.type" size-class="h-3.5 w-3.5" />
                    <component :is="tab.icon" v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span class="max-w-[7rem] truncate text-xs font-medium">{{ tab.shortLabel ?? tab.label }}</span>
                </button>
            </div>
        </div>

        <form class="flex min-h-0 flex-1 flex-col overflow-hidden" @submit.prevent="save">
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div v-show="activeTab === 'basic'" class="space-y-3 pb-2">
                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="point-name">{{ $t('points.fields.name') }}</label>
                        <input
                            id="point-name"
                            v-model="form.name"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :placeholder="$t('points.namePlaceholder')"
                            required
                            :disabled="!canEdit"
                        >
                    </div>

                    <div>
                        <label class="app-label mb-2 block text-sm font-medium">{{ $t('points.fields.types') }}</label>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="(label, key) in meta.types"
                                :key="key"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition"
                                :class="form.types.includes(key)
                                    ? 'border-theme-primary bg-theme-primary/10 text-theme-heading'
                                    : 'border-theme bg-theme-card text-theme-muted'"
                            >
                                <input
                                    v-model="form.types"
                                    type="checkbox"
                                    class="sr-only"
                                    :value="key"
                                    :disabled="!canEdit"
                                    @change="onTypesChange"
                                >
                                <span class="h-2 w-2 rounded-full" :style="{ background: pointColor(key) }" />
                                {{ label }}
                            </label>
                        </div>
                        <p v-if="typesError" class="mt-1 text-xs text-rose-600">{{ typesError }}</p>
                        <p v-else-if="!point?.id" class="mt-2 text-xs text-theme-muted">{{ $t('points.typePreviewHint') }}</p>
                    </div>

                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="point-status">{{ $t('points.fields.status') }}</label>
                        <select
                            id="point-status"
                            v-model="form.status"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :disabled="!canEdit"
                        >
                            <option v-for="(label, key) in meta.statuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="point-area">{{ $t('points.fields.area') }}</label>
                        <input
                            id="point-area"
                            v-model="form.area"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            list="network-areas"
                            :placeholder="$t('points.areaPlaceholder')"
                            :disabled="!canEdit"
                        >
                        <datalist id="network-areas">
                            <option v-for="area in areas" :key="area" :value="area" />
                        </datalist>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="point-latitude">{{ $t('points.fields.latitude') }}</label>
                            <input
                                id="point-latitude"
                                v-model.number="form.latitude"
                                type="number"
                                step="any"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                required
                                :disabled="!canEdit"
                            >
                        </div>
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="point-longitude">{{ $t('points.fields.longitude') }}</label>
                            <input
                                id="point-longitude"
                                v-model.number="form.longitude"
                                type="number"
                                step="any"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                required
                                :disabled="!canEdit"
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-for="type in form.types"
                    v-show="activeTab === deviceTabId(type)"
                    :key="deviceTabId(type)"
                    class="space-y-3 pb-2"
                >
                    <div class="rounded-xl border border-theme bg-theme-background/60 px-3 py-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-theme-heading">
                                    {{ meta.types?.[type] ?? type }}
                                    <span v-if="devicesForType(type).length > 1" class="font-normal text-theme-muted">
                                        ({{ devicesForType(type).length }})
                                    </span>
                                </p>
                                <p class="mt-0.5 text-xs text-theme-muted">{{ $t('points.deviceTabHint') }}</p>
                            </div>
                            <button
                                v-if="canEdit"
                                type="button"
                                class="btn-accent-outline inline-flex shrink-0 items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition"
                                @click="addDevice(type)"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                {{ $t('points.addDevice') }}
                            </button>
                        </div>
                    </div>

                    <article
                        v-for="(entry, deviceOrdinal) in devicesForType(type)"
                        :key="entry.device._key"
                        class="space-y-3 rounded-xl border border-theme bg-theme-background/40 px-3 py-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-theme-muted">
                                {{ $t('points.deviceInstanceLabel', { index: deviceOrdinal + 1 }) }}
                            </p>
                            <button
                                v-if="canEdit && devicesForType(type).length > 1"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-950/30"
                                @click="removeDevice(entry.index)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                {{ $t('points.removeDevice') }}
                            </button>
                        </div>

                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" :for="`device-label-${entry.device._key}`">
                                {{ $t('points.fields.deviceLabel') }}
                            </label>
                            <input
                                :id="`device-label-${entry.device._key}`"
                                v-model="entry.device.label"
                                type="text"
                                maxlength="255"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                :placeholder="$t('points.deviceLabelPlaceholder')"
                                :disabled="!canEdit"
                            >
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium text-theme-heading">{{ $t('points.devicePortsTitle') }}</p>
                            <button
                                v-if="canEdit"
                                type="button"
                                class="btn-accent-outline inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition"
                                @click="addPort(entry.index)"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                {{ $t('points.addPort') }}
                            </button>
                        </div>

                        <div v-if="entry.device.ports.length" class="space-y-2">
                            <article
                                v-for="(port, portIndex) in entry.device.ports"
                                :key="port._key"
                                class="grid gap-2 rounded-xl border border-dashed border-theme bg-theme-background px-3 py-2.5 sm:grid-cols-[1fr_1fr_auto]"
                            >
                                <div>
                                    <label class="app-label mb-1 block text-xs font-medium" :for="`port-label-${port._key}`">
                                        {{ $t('points.fields.portLabel') }}
                                    </label>
                                    <input
                                        :id="`port-label-${port._key}`"
                                        v-model="port.label"
                                        type="text"
                                        maxlength="255"
                                        class="app-input w-full rounded-lg border px-2.5 py-1.5 text-sm shadow-sm"
                                        :placeholder="$t('points.portLabelPlaceholder')"
                                        :disabled="!canEdit"
                                    >
                                </div>
                                <div>
                                    <label class="app-label mb-1 block text-xs font-medium" :for="`port-direction-${port._key}`">
                                        {{ $t('points.fields.portDirection') }}
                                    </label>
                                    <select
                                        :id="`port-direction-${port._key}`"
                                        v-model="port.direction"
                                        class="app-input w-full rounded-lg border px-2.5 py-1.5 text-sm shadow-sm"
                                        :disabled="!canEdit"
                                    >
                                        <option value="input">{{ $t('cables.portInput') }}</option>
                                        <option value="output">{{ $t('cables.portOutput') }}</option>
                                    </select>
                                </div>
                                <div class="flex items-end justify-end">
                                    <button
                                        v-if="canEdit"
                                        type="button"
                                        class="rounded p-1.5 text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-950/30"
                                        :aria-label="$t('points.removePort')"
                                        @click="removePort(entry.index, portIndex)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <div v-if="port.id && port.label.trim()" class="col-span-full border-t border-theme pt-2">
                                    <p class="text-xs font-medium text-theme-heading">{{ $t('points.portConnectionTitle') }}</p>
                                    <template v-if="portConnection(port)">
                                        <div class="mt-1.5 space-y-1 text-xs text-theme-muted">
                                            <p>
                                                <span class="font-medium text-theme-body">{{ $t('points.portWiredTo') }}:</span>
                                                {{ formatPortConnectionLabel(portConnection(port)) }}
                                            </p>
                                            <p v-if="portConnection(port).peerLabel && portConnection(port).peerLabel !== '—'">
                                                <span class="font-medium text-theme-body">{{ $t('points.portConnectionFarEnd') }}:</span>
                                                {{ portConnection(port).peerLabel }}
                                            </p>
                                        </div>
                                    </template>
                                    <p v-else class="mt-1 text-xs text-theme-muted">{{ $t('cables.connectionNone') }}</p>
                                </div>
                            </article>
                        </div>

                        <p v-else class="app-dashed-empty rounded-xl border border-dashed px-4 py-5 text-center text-sm">
                            {{ $t('points.noPortsForDevice') }}
                        </p>
                    </article>
                </div>

                <div v-show="activeTab === 'details'" class="space-y-3 pb-2">
                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="point-address">{{ $t('points.fields.address') }}</label>
                        <textarea
                            id="point-address"
                            v-model="form.address"
                            rows="2"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :placeholder="$t('points.addressPlaceholder')"
                            :disabled="!canEdit"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="point-contact-name">{{ $t('points.fields.contactName') }}</label>
                            <input
                                id="point-contact-name"
                                v-model="form.contact_name"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                :disabled="!canEdit"
                            >
                        </div>
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="point-contact-phone">{{ $t('points.fields.contactPhone') }}</label>
                            <input
                                id="point-contact-phone"
                                v-model="form.contact_phone"
                                type="tel"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                :disabled="!canEdit"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="point-notes">{{ $t('points.fields.notes') }}</label>
                        <textarea
                            id="point-notes"
                            v-model="form.notes"
                            rows="2"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :placeholder="$t('points.notesPlaceholder')"
                            :disabled="!canEdit"
                        />
                    </div>
                </div>

                <div v-show="activeTab === 'signal'" class="space-y-3 pb-2">
                    <div class="rounded-xl border border-theme bg-theme-background/60 px-3 py-2.5">
                        <p class="text-sm font-semibold text-theme-heading">{{ $t('points.signalPathTitle') }}</p>
                        <p class="mt-0.5 text-xs text-theme-muted">{{ $t('points.signalPathHint') }}</p>
                    </div>

                    <div v-if="isCustomerPoint" class="space-y-3">
                        <div class="rounded-xl border border-theme px-3 py-3">
                            <div class="mb-2 flex min-w-0 items-center gap-2">
                                <span
                                    class="inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="signalStatusClass"
                                />
                                <p class="text-sm font-medium text-theme-heading">
                                    {{ signalStatusLabel }}
                                </p>
                            </div>
                            <p v-if="signalUpstreamPaths.length > 1" class="text-xs text-theme-muted">
                                {{ $t('points.signalIncomingPaths', { count: signalUpstreamPaths.length }) }}
                            </p>
                        </div>

                        <p v-if="!signalUpstreamPaths.length" class="text-xs text-theme-muted">{{ $t('points.noSignalPath') }}</p>

                        <details
                            v-for="(path, index) in signalUpstreamPaths"
                            :key="`up-path-${index}-${path.label}`"
                            class="rounded-xl border border-theme bg-theme-background px-3 py-2"
                            :class="isPathAnimating('upstream', index) ? 'ring-1 ring-emerald-500/40' : ''"
                            :open="signalUpstreamPaths.length === 1"
                        >
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-xs font-medium text-theme-heading [&::-webkit-details-marker]:hidden">
                                <span class="flex min-w-0 items-center gap-2 truncate">
                                    <span
                                        class="inline-flex h-2 w-2 shrink-0 rounded-full"
                                        :class="signalPathStatusClass(path)"
                                    />
                                    <span class="min-w-0 truncate">
                                        <span class="block truncate">{{ path.label }}</span>
                                        <span
                                            v-if="path.destinationLabel"
                                            class="block truncate text-[11px] font-normal text-theme-muted"
                                        >
                                            {{ $t('points.signalPathTo', { destination: path.destinationLabel }) }}
                                        </span>
                                    </span>
                                </span>
                                <button
                                    v-if="path.hops?.length && !path.unwired"
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold transition"
                                    :class="isPathAnimating('upstream', index) ? 'btn-secondary border border-theme' : 'btn-primary'"
                                    @click.prevent="toggleUpstreamSignalAnimation(index)"
                                >
                                    <Radio class="h-3 w-3" :class="isPathAnimating('upstream', index) ? 'animate-pulse text-emerald-600' : ''" />
                                    {{ isPathAnimating('upstream', index) ? $t('points.stopSignalAnimation') : $t('points.animateSignal') }}
                                </button>
                            </summary>

                            <div class="mt-2 space-y-2 border-t border-theme pt-2">
                                <p v-if="path.unwired" class="text-xs text-theme-muted">
                                    {{ $t('points.signalNoUplinkConfigured') }}
                                </p>
                                <p v-else-if="upstreamServiceSource(path)" class="text-xs font-medium text-theme-heading">
                                    {{ $t('points.signalServiceFrom', { source: upstreamServiceSource(path) }) }}
                                </p>
                                <p v-if="upstreamBackhaulOrigin(path)" class="text-xs text-theme-muted">
                                    {{ $t('points.signalBackhaulFrom', { origin: upstreamBackhaulOrigin(path) }) }}
                                </p>
                                <p v-else-if="path.receivingSignal && path.originName && !upstreamOltHop(path)" class="text-xs text-theme-muted">
                                    {{ $t('points.signalFromOrigin', { origin: path.originName }) }}
                                </p>
                                <p v-else-if="path.partialPath" class="text-xs text-amber-700 dark:text-amber-300">
                                    {{ $t('points.signalPartialHint') }}
                                </p>

                                <ol v-if="upstreamFlowHops(path).length" class="relative space-y-2">
                                    <div
                                        v-if="isPathAnimating('upstream', index)"
                                        class="pointer-events-none absolute bottom-1 left-2.5 top-1 w-px bg-emerald-500/20"
                                        aria-hidden="true"
                                    >
                                        <span
                                            class="absolute left-1/2 inline-flex -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500 p-0.5 text-white shadow-md ring-2 ring-emerald-500/30 transition-[top] duration-75 ease-linear"
                                            :style="{ top: `${signalPathProgress * 100}%` }"
                                        >
                                            <ArrowDown class="h-3 w-3" />
                                        </span>
                                    </div>
                                    <li
                                        v-for="(hop, hopIndex) in upstreamFlowHops(path)"
                                        :key="`up-${index}-${hopIndex}-${hop.kind}-${hop.pointId ?? hop.cableId ?? hop.label}`"
                                        class="relative flex gap-2 text-xs transition-colors duration-300"
                                        :class="animatedHopClass(hopIndex, index, 'upstream')"
                                    >
                                        <span
                                            v-if="hopIndex > 0"
                                            class="pointer-events-none absolute -top-2 left-2.5 h-2 w-px bg-theme-primary/30"
                                            aria-hidden="true"
                                        />
                                        <span
                                            class="relative z-[1] mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold transition-all duration-300"
                                            :class="animatedHopBadgeClass(hopIndex, index, 'upstream')"
                                        >
                                            <ArrowDown
                                                v-if="isPathAnimating('upstream', index) && signalAnimHopIndex === hopIndex && hopIndex > 0"
                                                class="h-3 w-3 animate-bounce"
                                            />
                                            <template v-else>{{ hopIndex + 1 }}</template>
                                        </span>
                                        <div class="min-w-0 text-theme-body">
                                            <p v-if="hop.kind === 'point'" class="font-medium text-theme-heading">
                                                {{ formatSignalHop(hop) }}
                                            </p>
                                            <p v-else-if="hop.kind === 'splice'">
                                                <span class="font-medium text-theme-heading">{{ hop.pointName }}</span>
                                                <span class="text-theme-muted"> · {{ $t('points.signalSplice') }} · {{ hop.label }}</span>
                                            </p>
                                            <p v-else>
                                                <span class="font-medium">{{ hop.cableName }}</span>
                                                <span class="text-theme-muted"> · {{ hop.coreCount }}C · Core {{ hop.coreNumber }} · {{ hop.fromPort }} → {{ hop.toPort }}</span>
                                            </p>
                                        </div>
                                    </li>
                                </ol>
                            </div>
                        </details>
                    </div>

                    <div v-if="isOltPoint" class="space-y-3">
                        <p class="text-sm font-medium text-theme-heading">{{ $t('points.signalIncomingBackhaul') }}</p>
                        <p v-if="signalUpstreamPaths.length > 1" class="text-xs text-theme-muted">
                            {{ $t('points.signalBackhaulPaths', { count: signalUpstreamPaths.length }) }}
                        </p>
                        <p v-if="!signalUpstreamPaths.length" class="text-xs text-theme-muted">{{ $t('points.noSignalPath') }}</p>
                        <details
                            v-for="(path, index) in signalUpstreamPaths"
                            :key="`olt-in-${index}-${path.label}`"
                            class="rounded-xl border border-theme bg-theme-background px-3 py-2"
                            :open="signalUpstreamPaths.length === 1"
                        >
                            <summary class="cursor-pointer list-none text-xs font-medium text-theme-heading [&::-webkit-details-marker]:hidden">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="inline-flex h-2 w-2 shrink-0 rounded-full"
                                        :class="signalPathStatusClass(path)"
                                    />
                                    <span class="min-w-0 truncate">
                                        <span class="block truncate">{{ path.label }}</span>
                                        <span
                                            v-if="path.destinationLabel"
                                            class="block truncate text-[11px] font-normal text-theme-muted"
                                        >
                                            {{ $t('points.signalPathTo', { destination: path.destinationLabel }) }}
                                        </span>
                                    </span>
                                </span>
                            </summary>
                            <div class="mt-2 space-y-2 border-t border-theme pt-2">
                                <p v-if="path.unwired" class="text-xs text-theme-muted">
                                    {{ $t('points.signalNoUplinkConfigured') }}
                                </p>
                                <p v-else-if="path.receivingSignal && (path.destinationLabel || path.originName)" class="text-xs text-theme-muted">
                                    {{ $t('points.signalFedBy', { origin: path.destinationLabel ?? path.originName }) }}
                                </p>
                                <p v-else-if="path.partialPath" class="text-xs text-amber-700 dark:text-amber-300">
                                    {{ $t('points.signalPartialHint') }}
                                </p>
                                <ol v-if="upstreamFlowHops(path).length" class="space-y-2">
                                    <li
                                        v-for="(hop, hopIndex) in upstreamFlowHops(path)"
                                        :key="`olt-in-hop-${index}-${hopIndex}`"
                                        class="flex gap-2 text-xs text-theme-body"
                                    >
                                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-theme-primary/10 text-[11px] font-semibold text-theme-primary">
                                            {{ hopIndex + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <template v-if="hop.kind === 'point'">
                                                {{ formatSignalHop(hop) }}
                                            </template>
                                            <template v-else-if="hop.kind === 'splice'">
                                                <span class="font-medium text-theme-heading">{{ hop.pointName }}</span>
                                                <span class="text-theme-muted"> · {{ $t('points.signalSplice') }}</span>
                                            </template>
                                            <template v-else>
                                                <span class="font-medium">{{ hop.cableName }}</span>
                                                <span class="text-theme-muted"> · {{ hop.fromPort }} → {{ hop.toPort }}</span>
                                            </template>
                                        </div>
                                    </li>
                                </ol>
                            </div>
                        </details>
                    </div>

                    <div v-if="isOltPoint" class="space-y-4">
                        <p class="text-sm font-medium text-theme-heading">{{ $t('points.signalOutgoingPon') }}</p>
                        <p v-if="!oltDownstreamGroups.length" class="text-xs text-theme-muted">{{ $t('points.noDownstreamSignals') }}</p>
                        <section
                            v-for="group in oltDownstreamGroups"
                            :key="`olt-out-${group.device.id ?? group.deviceLabel}`"
                            class="space-y-2"
                        >
                            <p class="text-xs font-semibold text-theme-heading">{{ group.deviceLabel }}</p>
                            <p v-if="!group.items.length" class="text-xs text-theme-muted">
                                {{ $t('points.noDownstreamFromDevice') }}
                            </p>
                            <details
                                v-for="{ path, index } in group.items"
                                :key="`down-${index}-${path.label}`"
                                class="rounded-xl border border-theme bg-theme-background px-3 py-2"
                                :class="isPathAnimating('downstream', index) ? 'ring-1 ring-emerald-500/40' : ''"
                            >
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-xs font-medium text-theme-heading [&::-webkit-details-marker]:hidden">
                                    <span class="min-w-0 truncate">{{ path.label }}</span>
                                    <button
                                        v-if="path.hops?.length && !path.unwired"
                                        type="button"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold transition"
                                        :class="isPathAnimating('downstream', index) ? 'btn-secondary border border-theme' : 'btn-primary'"
                                        @click.prevent="toggleDownstreamSignalAnimation(index)"
                                    >
                                        <Radio class="h-3 w-3" :class="isPathAnimating('downstream', index) ? 'animate-pulse text-emerald-600' : ''" />
                                        {{ isPathAnimating('downstream', index) ? $t('points.stopSignalAnimation') : $t('points.animateSignal') }}
                                    </button>
                                </summary>
                                <p v-if="path.unwired" class="mt-2 border-t border-theme pt-2 text-xs text-theme-muted">
                                    {{ $t('points.signalNoPonConfigured') }}
                                </p>
                                <ol
                                    v-else
                                    class="relative mt-2 space-y-2 border-t border-theme pt-2"
                                >
                                    <div
                                        v-if="isPathAnimating('downstream', index)"
                                        class="pointer-events-none absolute bottom-1 left-2.5 top-1 w-px bg-emerald-500/20"
                                        aria-hidden="true"
                                    >
                                        <span
                                            class="absolute left-1/2 inline-flex -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500 p-0.5 text-white shadow-md ring-2 ring-emerald-500/30 transition-[top] duration-75 ease-linear"
                                            :style="{ top: `${signalPathProgress * 100}%` }"
                                        >
                                            <ArrowDown class="h-3 w-3" />
                                        </span>
                                    </div>
                                    <li
                                        v-for="(hop, hopIndex) in path.hops"
                                        :key="`down-hop-${index}-${hopIndex}`"
                                        class="relative flex gap-2 text-xs transition-colors duration-300"
                                        :class="animatedHopClass(hopIndex, index, 'downstream')"
                                    >
                                        <span
                                            v-if="hopIndex > 0"
                                            class="pointer-events-none absolute -top-2 left-2.5 h-2 w-px bg-theme-primary/30"
                                            aria-hidden="true"
                                        />
                                        <span
                                            class="relative z-[1] mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold transition-all duration-300"
                                            :class="animatedHopBadgeClass(hopIndex, index, 'downstream')"
                                        >
                                            <ArrowDown
                                                v-if="isPathAnimating('downstream', index) && signalAnimHopIndex === hopIndex && hopIndex > 0"
                                                class="h-3 w-3 animate-bounce"
                                            />
                                            <template v-else>{{ hopIndex + 1 }}</template>
                                        </span>
                                        <div class="min-w-0 text-theme-body">
                                            <template v-if="hop.kind === 'point'">
                                                {{ formatSignalHop(hop) }}
                                            </template>
                                            <template v-else-if="hop.kind === 'splice'">
                                                <span class="font-medium text-theme-heading">{{ hop.pointName }}</span>
                                                <span class="text-theme-muted"> · {{ $t('points.signalSplice') }} · {{ hop.label }}</span>
                                            </template>
                                            <template v-else>
                                                <span class="font-medium">{{ hop.cableName }}</span>
                                                <span class="text-theme-muted"> · {{ hop.coreCount }}C · Core {{ hop.coreNumber }} · {{ hop.fromPort }} → {{ hop.toPort }}</span>
                                            </template>
                                        </div>
                                    </li>
                                </ol>
                            </details>
                        </section>
                    </div>

                    <div v-else-if="isHeadEndPoint" class="space-y-3">
                        <p class="text-sm font-medium text-theme-heading">{{ $t('points.downstreamSignals') }}</p>
                        <p v-if="!signalDownstream.length" class="text-xs text-theme-muted">{{ $t('points.noDownstreamSignals') }}</p>
                        <details
                            v-for="(path, index) in signalDownstream"
                            :key="`down-${index}-${path.label}`"
                            class="rounded-xl border border-theme bg-theme-background px-3 py-2"
                            :class="isPathAnimating('downstream', index) ? 'ring-1 ring-emerald-500/40' : ''"
                        >
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-xs font-medium text-theme-heading [&::-webkit-details-marker]:hidden">
                                <span class="min-w-0 truncate">{{ path.label }}</span>
                                <button
                                    v-if="path.hops?.length"
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold transition"
                                    :class="isPathAnimating('downstream', index) ? 'btn-secondary border border-theme' : 'btn-primary'"
                                    @click.prevent="toggleDownstreamSignalAnimation(index)"
                                >
                                    <Radio class="h-3 w-3" :class="isPathAnimating('downstream', index) ? 'animate-pulse text-emerald-600' : ''" />
                                    {{ isPathAnimating('downstream', index) ? $t('points.stopSignalAnimation') : $t('points.animateSignal') }}
                                </button>
                            </summary>
                            <ol
                                class="relative mt-2 space-y-2 border-t border-theme pt-2"
                            >
                                <div
                                    v-if="isPathAnimating('downstream', index)"
                                    class="pointer-events-none absolute bottom-1 left-2.5 top-1 w-px bg-emerald-500/20"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="absolute left-1/2 inline-flex -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500 p-0.5 text-white shadow-md ring-2 ring-emerald-500/30 transition-[top] duration-75 ease-linear"
                                        :style="{ top: `${signalPathProgress * 100}%` }"
                                    >
                                        <ArrowDown class="h-3 w-3" />
                                    </span>
                                </div>
                                <li
                                    v-for="(hop, hopIndex) in path.hops"
                                    :key="`down-hop-${index}-${hopIndex}`"
                                    class="relative flex gap-2 text-xs transition-colors duration-300"
                                    :class="animatedHopClass(hopIndex, index, 'downstream')"
                                >
                                    <span
                                        v-if="hopIndex > 0"
                                        class="pointer-events-none absolute -top-2 left-2.5 h-2 w-px bg-theme-primary/30"
                                        aria-hidden="true"
                                    />
                                    <span
                                        class="relative z-[1] mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold transition-all duration-300"
                                        :class="animatedHopBadgeClass(hopIndex, index, 'downstream')"
                                    >
                                        <ArrowDown
                                            v-if="isPathAnimating('downstream', index) && signalAnimHopIndex === hopIndex && hopIndex > 0"
                                            class="h-3 w-3 animate-bounce"
                                        />
                                        <template v-else>{{ hopIndex + 1 }}</template>
                                    </span>
                                    <div class="min-w-0 text-theme-body">
                                        <template v-if="hop.kind === 'point'">
                                            {{ formatSignalHop(hop) }}
                                        </template>
                                        <template v-else-if="hop.kind === 'splice'">
                                            <span class="font-medium text-theme-heading">{{ hop.pointName }}</span>
                                            <span class="text-theme-muted"> · {{ $t('points.signalSplice') }} · {{ hop.label }}</span>
                                        </template>
                                        <template v-else>
                                            <span class="font-medium">{{ hop.cableName }}</span>
                                            <span class="text-theme-muted"> · {{ hop.coreCount }}C · Core {{ hop.coreNumber }} · {{ hop.fromPort }} → {{ hop.toPort }}</span>
                                        </template>
                                    </div>
                                </li>
                            </ol>
                        </details>
                    </div>
                </div>

                <div v-show="activeTab === 'photos'" class="space-y-3 pb-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="app-label text-sm font-medium">{{ $t('points.sections.photos') }}</p>
                        <label v-if="canEdit" class="btn-accent-outline inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-semibold transition">
                            <ImagePlus class="h-4 w-4" />
                            {{ $t('points.uploadImage') }}
                            <input type="file" accept="image/*" class="hidden" @change="uploadImage">
                        </label>
                    </div>

                    <div v-if="point?.images?.length" class="grid grid-cols-2 gap-2">
                        <figure
                            v-for="image in point.images"
                            :key="image.id"
                            class="overflow-hidden rounded-xl border border-theme"
                        >
                            <img :src="image.url" :alt="image.caption || form.name" class="h-20 w-full object-cover">
                            <figcaption class="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span class="truncate text-xs text-theme-muted">{{ image.caption || $t('points.referencePhoto') }}</span>
                                <button
                                    v-if="canEdit"
                                    type="button"
                                    class="rounded p-1 text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-950/30"
                                    @click="$emit('delete-image', image)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </figcaption>
                        </figure>
                    </div>

                    <p v-else class="app-dashed-empty rounded-xl border border-dashed px-4 py-6 text-center text-sm">
                        {{ $t('points.noImages') }}
                    </p>
                </div>

                <p v-if="portsSummary && activeTab.startsWith('device:')" class="pb-2 text-xs text-theme-muted">
                    {{ portsSummary }}
                </p>
            </div>

            <div class="mt-3 flex shrink-0 flex-wrap gap-2 border-t border-theme pt-3">
                <button
                    v-if="canEdit"
                    type="submit"
                    class="btn-primary inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition disabled:opacity-60"
                    :disabled="saving"
                >
                    <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
                    {{ saving ? $t('common.loading') : $t('common.save') }}
                </button>
                <button
                    type="button"
                    class="btn-secondary inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition"
                    @click="$emit('close')"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    v-if="point?.id && canDelete"
                    type="button"
                    class="btn-danger-outline ml-auto inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition"
                    @click="$emit('delete')"
                >
                    <Trash2 class="h-4 w-4" />
                    {{ $t('common.delete') }}
                </button>
                <button
                    v-if="!canEdit"
                    type="button"
                    class="btn-secondary inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold transition disabled:opacity-60"
                    disabled
                >
                    {{ $t('points.viewOnly') }}
                </button>
            </div>
        </form>
    </aside>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowDown, Image, ImagePlus, LoaderCircle, MapPin, NotebookText, Plus, Radio, Trash2, X } from 'lucide-vue-next';
import NetworkPointTypeBadges from './NetworkPointTypeBadges.vue';
import NetworkPointTypeIcon from './NetworkPointTypeIcon.vue';
import {
    findPortConnection,
    findPortConnections,
    formatPortCableConnection,
    formatSignalPointHop,
    normalizePointTypes,
    oltHopFromFlowHops,
    oltDevicesOnPoint,
    pointColor,
    signalFlowOrder,
    traceSignalDownstream,
    traceSignalUpstreamPaths,
    isHeadEndNetworkPoint,
    isOltNetworkPoint,
} from '../../utils/networkMap';

const props = defineProps({
    point: { type: Object, default: null },
    points: { type: Array, default: () => [] },
    cables: { type: Array, default: () => [] },
    meta: { type: Object, required: true },
    areas: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save', 'delete', 'upload-image', 'delete-image', 'draft-change', 'signal-animate']);

const { t } = useI18n();
const activeTab = ref('basic');
const typesError = ref('');
let syncingFromPoint = false;
let portKeyCounter = 0;
let deviceKeyCounter = 0;

function createPortRow(port = {}) {
    portKeyCounter += 1;

    return {
        _key: port.id ?? `new-port-${portKeyCounter}`,
        id: port.id ?? null,
        label: port.label ?? '',
        direction: port.direction ?? 'input',
    };
}

function defaultPortsForDevice() {
    return [
        createPortRow({ direction: 'input' }),
        createPortRow({ direction: 'output' }),
    ];
}

function createDeviceRow(device = {}, withDefaultPorts = false) {
    deviceKeyCounter += 1;

    const ports = device.ports?.length
        ? device.ports.map((port) => createPortRow(port))
        : (withDefaultPorts ? defaultPortsForDevice() : []);

    return {
        _key: device.id ?? `new-device-${deviceKeyCounter}`,
        id: device.id ?? null,
        label: device.label ?? '',
        type: device.type ?? 'junction',
        ports,
    };
}

function defaultDevicesFromTypes(types, typeLabels = {}) {
    const normalized = types?.length ? types : ['junction'];

    return normalized.map((type) => createDeviceRow({
        type,
        label: typeLabels[type] ?? type,
    }, true));
}

function devicesFromPoint(point, typeLabels = {}) {
    const types = normalizePointTypes(point ?? { types: ['junction'] });
    let devices = point?.devices?.length
        ? point.devices.map((device) => createDeviceRow(device))
        : [];

    devices = devices.filter((device) => types.includes(device.type));

    types.forEach((type) => {
        if (!devices.some((device) => device.type === type)) {
            devices.push(createDeviceRow({
                type,
                label: typeLabels[type] ?? type,
            }, !point?.id));
        }
    });

    return devices.length ? devices : defaultDevicesFromTypes(types, typeLabels);
}

function deviceTabId(type) {
    return `device:${type}`;
}

function devicesForType(type) {
    return form.devices
        .map((device, index) => ({ device, index }))
        .filter(({ device }) => device.type === type);
}

function deviceCountForType(type) {
    return devicesForType(type).length;
}

function syncDevicesWithTypes() {
    const types = form.types.length ? form.types : ['junction'];

    for (let index = form.devices.length - 1; index >= 0; index -= 1) {
        if (!types.includes(form.devices[index].type)) {
            form.devices.splice(index, 1);
        }
    }

    types.forEach((type) => {
        if (!form.devices.some((device) => device.type === type)) {
            form.devices.push(createDeviceRow({
                type,
                label: props.meta.types?.[type] ?? type,
            }, true));
        }
    });
}

function addDevice(type) {
    const typeLabel = props.meta.types?.[type] ?? type;
    const count = deviceCountForType(type) + 1;

    form.devices.push(createDeviceRow({
        type,
        label: count > 1 ? `${typeLabel} ${count}` : typeLabel,
    }, true));
}

function removeDevice(deviceIndex) {
    form.devices.splice(deviceIndex, 1);
}

function firstDeviceTabId() {
    const type = form.types[0] ?? 'junction';

    return deviceTabId(type);
}

function emptyForm() {
    return {
        name: '',
        types: ['junction'],
        status: 'active',
        area: '',
        latitude: 0,
        longitude: 0,
        address: '',
        notes: '',
        contact_name: '',
        contact_phone: '',
        devices: defaultDevicesFromTypes(['junction']),
    };
}

const form = reactive(emptyForm());

const visibleTabs = computed(() => {
    const tabs = [
        {
            id: 'basic',
            label: t('points.tabs.basic'),
            shortLabel: t('points.tabs.basicShort'),
            icon: MapPin,
        },
    ];

    (form.types.length ? form.types : ['junction']).forEach((type) => {
        const count = deviceCountForType(type);
        const typeLabel = props.meta.types?.[type] ?? type;

        tabs.push({
            id: deviceTabId(type),
            type,
            label: count > 1 ? `${typeLabel} (${count})` : typeLabel,
            shortLabel: count > 1 ? `${typeLabel} (${count})` : typeLabel,
            icon: MapPin,
        });
    });

    tabs.push({
        id: 'details',
        label: t('points.tabs.details'),
        shortLabel: t('points.tabs.detailsShort'),
        icon: NotebookText,
    });

    if (props.point?.id) {
        tabs.push({
            id: 'signal',
            label: t('points.tabs.signal'),
            shortLabel: t('points.tabs.signalShort'),
            icon: Radio,
        });
        tabs.push({
            id: 'photos',
            label: t('points.tabs.photos'),
            shortLabel: t('points.tabs.photosShort'),
            icon: Image,
        });
    }

    return tabs;
});

const isCustomerPoint = computed(() => (props.point?.types ?? [props.point?.type]).includes('customer'));

const isOltPoint = computed(() => isOltNetworkPoint(props.point));

const isHeadEndPoint = computed(() => isHeadEndNetworkPoint(props.point));

const signalTypeLabels = computed(() => props.meta?.types ?? {});

const pointsForSignalTrace = computed(() => {
    if (!props.point?.id) {
        return props.points;
    }

    return props.points.map((item) => (
        String(item.id) === String(props.point.id)
            ? { ...item, ...props.point, devices: props.point.devices ?? item.devices }
            : item
    ));
});

const signalUpstreamPaths = computed(() => {
    if (!props.point?.id) {
        return [];
    }

    return traceSignalUpstreamPaths(
        props.point.id,
        props.cables,
        pointsForSignalTrace.value,
        signalTypeLabels.value,
    );
});

const signalStatusLabel = computed(() => {
    if (signalUpstreamPaths.value.some((path) => path.receivingSignal)) {
        return t('points.signalReceiving');
    }

    if (signalUpstreamPaths.value.some((path) => path.partialPath)) {
        return t('points.signalPartial');
    }

    return t('points.signalNotReceiving');
});

const signalStatusClass = computed(() => {
    if (signalUpstreamPaths.value.some((path) => path.receivingSignal)) {
        return 'bg-emerald-500';
    }

    if (signalUpstreamPaths.value.some((path) => path.partialPath)) {
        return 'bg-amber-500';
    }

    return 'bg-rose-500';
});

function formatSignalHop(hop) {
    return formatSignalPointHop(hop, signalTypeLabels.value, t);
}

function upstreamServiceSource(path) {
    const hop = upstreamOltHop(path);

    return hop ? formatSignalHop(hop) : null;
}

function upstreamFlowHops(path) {
    return path?.hops?.length ? signalFlowOrder(path.hops) : [];
}

function upstreamOltHop(path) {
    return oltHopFromFlowHops(upstreamFlowHops(path), props.points);
}

function upstreamBackhaulOrigin(path) {
    if (!path?.receivingSignal || !path.originName) {
        return null;
    }

    const oltName = upstreamOltHop(path)?.pointName;

    if (oltName && path.originName === oltName) {
        return null;
    }

    return path.originName;
}

function signalPathStatusClass(path) {
    if (path.receivingSignal) {
        return 'bg-emerald-500';
    }

    if (path.partialPath) {
        return 'bg-amber-500';
    }

    return 'bg-rose-500';
}

const signalAnimating = ref(false);
const signalAnimHopIndex = ref(-1);
const signalPathProgress = ref(0);
const signalAnimHops = ref([]);
const signalAnimatingScope = ref(null);
const signalAnimatingPathIndex = ref(-1);
let signalAnimFrame = null;
let signalAnimStartTime = null;

const SIGNAL_ANIM_LOOP_MS = 5000;

function isPathAnimating(scope, pathIndex) {
    return signalAnimating.value
        && signalAnimatingScope.value === scope
        && signalAnimatingPathIndex.value === pathIndex;
}

function animatedHopClass(hopIndex, pathIndex, scope) {
    if (!isPathAnimating(scope, pathIndex) || signalAnimHopIndex.value < 0) {
        return '';
    }

    if (hopIndex === signalAnimHopIndex.value) {
        return 'rounded-lg bg-theme-primary/10 px-1 py-1 -mx-1';
    }

    if (hopIndex < signalAnimHopIndex.value) {
        return 'opacity-80';
    }

    return 'opacity-45';
}

function animatedHopBadgeClass(hopIndex, pathIndex, scope) {
    if (isPathAnimating(scope, pathIndex) && hopIndex === signalAnimHopIndex.value) {
        return 'bg-theme-primary text-white shadow-sm ring-2 ring-theme-primary/30';
    }

    if (isPathAnimating(scope, pathIndex) && hopIndex < signalAnimHopIndex.value) {
        return 'bg-emerald-500/15 text-emerald-700';
    }

    return 'bg-theme-primary/10 text-theme-primary';
}

function stopSignalAnimation() {
    signalAnimating.value = false;
    signalAnimHopIndex.value = -1;
    signalPathProgress.value = 0;
    signalAnimHops.value = [];
    signalAnimatingScope.value = null;
    signalAnimatingPathIndex.value = -1;
    signalAnimStartTime = null;

    if (signalAnimFrame) {
        cancelAnimationFrame(signalAnimFrame);
        signalAnimFrame = null;
    }

    emit('signal-animate', null);
}

function buildSignalAnimatePayload(activeHopIndex, pathProgress) {
    const flowHops = [...(signalAnimHops.value ?? [])];

    return {
        flowHops,
        cableIds: flowHops.filter((hop) => hop.kind === 'cable').map((hop) => hop.cableId),
        pointIds: flowHops.filter((hop) => hop.kind === 'point').map((hop) => hop.pointId),
        active: true,
        activeHopIndex,
        pathProgress,
    };
}

function emitSignalAnimateFrame(activeHopIndex, pathProgress) {
    emit('signal-animate', buildSignalAnimatePayload(activeHopIndex, pathProgress));
}

function tickSignalAnimation(timestamp) {
    if (!signalAnimating.value) {
        return;
    }

    if (signalAnimStartTime == null) {
        signalAnimStartTime = timestamp;
    }

    const elapsed = (timestamp - signalAnimStartTime) % SIGNAL_ANIM_LOOP_MS;
    const pathProgress = elapsed / SIGNAL_ANIM_LOOP_MS;
    const hops = signalAnimHops.value;
    const hopIndex = hops.length
        ? Math.min(hops.length - 1, Math.floor(pathProgress * hops.length))
        : 0;

    signalPathProgress.value = pathProgress;
    signalAnimHopIndex.value = hopIndex;
    emitSignalAnimateFrame(hopIndex, pathProgress);
    signalAnimFrame = requestAnimationFrame(tickSignalAnimation);
}

function startSignalAnimationForHops(hops, scope, pathIndex) {
    if (!hops.length) {
        return;
    }

    stopSignalAnimation();

    signalAnimHops.value = hops;
    signalAnimatingScope.value = scope;
    signalAnimatingPathIndex.value = pathIndex;
    signalAnimating.value = true;
    signalAnimHopIndex.value = 0;
    signalPathProgress.value = 0;
    signalAnimStartTime = null;
    emitSignalAnimateFrame(0, 0);
    signalAnimFrame = requestAnimationFrame(tickSignalAnimation);
}

function toggleUpstreamSignalAnimation(pathIndex) {
    if (isPathAnimating('upstream', pathIndex)) {
        stopSignalAnimation();

        return;
    }

    const path = signalUpstreamPaths.value[pathIndex];

    if (!path) {
        return;
    }

    startSignalAnimationForHops(upstreamFlowHops(path), 'upstream', pathIndex);
}

function toggleDownstreamSignalAnimation(pathIndex) {
    if (isPathAnimating('downstream', pathIndex)) {
        stopSignalAnimation();

        return;
    }

    const path = signalDownstream.value[pathIndex];

    if (!path?.hops?.length) {
        return;
    }

    startSignalAnimationForHops(path.hops, 'downstream', pathIndex);
}

onBeforeUnmount(() => {
    stopSignalAnimation();
});

watch(() => props.point?.id, () => {
    stopSignalAnimation();
});

const signalDownstream = computed(() => {
    if (!props.point?.id) {
        return [];
    }

    return traceSignalDownstream(
        props.point.id,
        props.cables,
        pointsForSignalTrace.value,
        signalTypeLabels.value,
    );
});

function oltDeviceCardLabel(device) {
    const type = signalTypeLabels.value[device?.type] ?? device?.type;

    if (device?.label?.trim() && type) {
        return `${device.label.trim()} (${type})`;
    }

    return device?.label?.trim() || type || 'OLT';
}

const oltDownstreamGroups = computed(() => {
    if (!isOltPoint.value || !props.point?.id) {
        return [];
    }

    return oltDevicesOnPoint(props.point).map((device) => ({
        device,
        deviceLabel: oltDeviceCardLabel(device),
        items: signalDownstream.value
            .map((path, index) => ({ path, index }))
            .filter(({ path }) => String(path.sourceDeviceId) === String(device.id)),
    }));
});

function portConnection(port) {
    if (!props.point?.id || !port.id) {
        return null;
    }

    return findPortConnection(props.cables, props.point.id, port.id);
}

function formatPortConnectionLabel(connection) {
    return formatPortCableConnection(connection, t);
}

const portsSummary = computed(() => {
    let deviceCount = 0;
    let inputCount = 0;
    let outputCount = 0;

    form.devices.forEach((device) => {
        if (!device.label.trim()) {
            return;
        }

        deviceCount += 1;

        device.ports.forEach((port) => {
            if (!port.label.trim()) {
                return;
            }

            if (port.direction === 'input') {
                inputCount += 1;
            } else {
                outputCount += 1;
            }
        });
    });

    if (!deviceCount) {
        return '';
    }

    return t('points.devicesSummary', { devices: deviceCount, input: inputCount, output: outputCount });
});

function ensureDevicesForSelectedTypes() {
    syncDevicesWithTypes();
}

watch(() => [...form.types], () => {
    if (syncingFromPoint) {
        return;
    }

    syncDevicesWithTypes();

    if (activeTab.value.startsWith('device:')) {
        const activeType = activeTab.value.slice('device:'.length);

        if (!form.types.includes(activeType)) {
            activeTab.value = form.types.length ? deviceTabId(form.types[0]) : 'basic';
        }
    }
});

watch(() => props.point, (point) => {
    activeTab.value = 'basic';
    syncingFromPoint = true;

    if (!point) {
        Object.assign(form, emptyForm());
        activeTab.value = firstDeviceTabId();
        nextTick(() => {
            syncingFromPoint = false;
        });

        return;
    }

    Object.assign(form, {
        name: point.name ?? '',
        types: normalizePointTypes(point),
        status: point.status ?? 'active',
        area: point.area ?? '',
        latitude: point.latitude ?? 0,
        longitude: point.longitude ?? 0,
        address: point.address ?? '',
        notes: point.notes ?? '',
        contact_name: point.contact_name ?? '',
        contact_phone: point.contact_phone ?? '',
        devices: devicesFromPoint(point, props.meta.types ?? {}),
    });

    syncDevicesWithTypes();

    nextTick(() => {
        syncingFromPoint = false;
    });
}, { immediate: true });

watch(visibleTabs, (tabs) => {
    if (!tabs.some((tab) => tab.id === activeTab.value)) {
        activeTab.value = tabs[0]?.id ?? 'basic';
    }
});

watch(form, () => {
    if (syncingFromPoint || props.point?.id) {
        return;
    }

    emit('draft-change', { ...form });
}, { deep: true });

function onTypesChange() {
    typesError.value = '';

    if (form.types.length === 0) {
        form.types = ['junction'];
    }

    ensureDevicesForSelectedTypes();

    if (activeTab.value.startsWith('device:')) {
        const activeType = activeTab.value.slice('device:'.length);

        if (!form.types.includes(activeType)) {
            activeTab.value = deviceTabId(form.types[0]);
        }
    }
}

function addPort(deviceIndex) {
    form.devices[deviceIndex].ports.push(createPortRow({ direction: 'input' }));
}

function removePort(deviceIndex, portIndex) {
    form.devices[deviceIndex].ports.splice(portIndex, 1);
}

function serializeDevices() {
    return form.devices
        .map((device) => ({
            id: device.id,
            label: device.label.trim(),
            type: device.type,
            ports: device.ports
                .map((port) => ({
                    id: port.id,
                    label: port.label.trim(),
                    direction: port.direction,
                }))
                .filter((port) => port.label !== ''),
        }))
        .filter((device) => device.label !== '');
}

function save() {
    if (!form.types.length) {
        typesError.value = t('points.selectAtLeastOneType');

        return;
    }

    emit('save', {
        ...form,
        types: [...form.types],
        devices: serializeDevices(),
    });
}

function syncCoordinates(latitude, longitude) {
    syncingFromPoint = true;
    form.latitude = latitude;
    form.longitude = longitude;
    nextTick(() => {
        syncingFromPoint = false;
    });
}

defineExpose({ syncCoordinates });

function uploadImage(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    emit('upload-image', file);
    event.target.value = '';
}
</script>
