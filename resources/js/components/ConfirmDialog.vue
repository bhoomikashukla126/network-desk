<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="state.visible"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/50 p-4"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="platform-confirm-title"
                aria-describedby="platform-confirm-message"
                @click.self="cancel"
            >
                <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                    <h2 id="platform-confirm-title" class="text-lg font-semibold text-slate-900">
                        {{ state.title }}
                    </h2>
                    <p id="platform-confirm-message" class="mt-2 text-sm leading-relaxed text-slate-600">
                        {{ state.message }}
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            ref="cancelButtonRef"
                            type="button"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            @click="cancel"
                        >
                            {{ state.cancelLabel }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
                            :class="state.variant === 'primary'
                                ? 'bg-indigo-600 hover:bg-indigo-700'
                                : 'bg-rose-600 hover:bg-rose-700'"
                            @click="accept"
                        >
                            {{ state.confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { resolveConfirm, useConfirmState } from '../composables/useConfirm';

const state = useConfirmState();
const cancelButtonRef = ref(null);

watch(
    () => state.visible,
    async (visible) => {
        document.body.classList.toggle('overflow-hidden', visible);

        if (visible) {
            await nextTick();
            cancelButtonRef.value?.focus();
        }
    },
);

function accept() {
    resolveConfirm(true);
}

function cancel() {
    resolveConfirm(false);
}
</script>
