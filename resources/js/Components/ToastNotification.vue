<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();

const isVisible = ref(false);
const message = ref('');
const type = ref('success'); // 'success' | 'error'
const progress = ref(100);
const isPaused = ref(false);

const DURATION = 5000; // 5 detik
let remainingTime = DURATION;
let progressInterval = null;
let lastTick = null;
let removeFinishListener = null;

function clearTimers() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

function startTimer() {
    clearTimers();
    lastTick = Date.now();

    progressInterval = setInterval(() => {
        if (isPaused.value) {
            lastTick = Date.now();
            return;
        }

        const now = Date.now();
        const delta = now - lastTick;
        lastTick = now;

        remainingTime = Math.max(0, remainingTime - delta);
        progress.value = (remainingTime / DURATION) * 100;

        if (remainingTime <= 0) {
            closeToast();
        }
    }, 50);
}

function showToast(msg, toastType = 'success') {
    if (!msg) return;
    message.value = msg;
    type.value = toastType;
    remainingTime = DURATION;
    progress.value = 100;
    isPaused.value = false;
    isVisible.value = true;
    startTimer();
}

function closeToast() {
    isVisible.value = false;
    clearTimers();
}

function pauseTimer() {
    isPaused.value = true;
}

function resumeTimer() {
    isPaused.value = false;
    lastTick = Date.now();
}

function checkFlashMessages() {
    const flash = page.props.flash;
    if (flash?.success) {
        showToast(flash.success, 'success');
    } else if (flash?.error) {
        showToast(flash.error, 'error');
    }
}

// Watch Inertia flash reactively
watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([newSuccess, newError]) => {
        if (newSuccess) {
            showToast(newSuccess, 'success');
        } else if (newError) {
            showToast(newError, 'error');
        }
    },
    { deep: true }
);

onMounted(() => {
    checkFlashMessages();

    // Pastikan menangkap setiap respon Inertia baru (misal setelah aksi hapus/simpan surat)
    removeFinishListener = router.on('finish', () => {
        checkFlashMessages();
    });
});

onUnmounted(() => {
    if (removeFinishListener) {
        removeFinishListener();
    }
    clearTimers();
});
</script>

<template>
    <teleport to="body">
        <div
            aria-live="assertive"
            class="fixed inset-0 pointer-events-none flex items-end justify-end p-4 sm:p-6 z-50"
        >
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-5 opacity-0 sm:translate-y-0 sm:translate-x-6 scale-95"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 translate-x-4 scale-95"
            >
                <div
                    v-if="isVisible"
                    @mouseenter="pauseTimer"
                    @mouseleave="resumeTimer"
                    role="alert"
                    class="pointer-events-auto w-full max-w-sm sm:max-w-md rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/90 dark:border-slate-800/90 shadow-2xl shadow-slate-900/10 dark:shadow-black/60 overflow-hidden transition-all duration-200 hover:shadow-3xl"
                    :class="type === 'success' ? 'border-emerald-500/30' : 'border-rose-500/30'"
                >
                    <div class="p-4 sm:p-4.5 flex items-start gap-3.5">
                        <!-- Glowing Icon Badge -->
                        <div
                            v-if="type === 'success'"
                            class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 ring-4 ring-emerald-500/10"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div
                            v-else
                            class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 ring-4 ring-rose-500/10"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <!-- Content Details -->
                        <div class="flex-1 min-w-0 pt-0.5">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    v-if="type === 'success'"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-extrabold tracking-wider uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                >
                                    Berhasil
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-0.5 rounded-md text-[10px] font-extrabold tracking-wider uppercase bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                >
                                    Perhatian
                                </span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                    SILAS-TTE Fasilkom
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug break-words">
                                {{ message }}
                            </p>
                        </div>

                        <!-- Close Button -->
                        <button
                            type="button"
                            @click="closeToast"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0"
                            title="Tutup pemberitahuan"
                            aria-label="Tutup pemberitahuan"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Subtle Animated Timer Progress Bar -->
                    <div class="h-1 w-full bg-slate-100 dark:bg-slate-800/80 overflow-hidden">
                        <div
                            class="h-full transition-all duration-75 ease-linear"
                            :class="type === 'success' ? 'bg-emerald-500' : 'bg-rose-500'"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>
            </Transition>
        </div>
    </teleport>
</template>

