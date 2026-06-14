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
                        <p class="text-sm font-semibold text-theme-heading">
                            {{ meta.types?.[type] ?? type }}
                        </p>
                        <p class="mt-0.5 text-xs text-theme-muted">{{ $t('points.deviceTabHint') }}</p>
                    </div>

                    <template v-if="deviceEntryForType(type)">
                        <div>
                            <label class="app-label mb-1 block text-sm font-medium" :for="`device-label-${type}`">
                                {{ $t('points.fields.deviceLabel') }}
                            </label>
                            <input
                                :id="`device-label-${type}`"
                                v-model="deviceEntryForType(type).device.label"
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
                                @click="addPort(deviceEntryForType(type).index)"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                {{ $t('points.addPort') }}
                            </button>
                        </div>

                        <div v-if="deviceEntryForType(type).device.ports.length" class="space-y-2">
                            <article
                                v-for="(port, portIndex) in deviceEntryForType(type).device.ports"
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
                                        @click="removePort(deviceEntryForType(type).index, portIndex)"
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
                    </template>
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

                    <div v-if="isCustomerPoint" class="rounded-xl border border-theme px-3 py-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="signalStatusClass"
                                />
                                <p class="text-sm font-medium text-theme-heading">
                                    {{ signalStatusLabel }}
                                </p>
                            </div>
                            <button
                                v-if="signalFlowHops.length"
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="signalAnimating ? 'btn-secondary border border-theme' : 'btn-primary'"
                                @click="toggleSignalAnimation"
                            >
                                <Radio class="h-3.5 w-3.5" :class="signalAnimating ? 'animate-pulse text-emerald-600' : ''" />
                                {{ signalAnimating ? $t('points.stopSignalAnimation') : $t('points.animateSignal') }}
                            </button>
                        </div>
                        <p v-if="signalOltHop" class="mb-1 text-xs font-medium text-theme-heading">
                            {{ $t('points.signalServiceFromOlt', { olt: signalOltHop.pointName, port: signalOltHop.portLabel }) }}
                        </p>
                        <p v-if="signalBackhaulOrigin" class="mb-3 text-xs text-theme-muted">
                            {{ $t('points.signalBackhaulFrom', { origin: signalBackhaulOrigin }) }}
                        </p>
                        <p v-else-if="signalUpstream.receivingSignal && signalUpstream.originName && !signalOltHop" class="mb-3 text-xs text-theme-muted">
                            {{ $t('points.signalFromOrigin', { origin: signalUpstream.originName }) }}
                        </p>
                        <p v-else-if="signalUpstream.partialPath" class="mb-3 text-xs text-amber-700 dark:text-amber-300">
                            {{ $t('points.signalPartialHint') }}
                        </p>
                        <ol v-if="signalFlowHops.length" class="relative space-y-2">
                            <div
                                v-if="signalAnimating"
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
                                v-for="(hop, index) in signalFlowHops"
                                :key="`up-${index}-${hop.kind}-${hop.pointId ?? hop.cableId ?? hop.label}`"
                                class="relative flex gap-2 text-xs transition-colors duration-300"
                                :class="signalHopClass(index)"
                            >
                                <span
                                    v-if="index > 0"
                                    class="pointer-events-none absolute -top-2 left-2.5 h-2 w-px bg-theme-primary/30"
                                    aria-hidden="true"
                                />
                                <span
                                    class="relative z-[1] mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full font-semibold transition-all duration-300"
                                    :class="signalHopBadgeClass(index)"
                                >
                                    <ArrowDown
                                        v-if="signalAnimating && signalAnimHopIndex === index && index > 0"
                                        class="h-3 w-3 animate-bounce"
                                    />
                                    <template v-else>{{ index + 1 }}</template>
                                </span>
                                <div class="min-w-0">
                                    <p v-if="hop.kind === 'point'" class="font-medium text-theme-heading">
                                        {{ hop.pointName }}
                                        <span class="font-normal text-theme-muted">· {{ hop.portLabel }} ({{ hop.portDirection === 'input' ? $t('cables.portInput') : $t('cables.portOutput') }})</span>
                                    </p>
                                    <p v-else-if="hop.kind === 'splice'" class="text-theme-body">
                                        <span class="font-medium text-theme-heading">{{ hop.pointName }}</span>
                                        <span class="text-theme-muted"> · {{ $t('points.signalSplice') }} · {{ hop.label }}</span>
                                    </p>
                                    <p v-else class="text-theme-body">
                                        <span class="font-medium">{{ hop.cableName }}</span>
                                        <span class="text-theme-muted"> · {{ hop.coreCount }}C · Core {{ hop.coreNumber }} · {{ hop.fromPort }} → {{ hop.toPort }}</span>
                                    </p>
                                </div>
                            </li>
                        </ol>
                        <p v-else class="text-xs text-theme-muted">{{ $t('points.noSignalPath') }}</p>
                    </div>

                    <div v-if="isOltPoint" class="rounded-xl border border-theme px-3 py-3">
                        <p class="mb-2 text-sm font-medium text-theme-heading">{{ $t('points.signalIncomingBackhaul') }}</p>
                        <p v-if="signalUpstream.receivingSignal && signalUpstream.originName" class="mb-3 text-xs text-theme-muted">
                            {{ $t('points.signalFedBy', { origin: signalUpstream.originName }) }}
                        </p>
                        <p v-else-if="signalUpstream.partialPath" class="mb-3 text-xs text-amber-700 dark:text-amber-300">
                            {{ $t('points.signalPartialHint') }}
                        </p>
                        <ol v-if="signalFlowHops.length" class="space-y-2">
                            <li
                                v-for="(hop, index) in signalIncomingFlowHops"
                                :key="`in-${index}-${hop.kind}-${hop.pointId ?? hop.cableId ?? hop.label}`"
                                class="flex gap-2 text-xs text-theme-body"
                            >
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-theme-primary/10 text-[11px] font-semibold text-theme-primary">
                                    {{ index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <template v-if="hop.kind === 'point'">
                                        <span class="font-medium text-theme-heading">{{ hop.pointName }}</span>
                                        <span class="text-theme-muted"> · {{ hop.portLabel }} ({{ hop.portDirection === 'input' ? $t('cables.portInput') : $t('cables.portOutput') }})</span>
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
                        <p v-else class="text-xs text-theme-muted">{{ $t('points.noSignalPath') }}</p>
                    </div>

                    <div v-if="isHeadEndPoint || isOltPoint" class="space-y-3">
                        <p class="text-sm font-medium text-theme-heading">
                            {{ isOltPoint ? $t('points.signalOutgoingPon') : $t('points.downstreamSignals') }}
                        </p>
                        <p v-if="!signalDownstream.length" class="text-xs text-theme-muted">{{ $t('points.noDownstreamSignals') }}</p>
                        <details
                            v-for="(path, index) in signalDownstream"
                            :key="`down-${index}-${path.label}`"
                            class="rounded-xl border border-theme bg-theme-background px-3 py-2"
                        >
                            <summary class="cursor-pointer text-xs font-medium text-theme-heading">{{ path.label }}</summary>
                            <ol class="mt-2 space-y-2 border-t border-theme pt-2">
                                <li
                                    v-for="(hop, hopIndex) in path.hops"
                                    :key="`down-hop-${index}-${hopIndex}`"
                                    class="text-xs text-theme-body"
                                >
                                    <template v-if="hop.kind === 'point'">
                                        {{ hop.pointName }} · {{ hop.portLabel }} ({{ hop.portDirection === 'input' ? $t('cables.portInput') : $t('cables.portOutput') }})
                                    </template>
                                    <template v-else-if="hop.kind === 'splice'">
                                        {{ hop.pointName }} · {{ $t('points.signalSplice') }} · {{ hop.label }}
                                    </template>
                                    <template v-else>
                                        {{ hop.cableName }} · {{ hop.coreCount }}C · Core {{ hop.coreNumber }} · {{ hop.fromPort }} → {{ hop.toPort }}
                                    </template>
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
    normalizePointTypes,
    oltHopFromFlowHops,
    pointColor,
    signalFlowOrder,
    traceMetaFromHops,
    traceSignalDownstream,
    traceSignalUpstream,
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

    const seenTypes = new Set();

    devices = devices.filter((device) => {
        if (!types.includes(device.type) || seenTypes.has(device.type)) {
            return false;
        }

        seenTypes.add(device.type);

        return true;
    });

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

    const seenTypes = new Set();

    for (let index = form.devices.length - 1; index >= 0; index -= 1) {
        const { type } = form.devices[index];

        if (seenTypes.has(type)) {
            form.devices.splice(index, 1);
        } else {
            seenTypes.add(type);
        }
    }
}

function deviceEntryForType(type) {
    const index = form.devices.findIndex((device) => device.type === type);

    if (index === -1) {
        return null;
    }

    return {
        device: form.devices[index],
        index,
    };
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
        tabs.push({
            id: deviceTabId(type),
            type,
            label: props.meta.types?.[type] ?? type,
            shortLabel: props.meta.types?.[type] ?? type,
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

const signalUpstream = computed(() => {
    if (!props.point?.id) {
        return { hops: [], receivingSignal: false, originName: null, partialPath: false };
    }

    return traceSignalUpstream(props.point.id, props.cables, props.points);
});

const signalFlowHops = computed(() => signalFlowOrder(signalUpstream.value.hops));

const signalOltHop = computed(() => {
    if (!isCustomerPoint.value) {
        return null;
    }

    return oltHopFromFlowHops(signalFlowHops.value, props.points);
});

const signalBackhaulOrigin = computed(() => {
    if (!signalUpstream.value.receivingSignal || !signalUpstream.value.originName) {
        return null;
    }

    const oltName = signalOltHop.value?.pointName;

    if (oltName && signalUpstream.value.originName === oltName) {
        return null;
    }

    return signalUpstream.value.originName;
});

/** Backhaul hops only: origin → OLT (stops before PON distribution). */
const signalIncomingFlowHops = computed(() => {
    if (!isOltPoint.value) {
        return [];
    }

    const hops = signalFlowHops.value;
    const oltIndex = hops.findIndex((hop) => Number(hop.pointId) === Number(props.point?.id));

    if (oltIndex >= 0) {
        return hops.slice(0, oltIndex + 1);
    }

    return hops;
});

const signalStatusLabel = computed(() => {
    if (signalUpstream.value.receivingSignal) {
        return t('points.signalReceiving');
    }

    if (signalUpstream.value.partialPath) {
        return t('points.signalPartial');
    }

    return t('points.signalNotReceiving');
});

const signalStatusClass = computed(() => {
    if (signalUpstream.value.receivingSignal) {
        return 'bg-emerald-500';
    }

    if (signalUpstream.value.partialPath) {
        return 'bg-amber-500';
    }

    return 'bg-rose-500';
});

const signalAnimating = ref(false);
const signalAnimHopIndex = ref(-1);
const signalPathProgress = ref(0);
let signalAnimFrame = null;
let signalAnimStartTime = null;

const SIGNAL_ANIM_LOOP_MS = 5000;

function signalHopClass(index) {
    if (!signalAnimating.value || signalAnimHopIndex.value < 0) {
        return '';
    }

    if (index === signalAnimHopIndex.value) {
        return 'rounded-lg bg-theme-primary/10 px-1 py-1 -mx-1';
    }

    if (index < signalAnimHopIndex.value) {
        return 'opacity-80';
    }

    return 'opacity-45';
}

function signalHopBadgeClass(index) {
    if (signalAnimating.value && index === signalAnimHopIndex.value) {
        return 'bg-theme-primary text-white shadow-sm ring-2 ring-theme-primary/30';
    }

    if (signalAnimating.value && index < signalAnimHopIndex.value) {
        return 'bg-emerald-500/15 text-emerald-700';
    }

    return 'bg-theme-primary/10 text-theme-primary';
}

function stopSignalAnimation() {
    signalAnimating.value = false;
    signalAnimHopIndex.value = -1;
    signalPathProgress.value = 0;
    signalAnimStartTime = null;

    if (signalAnimFrame) {
        cancelAnimationFrame(signalAnimFrame);
        signalAnimFrame = null;
    }

    emit('signal-animate', null);
}

function emitSignalAnimateFrame(activeHopIndex, pathProgress) {
    const meta = traceMetaFromHops(signalUpstream.value.hops);

    emit('signal-animate', {
        ...meta,
        active: true,
        activeHopIndex,
        pathProgress,
    });
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
    const hops = signalFlowHops.value;
    const hopIndex = hops.length
        ? Math.min(hops.length - 1, Math.floor(pathProgress * hops.length))
        : 0;

    signalPathProgress.value = pathProgress;
    signalAnimHopIndex.value = hopIndex;
    emitSignalAnimateFrame(hopIndex, pathProgress);
    signalAnimFrame = requestAnimationFrame(tickSignalAnimation);
}

function startSignalAnimation() {
    const hops = signalFlowHops.value;

    if (!hops.length || signalAnimating.value) {
        return;
    }

    signalAnimating.value = true;
    signalAnimHopIndex.value = 0;
    signalPathProgress.value = 0;
    signalAnimStartTime = null;
    emitSignalAnimateFrame(0, 0);
    signalAnimFrame = requestAnimationFrame(tickSignalAnimation);
}

function toggleSignalAnimation() {
    if (signalAnimating.value) {
        stopSignalAnimation();

        return;
    }

    startSignalAnimation();
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

    return traceSignalDownstream(props.point.id, props.cables, props.points);
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
