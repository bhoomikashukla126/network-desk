<template>
    <aside
        class="app-card flex h-full min-h-0 flex-col overflow-hidden rounded-2xl border p-4 shadow-sm sm:p-5"
        @mousedown.stop
        @click.stop
    >
        <div class="mb-3 flex shrink-0 items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-theme-heading">
                    {{ form.name || defaultTitle }}
                </h2>
                <p class="mt-0.5 text-xs text-theme-muted">{{ $t('cables.detail') }}</p>
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

        <div class="mb-3 grid shrink-0 grid-cols-5 gap-1 border-b border-theme pb-1">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="inline-flex items-center justify-center rounded-t-lg px-2 py-2.5 transition"
                :class="activeTab === tab.id ? 'app-tab-active' : 'app-tab-inactive'"
                :aria-label="tab.label"
                :title="tab.label"
                @click.stop="setActiveTab(tab.id)"
                @mousedown.stop
            >
                <component :is="tab.icon" class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>

        <form class="flex min-h-0 flex-1 flex-col overflow-hidden" @submit.prevent="save">
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div v-show="activeTab === 'basic'" class="space-y-3 pb-2">
                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="cable-name">{{ $t('cables.fields.name') }}</label>
                        <input
                            id="cable-name"
                            v-model="form.name"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :placeholder="$t('cables.namePlaceholder')"
                            :disabled="!canEdit"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="cable-type">{{ $t('cables.fields.type') }}</label>
                            <select
                                id="cable-type"
                                v-model="form.cable_type"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                :disabled="!canEdit"
                            >
                                <option v-for="(label, key) in meta.cable_types" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <div v-if="canEdit" class="mt-2 space-y-2">
                                <button
                                    v-if="!showAddCableType"
                                    type="button"
                                    class="text-xs font-medium text-theme-primary transition hover:underline"
                                    @click="showAddCableType = true"
                                >
                                    {{ $t('map.addCableType') }}
                                </button>
                                <div v-else class="flex flex-wrap items-center gap-2">
                                    <input
                                        v-model="newCableTypeLabel"
                                        type="text"
                                        maxlength="80"
                                        class="app-input min-w-0 flex-1 rounded-xl border px-3 py-1.5 text-sm shadow-sm"
                                        :placeholder="$t('map.newCableTypePlaceholder')"
                                        @keyup.enter="submitAddCableType"
                                    >
                                    <button
                                        type="button"
                                        class="btn-primary rounded-lg px-2.5 py-1.5 text-xs font-semibold transition disabled:opacity-60"
                                        :disabled="savingCableType || !newCableTypeLabel.trim()"
                                        @click="submitAddCableType"
                                    >
                                        {{ savingCableType ? $t('common.loading') : $t('map.saveCableType') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-secondary rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition"
                                        @click="cancelAddCableType"
                                    >
                                        {{ $t('common.cancel') }}
                                    </button>
                                </div>
                                <p v-if="cableTypeError" class="text-xs text-rose-600">{{ cableTypeError }}</p>
                                <div v-if="meta.custom_cable_types?.length" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="type in meta.custom_cable_types"
                                        :key="type.id"
                                        class="inline-flex items-center gap-1 rounded-full border border-theme bg-theme-background px-2 py-0.5 text-[11px] text-theme-body"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :style="{ background: type.color }" />
                                        {{ type.label }}
                                        <button
                                            type="button"
                                            class="rounded px-0.5 text-theme-muted transition hover:text-rose-600"
                                            :aria-label="$t('map.removeCableType', { name: type.label })"
                                            @click="$emit('remove-cable-type', type)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="cable-status">{{ $t('cables.fields.status') }}</label>
                            <select
                                id="cable-status"
                                v-model="form.status"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                :disabled="!canEdit"
                            >
                                <option v-for="(label, key) in meta.cable_statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-theme bg-theme-background px-3 py-2.5">
                            <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.fields.mapDistance') }}</label>
                            <p class="text-lg font-semibold text-theme-heading">{{ formattedMapDistance }}</p>
                            <p class="mt-0.5 text-xs text-theme-muted">{{ $t('cables.mapDistanceHint') }}</p>
                        </div>
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" for="cable-length">{{ $t('cables.fields.actualLength') }}</label>
                            <input
                                id="cable-length"
                                v-model.number="form.length_m"
                                type="number"
                                min="0"
                                step="any"
                                class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                                :placeholder="$t('cables.actualLengthPlaceholder')"
                                :disabled="!canEdit"
                            >
                            <button
                                v-if="canEdit && mapDistanceM != null"
                                type="button"
                                class="mt-1 text-xs font-medium text-theme-primary transition hover:underline"
                                @click="useMapDistance"
                            >
                                {{ $t('cables.useMapDistance') }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-xl border border-theme bg-theme-background px-3 py-2.5 text-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-theme-muted">{{ $t('cables.fields.route') }}</p>
                        <p class="mt-1 text-theme-body">
                            <template v-for="(label, index) in routePointLabels" :key="`${label}-${index}`">
                                <span v-if="index > 0" class="mx-1 text-theme-muted">→</span>
                                <span class="font-medium">{{ label }}</span>
                            </template>
                        </p>
                        <p v-if="bendCount" class="mt-1 text-xs text-theme-muted">
                            {{ $t('cables.bendCount', { count: bendCount }) }}
                        </p>
                    </div>
                </div>

                <div v-show="activeTab === 'cores'" class="space-y-3 pb-2">
                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="cable-core-count">{{ $t('cables.fields.coreCount') }}</label>
                        <input
                            id="cable-core-count"
                            v-model.number="form.core_count"
                            type="number"
                            min="0"
                            max="288"
                            step="1"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :placeholder="$t('cables.coreCountPlaceholder')"
                            :disabled="!canEdit"
                            @input="onCoreCountChange"
                        >
                        <p class="mt-1 text-xs text-theme-muted">{{ $t('cables.coresHint') }}</p>
                    </div>

                    <div v-if="form.core_count > 0" class="rounded-xl border border-theme bg-theme-background px-3 py-2 text-xs text-theme-muted">
                        <span class="font-medium text-theme-body">{{ $t('cables.fields.route') }}:</span>
                        {{ routeStartLabel }} → {{ routeEndLabel }}
                    </div>

                    <p v-if="!form.core_count" class="app-dashed-empty rounded-xl border border-dashed px-4 py-6 text-center text-sm">
                        {{ $t('cables.noCores') }}
                    </p>

                    <p v-else-if="!coresTabReady" class="app-dashed-empty rounded-xl border border-dashed px-4 py-6 text-center text-sm">
                        {{ $t('common.loading') }}
                    </p>

                    <div v-else class="space-y-3">
                        <article
                            v-for="core in form.cores"
                            :key="core.core_number"
                            class="rounded-xl border border-theme bg-theme-background p-3"
                        >
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="h-4 w-4 shrink-0 rounded-full border border-theme"
                                    :style="{ background: core.color }"
                                />
                                <p class="text-sm font-semibold text-theme-heading">
                                    {{ $t('cables.coreNumber', { number: core.core_number }) }}
                                </p>
                            </div>

                            <div class="mb-3 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.coreColor') }}</label>
                                    <select
                                        v-model="core.color"
                                        class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                        :disabled="!canEdit"
                                    >
                                        <option
                                            v-for="option in fiberColorOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ fiberColorLabel(option) }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.coreLabel') }}</label>
                                    <input
                                        v-model="core.label"
                                        type="text"
                                        maxlength="255"
                                        class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                        :disabled="!canEdit"
                                    >
                                </div>
                            </div>

                            <div
                                v-for="side in coreSides"
                                :key="`${core.core_number}-${side.id}`"
                                :class="side.id === 'end' ? 'mt-3' : ''"
                            >
                                <p class="mb-2 text-xs font-medium text-theme-heading">
                                    {{ side.label }}
                                    <span class="font-normal text-theme-muted">· {{ $t('cables.atPoint', { name: side.pointLabel }) }}</span>
                                    <span v-if="sidePointTypeLabel(side)" class="font-normal text-theme-muted"> · {{ sidePointTypeLabel(side) }}</span>
                                </p>
                                <div class="space-y-2 rounded-lg border border-dashed border-theme px-2.5 py-2">
                                    <div v-if="sideConnectionModesList(side).length > 1">
                                        <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.connectionType') }}</label>
                                        <select
                                            v-model="core.ends[side.id].connection_type"
                                            class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                            :disabled="!canEdit"
                                        >
                                            <option value="">{{ $t('cables.connectionNone') }}</option>
                                            <option value="device">{{ $t('cables.connectionDevice') }}</option>
                                            <option value="core_end">{{ $t('cables.connectionCore') }}</option>
                                        </select>
                                    </div>

                                    <template v-if="activeConnectionMode(core, side) === 'device'">
                                        <div v-if="devicePortGroupsWithAvailablePorts(side, core, side.id).length">
                                            <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.selectDevicePort') }}</label>
                                            <p class="mb-1 text-[11px] text-theme-muted">
                                                {{ side.id === 'start' ? $t('cables.devicePortAtStart', { name: side.pointLabel }) : $t('cables.devicePortAtEnd', { name: side.pointLabel }) }}
                                            </p>
                                            <select
                                                :value="core.ends[side.id].network_point_port_id"
                                                class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                                :disabled="!canEdit"
                                                @change="selectDevicePort(core, side.id, $event.target.value)"
                                            >
                                                <option value="">{{ $t('cables.connectionNone') }}</option>
                                                <optgroup
                                                    v-for="group in devicePortGroupsWithAvailablePorts(side, core, side.id)"
                                                    :key="group.device.id ?? group.device._key ?? group.device.label"
                                                    :label="deviceGroupLabel(group.device)"
                                                >
                                                    <option
                                                        v-for="port in group.ports"
                                                        :key="port.id"
                                                        :value="port.id"
                                                    >
                                                        {{ formatDevicePortOptionLabel(port, group.device, side) }}
                                                    </option>
                                                </optgroup>
                                            </select>
                                            <p
                                                v-if="deviceConnectionSummary(core, side)"
                                                class="mt-1.5 rounded-lg border border-theme bg-theme-background px-2 py-1.5 text-[11px] text-theme-body"
                                            >
                                                <span class="font-medium text-theme-heading">{{ $t('cables.deviceConnectionSummary') }}:</span>
                                                {{ deviceConnectionSummary(core, side) }}
                                            </p>
                                        </div>
                                        <p v-else-if="devicePortGroupsForSidePreferred(side).length" class="text-xs text-theme-muted">
                                            {{ $t('cables.noAvailablePortsAtPoint') }}
                                        </p>
                                        <p v-else class="text-xs text-theme-muted">{{ $t('cables.noDefinedPorts') }}</p>

                                        <template v-if="!devicePortGroupsForSide(side).length">
                                            <div>
                                                <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.deviceType') }}</label>
                                                <select
                                                    v-model="core.ends[side.id].device_type"
                                                    class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                                    :disabled="!canEdit"
                                                >
                                                    <option value="">{{ $t('cables.connectionNone') }}</option>
                                                    <option v-for="(label, key) in meta.types" :key="key" :value="key">{{ label }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="app-label mb-1 block text-xs font-medium">{{ $t('points.fields.deviceLabel') }}</label>
                                                <input
                                                    v-model="core.ends[side.id].device_label"
                                                    type="text"
                                                    maxlength="255"
                                                    class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                                    :disabled="!canEdit"
                                                >
                                            </div>
                                            <div>
                                                <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.devicePortLabel') }}</label>
                                                <input
                                                    v-model="core.ends[side.id].device_port_label"
                                                    type="text"
                                                    maxlength="255"
                                                    class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                                    :disabled="!canEdit"
                                                >
                                            </div>
                                            <div>
                                                <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.devicePortDirection') }}</label>
                                                <select
                                                    v-model="core.ends[side.id].device_port_direction"
                                                    class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                                    :disabled="!canEdit"
                                                >
                                                    <option value="input">{{ $t('cables.portInput') }}</option>
                                                    <option value="output">{{ $t('cables.portOutput') }}</option>
                                                </select>
                                            </div>
                                        </template>
                                    </template>

                                    <div v-else-if="activeConnectionMode(core, side) === 'core_end'">
                                        <label class="app-label mb-1 block text-xs font-medium">{{ $t('cables.selectCoreEnd') }}</label>
                                        <p class="mb-1 text-[11px] text-theme-muted">{{ $t('cables.coreEndAtPoint', { name: side.pointLabel }) }}</p>
                                        <select
                                            :value="core.ends[side.id].connected_core_end_id ?? ''"
                                            class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                            :disabled="!canEdit"
                                            @change="selectCoreEnd(core, side.id, $event.target.value)"
                                        >
                                            <option value="">{{ $t('cables.connectionNone') }}</option>
                                            <optgroup
                                                v-for="group in connectionOptionGroupsForCore(core, side.id)"
                                                :key="group.cable_id"
                                                :label="spliceCableGroupLabel(group)"
                                            >
                                                <option
                                                    v-for="option in group.options"
                                                    :key="option.id"
                                                    :value="option.id"
                                                >
                                                    {{ formatCoreConnectionOptionLabel(option) }}
                                                </option>
                                            </optgroup>
                                        </select>
                                        <p v-if="coresNeedingSpliceOnSide(side.id) > 0" class="mb-1 text-[11px] text-theme-muted">
                                            {{ $t('cables.coresNeedingSplice', { count: coresNeedingSpliceOnSide(side.id) }) }}
                                        </p>
                                        <p v-if="!connectionOptionsForCore(core, side.id).length" class="mt-1 text-xs text-theme-muted">
                                            {{ $t('cables.noCoreEndsAtPoint') }}
                                        </p>
                                    </div>

                                    <p v-else class="text-xs text-theme-muted">{{ $t('cables.noConnectionOptionsAtPoint') }}</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-show="activeTab === 'route'" class="space-y-4 pb-2">
                    <p class="text-xs text-theme-muted">{{ $t('cables.routeOpsHint') }}</p>

                    <section class="rounded-xl border border-theme bg-theme-background p-3">
                        <div class="mb-2 flex items-center gap-2">
                            <Scissors class="h-4 w-4 text-theme-primary" />
                            <h3 class="text-sm font-semibold text-theme-heading">{{ $t('cables.splitCable') }}</h3>
                        </div>
                        <p class="mb-3 text-xs text-theme-muted">{{ $t('cables.splitCableHint') }}</p>

                        <div v-if="splitOptions.length" class="space-y-2">
                            <label class="app-label block text-xs font-medium" for="split-point">{{ $t('cables.splitAtPoint') }}</label>
                            <select
                                id="split-point"
                                v-model="splitPointId"
                                class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                :disabled="!canEdit || routeOperating"
                            >
                                <option v-for="option in splitOptions" :key="option.point_id" :value="option.point_id">
                                    {{ option.name || `#${option.point_id}` }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="btn-secondary w-full rounded-lg border px-3 py-2 text-sm font-semibold transition disabled:opacity-60"
                                :disabled="!canEdit || routeOperating || !splitPointId"
                                @click="submitSplit"
                            >
                                {{ routeOperating ? $t('common.loading') : $t('cables.splitCableAction') }}
                            </button>
                        </div>
                        <p v-else class="text-sm text-theme-muted">{{ $t('cables.splitUnavailable') }}</p>
                    </section>

                    <section class="rounded-xl border border-theme bg-theme-background p-3">
                        <div class="mb-2 flex items-center gap-2">
                            <Link2 class="h-4 w-4 text-theme-primary" />
                            <h3 class="text-sm font-semibold text-theme-heading">{{ $t('cables.joinCable') }}</h3>
                        </div>
                        <p class="mb-3 text-xs text-theme-muted">{{ $t('cables.joinCableHint') }}</p>

                        <div v-if="joinCandidates.length" class="space-y-2">
                            <label class="app-label block text-xs font-medium" for="join-cable">{{ $t('cables.joinWith') }}</label>
                            <select
                                id="join-cable"
                                v-model="joinCableId"
                                class="app-input w-full rounded-lg border px-2 py-1.5 text-xs shadow-sm"
                                :disabled="!canEdit || routeOperating"
                            >
                                <option v-for="candidate in joinCandidates" :key="candidate.id" :value="candidate.id">
                                    {{ candidate.label }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="btn-primary w-full rounded-lg px-3 py-2 text-sm font-semibold transition disabled:opacity-60"
                                :disabled="!canEdit || routeOperating || !joinCableId"
                                @click="submitJoin"
                            >
                                {{ routeOperating ? $t('common.loading') : $t('cables.joinCableAction') }}
                            </button>
                        </div>
                        <p v-else class="text-sm text-theme-muted">{{ $t('cables.joinUnavailable') }}</p>
                    </section>

                    <p v-if="routeError" class="text-xs text-rose-600">{{ routeError }}</p>
                </div>

                <div v-show="activeTab === 'notes'" class="space-y-3">
                    <div>
                        <label class="app-label mb-1 block text-sm font-medium" for="cable-notes">{{ $t('cables.fields.notes') }}</label>
                        <textarea
                            id="cable-notes"
                            v-model="form.notes"
                            rows="5"
                            class="app-input w-full rounded-xl border px-3.5 py-2 text-sm shadow-sm"
                            :placeholder="$t('cables.notesPlaceholder')"
                            :disabled="!canEdit"
                        />
                    </div>
                </div>

                <div v-show="activeTab === 'photos'" class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="app-label text-sm font-medium">{{ $t('cables.sections.attachments') }}</p>
                        <label v-if="canEdit" class="btn-accent-outline inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-semibold transition">
                            <Paperclip class="h-4 w-4" />
                            {{ $t('cables.uploadAttachment') }}
                            <input type="file" accept="image/*,.pdf" class="hidden" @change="uploadAttachment">
                        </label>
                    </div>

                    <div v-if="cable?.images?.length" class="grid grid-cols-2 gap-2">
                        <figure
                            v-for="image in cable.images"
                            :key="image.id"
                            class="overflow-hidden rounded-xl border border-theme"
                        >
                            <a
                                v-if="isPdf(image)"
                                :href="image.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex h-20 flex-col items-center justify-center gap-1 bg-theme-background px-2 text-center"
                            >
                                <FileText class="h-6 w-6 text-theme-primary" />
                                <span class="truncate text-[10px] text-theme-muted">{{ image.caption || $t('cables.document') }}</span>
                            </a>
                            <img
                                v-else
                                :src="image.url"
                                :alt="image.caption || form.name"
                                class="h-20 w-full object-cover"
                            >
                            <figcaption class="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span class="truncate text-xs text-theme-muted">{{ image.caption || attachmentLabel(image) }}</span>
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
                        {{ $t('cables.noAttachments') }}
                    </p>
                </div>
            </div>

            <div class="mt-3 flex shrink-0 flex-col gap-2 border-t border-theme pt-3">
                <p v-if="saveError" class="text-xs text-rose-600">{{ saveError }}</p>
                <div class="flex flex-wrap gap-2">
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
                    v-if="canDelete"
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
            </div>
        </form>
    </aside>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Cable, FileText, Image, Link2, LoaderCircle, NotebookText, Paperclip, Scissors, Share2, Trash2, X } from 'lucide-vue-next';
import {
    applyAutoCoreEndDefaults,
    buildCoresForm,
    cableTypeLabel,
    cableMapDistanceFromRelations,
    cableRouteNodes,
    cableRoutePointIds,
    collectCoreConnectionOptionsAtPoint,
    collectUsedPortIds,
    coreFormFromApi,
    countCoresNeedingSpliceOnSide,
    FIBER_CORE_COLOR_OPTIONS,
    FIBER_CORE_COLORS,
    fiberCoreColorName,
    filterCoreSpliceOptionsByCableCapacity,
    formatCoreConnectionOption,
    formatDeviceConnectionSummary,
    formatDevicePortOption,
    formatDistanceM,
    groupCoreConnectionOptionsByCable,
    isCoreEndAvailableForSplice,
    preferredPortDirectionForCoreSide,
    pointTypeLabels,
    serializeCoresForm,
    sideConnectionModes,
} from '../../utils/networkMap';

const props = defineProps({
    cable: { type: Object, required: true },
    meta: { type: Object, required: true },
    points: { type: Array, default: () => [] },
    cables: { type: Array, default: () => [] },
    connectionOptions: { type: Array, default: () => [] },
    splitOptions: { type: Array, default: () => [] },
    joinCandidates: { type: Array, default: () => [] },
    routeOperating: { type: Boolean, default: false },
    routeError: { type: String, default: '' },
    canEdit: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    savingCableType: { type: Boolean, default: false },
    cableTypeError: { type: String, default: '' },
    saveError: { type: String, default: '' },
});

const emit = defineEmits([
    'close',
    'save',
    'delete',
    'upload-image',
    'delete-image',
    'add-cable-type',
    'remove-cable-type',
    'split-cable',
    'join-cable',
    'panel-interaction',
]);

const { t } = useI18n();
const activeTab = ref('basic');
const coresTabReady = ref(false);
const form = reactive(emptyForm());
const showAddCableType = ref(false);
const newCableTypeLabel = ref('');
const splitPointId = ref(null);
const joinCableId = ref(null);
const loadedCableId = ref(null);
let spliceOptionsCacheKey = '';
const spliceOptionsCache = new Map();
let usedPortsCacheKey = '';
let usedPortsCache = new Map();

const tabs = computed(() => [
    { id: 'basic', label: t('cables.tabs.basic'), icon: Cable },
    { id: 'route', label: t('cables.tabs.route'), icon: Link2 },
    { id: 'cores', label: t('cables.tabs.cores'), icon: Share2 },
    { id: 'notes', label: t('cables.tabs.notes'), icon: NotebookText },
    { id: 'photos', label: t('cables.tabs.attachments'), icon: Image },
]);

const fiberPalette = computed(() => (
    props.cable?.fiber_core_colors?.length ? props.cable.fiber_core_colors : FIBER_CORE_COLORS
));

const fiberColorOptions = computed(() => {
    if (props.cable?.fiber_core_color_options?.length) {
        return props.cable.fiber_core_color_options.map((option) => ({
            value: option.value,
            nameKey: fiberCoreColorName(option.value) ?? option.name?.toLowerCase(),
            name: option.name,
        }));
    }

    return FIBER_CORE_COLOR_OPTIONS;
});

function fiberColorLabel(option) {
    if (option.nameKey) {
        const key = `cables.colors.${option.nameKey}`;

        if (t(key) !== key) {
            return t(key);
        }
    }

    return option.name ?? option.value;
}

const defaultTitle = computed(() => {
    if (!props.cable) {
        return t('cables.defaultTitle');
    }

    return `${cableTypeLabel(props.cable.cable_type)} #${props.cable.id}`;
});

const pointsById = computed(() => {
    const mapById = {};

    const mergePoint = (point) => {
        if (!point?.id) {
            return;
        }

        const existing = mapById[point.id];
        const existingDevices = existing?.devices?.length ?? 0;
        const incomingDevices = point.devices?.length ?? 0;

        if (!existing || incomingDevices >= existingDevices) {
            mapById[point.id] = existing && incomingDevices === 0
                ? { ...point, devices: existing.devices }
                : point;
        }
    };

    props.points.forEach(mergePoint);

    if (props.cable?.from_point) {
        mergePoint(props.cable.from_point);
    }

    if (props.cable?.fromPoint) {
        mergePoint(props.cable.fromPoint);
    }

    if (props.cable?.to_point) {
        mergePoint(props.cable.to_point);
    }

    if (props.cable?.toPoint) {
        mergePoint(props.cable.toPoint);
    }

    return mapById;
});

const routePointLabels = computed(() => {
    const nodes = cableRouteNodes(props.cable);

    return nodes
        .filter((node) => node.type === 'point')
        .map((node) => {
            const point = pointsById.value[node.point_id];

            return point?.name ?? `#${node.point_id}`;
        });
});

const routeStartLabel = computed(() => routePointLabels.value[0] ?? '—');
const routeEndLabel = computed(() => routePointLabels.value[routePointLabels.value.length - 1] ?? '—');

const coreSides = computed(() => {
    const pointIds = cableRoutePointIds(props.cable);

    return [
        {
            id: 'start',
            label: t('cables.endStart'),
            pointLabel: routeStartLabel.value,
            pointId: pointIds[0] ?? null,
        },
        {
            id: 'end',
            label: t('cables.endFinish'),
            pointLabel: routeEndLabel.value,
            pointId: pointIds[pointIds.length - 1] ?? null,
        },
    ];
});

function devicesForSide(side) {
    if (!side?.pointId) {
        return [];
    }

    const point = pointsById.value[side.pointId];

    return point?.devices ?? [];
}

function sidePointTypeLabel(side) {
    if (!side?.pointId) {
        return '';
    }

    const point = pointsById.value[side.pointId];

    if (!point) {
        return '';
    }

    return pointTypeLabels(point, props.meta?.types ?? {}).join(', ');
}

function sourceCables() {
    return props.cables?.length ? props.cables : [props.cable];
}

function invalidateCoreOptionCaches() {
    spliceOptionsCacheKey = '';
    spliceOptionsCache.clear();
    usedPortsCacheKey = '';
    usedPortsCache.clear();
}

function spliceOptionsForPoint(pointId) {
    const cacheKey = `${normalizeCableId(props.cable?.id)}:${props.cables?.length ?? 0}`;

    if (cacheKey !== spliceOptionsCacheKey) {
        spliceOptionsCacheKey = cacheKey;
        spliceOptionsCache.clear();
    }

    if (!spliceOptionsCache.has(pointId)) {
        spliceOptionsCache.set(
            pointId,
            collectCoreConnectionOptionsAtPoint(sourceCables(), pointId, {}),
        );
    }

    return spliceOptionsCache.get(pointId) ?? [];
}

function sideConnectionModesList(side) {
    const point = pointsById.value[side.pointId];

    return sideConnectionModes(point, sourceCables(), side.pointId, side.id);
}

function devicePortGroupsForSidePreferred(side) {
    const preferred = preferredPortDirectionForCoreSide(side.id);

    return devicePortGroupsForSide(side)
        .map((group) => ({
            ...group,
            ports: group.ports.filter((port) => port.direction === preferred),
        }))
        .filter((group) => group.ports.length > 0);
}

function portsClaimedInForm(excludeCoreNumber = null, excludeSideId = null) {
    const claimed = new Set();

    for (const core of form.cores) {
        for (const sideId of ['start', 'end']) {
            if (core.core_number === excludeCoreNumber && sideId === excludeSideId) {
                continue;
            }

            const portId = core.ends[sideId].network_point_port_id;

            if (portId) {
                claimed.add(Number(portId));
            }
        }
    }

    return claimed;
}

function devicePortGroupsWithAvailablePorts(side, core, sideId) {
    const preferredGroups = devicePortGroupsForSidePreferred(side);
    const groups = preferredGroups.length > 0 ? preferredGroups : devicePortGroupsForSide(side);

    return groups
        .map((group) => ({
            ...group,
            ports: portsAvailableForGroup(side, core, sideId, group),
        }))
        .filter((group) => group.ports.length > 0);
}

function activeConnectionMode(core, side) {
    const end = core.ends[side.id];

    if (end.connection_type === 'device') {
        if (end.network_point_port_id || String(end.device_port_label ?? '').trim()) {
            return 'device';
        }
    }

    if (end.connection_type === 'core_end') {
        if (end.connected_core_end_id) {
            return 'core_end';
        }
    }

    const modes = sideConnectionModesList(side);

    if (modes.includes('device')) {
        return 'device';
    }

    if (modes.includes('core_end')) {
        return 'core_end';
    }

    return '';
}

function applyCoreEndDefaults() {
    if (!props.cable || !form.core_count) {
        return;
    }

    applyAutoCoreEndDefaults(form.cores, props.cable, sourceCables(), pointsById.value);
}

function devicePortGroupsForSide(side) {
    return devicesForSide(side)
        .map((device) => ({
            device,
            ports: (device.ports ?? []).filter((port) => port.label),
        }))
        .filter((group) => group.ports.length > 0);
}

function deviceGroupLabel(device) {
    const typeLabel = props.meta.types?.[device.type] ?? device.type;

    return device.label ? `${device.label} (${typeLabel})` : typeLabel;
}

function usedPortIds(excludeEndId = null) {
    if (excludeEndId) {
        return collectUsedPortIds(sourceCables(), { excludeEndId });
    }

    const cacheKey = `${normalizeCableId(props.cable?.id)}:${props.cables?.length ?? 0}`;

    if (cacheKey !== usedPortsCacheKey) {
        usedPortsCacheKey = cacheKey;
        usedPortsCache = collectUsedPortIds(sourceCables());
    }

    return usedPortsCache;
}

function ownCoreEndId(core, sideId) {
    return props.cable?.cores
        ?.find((item) => item.core_number === core.core_number)
        ?.ends?.[sideId]?.id ?? null;
}

function isPortInUseByOtherCore(portId, core, sideId) {
    const used = usedPortIds(ownCoreEndId(core, sideId));
    const selected = Number(core.ends[sideId].network_point_port_id);

    if (selected === Number(portId)) {
        return false;
    }

    return used.has(Number(portId));
}

function portInUseSuffix(portId, core, sideId) {
    if (!isPortInUseByOtherCore(portId, core, sideId)) {
        return '';
    }

    return ` · ${t('cables.portAlreadyInUse')}`;
}

function portsAvailableForGroup(side, core, sideId, group) {
    const selectedId = Number(core.ends[sideId].network_point_port_id);
    const claimed = portsClaimedInForm(core.core_number, sideId);

    return group.ports.filter((port) => {
        const portId = Number(port.id);

        if (portId === selectedId) {
            return true;
        }

        if (isPortInUseByOtherCore(portId, core, sideId)) {
            return false;
        }

        return !claimed.has(portId);
    });
}

function formatDevicePortOptionLabel(port, device, side) {
    return formatDevicePortOption(port, device, t, side?.pointLabel, props.meta.types ?? {});
}

function deviceConnectionSummary(core, side) {
    return formatDeviceConnectionSummary(core.ends[side.id], side, props.meta.types ?? {}, t);
}

function formatCoreConnectionOptionLabel(option) {
    return formatCoreConnectionOption(option, t);
}

function portsForSide(side) {
    return devicePortGroupsForSide(side).flatMap((group) => group.ports);
}

function selectDevicePort(core, sideId, value) {
    const portId = value ? Number(value) : null;
    core.ends[sideId].network_point_port_id = portId;

    if (!portId) {
        core.ends[sideId].connection_type = '';
        core.ends[sideId].network_point_device_id = null;
        core.ends[sideId].device_type = '';
        core.ends[sideId].device_label = '';
        core.ends[sideId].device_port_label = '';
        core.ends[sideId].device_port_direction = 'output';
        core.ends[sideId].connected_core_end_id = null;

        return;
    }

    core.ends[sideId].connection_type = 'device';
    core.ends[sideId].connected_core_end_id = null;

    const side = coreSides.value.find((item) => item.id === sideId);
    const group = devicePortGroupsForSide(side).find((entry) => (
        entry.ports.some((item) => Number(item.id) === portId)
    ));
    const port = group?.ports.find((item) => Number(item.id) === portId);
    const device = group?.device;

    if (port && device) {
        core.ends[sideId].network_point_device_id = device.id ?? null;
        core.ends[sideId].device_type = device.type ?? '';
        core.ends[sideId].device_label = device.label ?? '';
        core.ends[sideId].device_port_label = port.label;
        core.ends[sideId].device_port_direction = port.direction;
    }

    invalidateCoreOptionCaches();
}

function selectCoreEnd(core, sideId, value) {
    core.ends[sideId].connected_core_end_id = value ? Number(value) : null;

    if (!value) {
        core.ends[sideId].connection_type = '';
        invalidateCoreOptionCaches();

        return;
    }

    core.ends[sideId].connection_type = 'core_end';
    core.ends[sideId].network_point_port_id = null;
    core.ends[sideId].network_point_device_id = null;
    core.ends[sideId].device_type = '';
    core.ends[sideId].device_label = '';
    core.ends[sideId].device_port_label = '';
    core.ends[sideId].device_port_direction = preferredPortDirectionForCoreSide(sideId);
    invalidateCoreOptionCaches();
}

const bendCount = computed(() => (
    cableRouteNodes(props.cable).filter((node) => node.type === 'bend').length
));

const mapDistanceM = computed(() => {
    if (props.cable?.map_distance_m != null) {
        return Number(props.cable.map_distance_m);
    }

    return cableMapDistanceFromRelations(props.cable);
});

const formattedMapDistance = computed(() => formatDistanceM(mapDistanceM.value));

function useMapDistance() {
    if (mapDistanceM.value == null) {
        return;
    }

    form.length_m = Math.round(mapDistanceM.value * 100) / 100;
}

function emptyForm() {
    return {
        name: '',
        cable_type: 'fiber',
        status: 'active',
        length_m: null,
        notes: '',
        core_count: 0,
        cores: [],
    };
}

function normalizeCableId(id) {
    return id != null && id !== '' ? String(id) : null;
}

function setActiveTab(tabId) {
    activeTab.value = tabId;
    emit('panel-interaction');

    if (tabId === 'cores') {
        scheduleCoresTabRender();
    }
}

function syncCoresFormToCount({ applyDefaults = false } = {}) {
    const count = Math.max(0, Math.min(288, Number(form.core_count) || 0));
    form.core_count = count;

    if (count <= 0) {
        form.cores = [];
        coresTabReady.value = false;
        invalidateCoreOptionCaches();

        return;
    }

    const preserved = form.cores.length > 0
        ? form.cores
        : (props.cable?.cores ?? []).map((core) => coreFormFromApi(core, fiberPalette.value));

    form.cores = buildCoresForm(count, preserved, fiberPalette.value);
    coresTabReady.value = true;
    invalidateCoreOptionCaches();

    if (applyDefaults) {
        applyCoreEndDefaults();
    }
}

function scheduleCoresTabRender() {
    nextTick(() => {
        requestAnimationFrame(() => {
            syncCoresFormToCount({ applyDefaults: true });
        });
    });
}

function populateFormFromCable(cable) {
    if (!cable) {
        Object.assign(form, emptyForm());
        splitPointId.value = null;
        joinCableId.value = null;

        return;
    }

    Object.assign(form, {
        name: cable.name ?? '',
        cable_type: cable.cable_type ?? 'fiber',
        status: cable.status ?? 'active',
        length_m: cable.length_m ?? null,
        notes: cable.notes ?? '',
    });
    syncCoresFromCable(cable);
    splitPointId.value = props.splitOptions[0]?.point_id ?? null;
    joinCableId.value = props.joinCandidates[0]?.id ?? null;
}

function syncCoresFromCable(cable) {
    form.core_count = Number(cable?.core_count ?? 0);
    form.cores = [];
    coresTabReady.value = false;
    invalidateCoreOptionCaches();

    if (activeTab.value === 'cores' && form.core_count > 0) {
        scheduleCoresTabRender();
    }
}

function onCoreCountChange() {
    syncCoresFormToCount({ applyDefaults: true });
}

function reservedSpliceEndIds(excludeCoreNumber = null, excludeSideId = null) {
    const ids = new Set();

    for (const core of form.cores) {
        for (const sideId of ['start', 'end']) {
            if (core.core_number === excludeCoreNumber && sideId === excludeSideId) {
                continue;
            }

            const partnerId = core.ends?.[sideId]?.connected_core_end_id;

            if (partnerId) {
                ids.add(Number(partnerId));
            }
        }
    }

    return ids;
}

function coresNeedingSpliceOnSide(sideId) {
    return countCoresNeedingSpliceOnSide(form.cores, sideId);
}

function spliceCableGroupLabel(group) {
    return t('cables.spliceCableGroup', {
        name: group.cable_name,
        count: group.available,
    });
}

function connectionOptionsForCore(core, sideId) {
    const sideMeta = coreSides.value.find((item) => item.id === sideId);
    const ownEndId = ownCoreEndId(core, sideId);
    const currentPartnerId = core.ends?.[sideId]?.connected_core_end_id ?? null;
    const reservedEndIds = reservedSpliceEndIds(core.core_number, sideId);
    const pointId = sideMeta?.pointId ? Number(sideMeta.pointId) : null;

    const rawOptions = pointId
        ? spliceOptionsForPoint(pointId)
        : props.connectionOptions.filter((option) => {
            if (ownEndId && Number(option.id) === Number(ownEndId)) {
                return false;
            }

            if (pointId && Number(option.network_point_id) !== pointId) {
                return false;
            }

            return true;
        });

    return filterCoreSpliceOptionsByCableCapacity(rawOptions, {
        cables: sourceCables(),
        pointId,
        coresNeedingSplice: 1,
        reservedEndIds,
        currentEndId: ownEndId,
        currentPartnerId,
    }).filter((option) => {
        if (ownEndId && Number(option.id) === Number(ownEndId)) {
            return false;
        }

        if (Number(option.cable_id) === Number(props.cable?.id)) {
            return false;
        }

        return isCoreEndAvailableForSplice(option, {
            currentEndId: ownEndId,
            currentPartnerId,
        });
    });
}

function connectionOptionGroupsForCore(core, sideId) {
    const sideMeta = coreSides.value.find((item) => item.id === sideId);
    const pointId = sideMeta?.pointId ? Number(sideMeta.pointId) : null;

    if (!pointId) {
        return [];
    }

    const options = connectionOptionsForCore(core, sideId);

    return groupCoreConnectionOptionsByCable(options, sourceCables(), pointId, {
        reservedEndIds: reservedSpliceEndIds(core.core_number, sideId),
        currentEndId: ownCoreEndId(core, sideId),
        currentPartnerId: core.ends?.[sideId]?.connected_core_end_id ?? null,
    }).filter((group) => group.options.length > 0);
}

watch(() => normalizeCableId(props.cable?.id), (cableId, previousId) => {
    if (cableId === previousId) {
        return;
    }

    loadedCableId.value = cableId;
    activeTab.value = 'basic';
    coresTabReady.value = false;
    showAddCableType.value = false;
    newCableTypeLabel.value = '';
    populateFormFromCable(props.cable);
}, { immediate: true });

watch(() => props.saving, (saving, wasSaving) => {
    if (wasSaving && !saving && props.cable?.id) {
        populateFormFromCable(props.cable);
    }
});

watch(() => props.splitOptions, (options) => {
    if (!splitPointId.value && options?.length) {
        splitPointId.value = options[0].point_id;
    }
}, { deep: true });

watch(() => props.joinCandidates, (candidates) => {
    if (!joinCableId.value && candidates?.length) {
        joinCableId.value = candidates[0].id;
    }
}, { deep: true });

function submitSplit() {
    if (!splitPointId.value) {
        return;
    }

    emit('split-cable', Number(splitPointId.value));
}

function submitJoin() {
    if (!joinCableId.value) {
        return;
    }

    emit('join-cable', Number(joinCableId.value));
}

function save() {
    syncCoresFormToCount();
    const corePayload = serializeCoresForm(form.core_count, form.cores);

    emit('save', {
        route: cableRouteNodes(props.cable),
        name: form.name || null,
        cable_type: form.cable_type,
        status: form.status,
        length_m: form.length_m,
        notes: form.notes,
        ...corePayload,
    });
}

function uploadAttachment(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    emit('upload-image', file);
    event.target.value = '';
}

function isPdf(image) {
    return image.mime_type === 'application/pdf' || String(image.url ?? '').toLowerCase().endsWith('.pdf');
}

function attachmentLabel(image) {
    return isPdf(image) ? t('cables.document') : t('cables.photo');
}

function cancelAddCableType() {
    showAddCableType.value = false;
    newCableTypeLabel.value = '';
}

function submitAddCableType() {
    const label = newCableTypeLabel.value.trim();

    if (!label) {
        return;
    }

    emit('add-cable-type', label);
    cancelAddCableType();
}

watch(() => props.meta.cable_types, (types) => {
    if (form.cable_type && types && !types[form.cable_type]) {
        form.cable_type = 'fiber';
    }
}, { deep: true });
</script>
