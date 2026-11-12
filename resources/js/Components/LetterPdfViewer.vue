<script setup>
import { computed, markRaw, nextTick, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const props = defineProps({
    pdfBase64: {
        type: String,
        default: null,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    errorMessage: {
        type: String,
        default: null,
    },
    initialScale: {
        type: Number,
        default: 1.05,
    },
    showOpenTab: {
        type: Boolean,
        default: true,
    },
});

const canvasRef = ref(null);
const pdfDoc = shallowRef(null);
const currentPage = ref(1);
const totalPages = ref(1);
const scale = ref(props.initialScale);
const isLoadingPdf = ref(false);
const renderError = ref(null);
const pdfBlobUrl = ref(null);
let currentRenderTask = null;

const createBlobUrl = (base64) => {
    if (pdfBlobUrl.value) {
        URL.revokeObjectURL(pdfBlobUrl.value);
        pdfBlobUrl.value = null;
    }

    if (!base64) return;

    try {
        const binaryString = atob(base64);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        const blob = new Blob([bytes], { type: 'application/pdf' });
        pdfBlobUrl.value = URL.createObjectURL(blob);
    } catch (e) {
        console.error('Error creating PDF Blob URL:', e);
    }
};

const loadPdf = async () => {
    if (!props.pdfBase64) {
        pdfDoc.value = null;
        totalPages.value = 1;
        currentPage.value = 1;
        return;
    }

    isLoadingPdf.value = true;
    renderError.value = null;

    try {
        const binaryString = atob(props.pdfBase64);
        const len = binaryString.length;
        const uint8Array = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            uint8Array[i] = binaryString.charCodeAt(i);
        }

        const loadingTask = pdfjsLib.getDocument({
            data: uint8Array,
            cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4/cmaps/',
            cMapPacked: true,
        });

        const doc = await loadingTask.promise;
        pdfDoc.value = markRaw(doc);
        totalPages.value = doc.numPages;
        if (currentPage.value > doc.numPages) {
            currentPage.value = 1;
        }
        isLoadingPdf.value = false;

        await nextTick();
        await renderPage(currentPage.value);
    } catch (e) {
        console.error('Error loading PDF with PDF.js:', e);
        renderError.value = 'Gagal memproses tampilan dokumen PDF.';
        isLoadingPdf.value = false;
    }
};

const renderPage = async (pageNumber) => {
    if (!pdfDoc.value || !canvasRef.value) return;

    try {
        if (currentRenderTask) {
            try {
                currentRenderTask.cancel();
                await currentRenderTask.promise;
            } catch (_) {
                // Ignore cancel exception
            }
        }

        const page = await pdfDoc.value.getPage(pageNumber);
        const viewport = page.getViewport({ scale: scale.value });
        const canvas = canvasRef.value;
        const context = canvas.getContext('2d');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        currentRenderTask = page.render({ canvasContext: context, viewport });
        await currentRenderTask.promise;
    } catch (err) {
        if (err?.name !== 'RenderingCancelledException') {
            console.error('Error rendering page:', err);
        }
    }
};

const goToPage = async (pageNumber) => {
    if (pageNumber < 1 || pageNumber > totalPages.value || pageNumber === currentPage.value) return;
    currentPage.value = pageNumber;
    await renderPage(pageNumber);
};

const changePage = async (delta) => {
    const target = currentPage.value + delta;
    if (target >= 1 && target <= totalPages.value) {
        await goToPage(target);
    }
};

const zoom = async (delta) => {
    const newScale = Math.min(Math.max(scale.value + delta, 0.6), 2.2);
    scale.value = Math.round(newScale * 100) / 100;
    await renderPage(currentPage.value);
};

const zoomPercentage = computed(() => Math.round(scale.value * 100) + '%');

const openInNewTab = () => {
    if (pdfBlobUrl.value) {
        window.open(pdfBlobUrl.value, '_blank');
    }
};

watch(
    () => props.pdfBase64,
    (newVal) => {
        if (newVal) {
            createBlobUrl(newVal);
            loadPdf();
        } else {
            pdfDoc.value = null;
        }
    },
    { immediate: true }
);

onBeforeUnmount(() => {
    if (pdfBlobUrl.value) {
        URL.revokeObjectURL(pdfBlobUrl.value);
        pdfBlobUrl.value = null;
    }
    if (currentRenderTask) {
        try {
            currentRenderTask.cancel();
        } catch (_) {}
    }
});

defineExpose({
    reload: loadPdf,
    openInNewTab,
});
</script>

<template>
    <div class="flex flex-col h-full bg-slate-100 dark:bg-slate-950 rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-800">
        <!-- Sub-Toolbar for Multi-page & Zoom controls -->
        <div class="px-4 py-2.5 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5 shrink-0 select-none">
            <!-- Left: Page Stepper / Multi-page Tabs -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <!-- Multi-page Tabs -->
                <div v-if="totalPages > 1" class="flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-xs">
                    <button
                        v-for="p in totalPages"
                        :key="p"
                        type="button"
                        @click="goToPage(p)"
                        :class="[
                            'px-2.5 py-1 rounded-lg font-bold transition flex items-center gap-1 text-[11px]',
                            currentPage === p
                                ? 'bg-blue-700 text-white shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <span>Hal {{ p }}</span>
                        <span v-if="totalPages === 2 && p === 1" class="hidden sm:inline text-[9px] opacity-80">(Surat)</span>
                        <span v-else-if="totalPages === 2 && p === 2" class="hidden sm:inline text-[9px] opacity-80">(Lampiran)</span>
                    </button>
                </div>

                <!-- Stepper (< 1/2 >) -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-xs">
                    <button
                        type="button"
                        @click="changePage(-1)"
                        :disabled="currentPage <= 1"
                        class="p-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent transition"
                        title="Halaman Sebelumnya"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <span class="px-2 font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap text-[11px]">
                        Hal {{ currentPage }} / {{ totalPages }}
                    </span>
                    <button
                        type="button"
                        @click="changePage(1)"
                        :disabled="currentPage >= totalPages"
                        class="p-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent transition"
                        title="Halaman Berikutnya"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Right: Zoom Controls & Open Tab Button -->
            <div class="flex items-center gap-2">
                <!-- Zoom Controls -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700/80 text-xs">
                    <button
                        type="button"
                        @click="zoom(-0.15)"
                        class="p-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                        title="Perkecil (-)"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </button>
                    <span class="px-1.5 font-mono text-[11px] font-semibold text-slate-700 dark:text-slate-300 w-11 text-center">
                        {{ zoomPercentage }}
                    </span>
                    <button
                        type="button"
                        @click="zoom(0.15)"
                        class="p-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                        title="Perbesar (+)"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <!-- Open in New Tab Button -->
                <button
                    v-if="showOpenTab && pdfBlobUrl && !isLoading"
                    type="button"
                    @click="openInNewTab"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-700 dark:hover:text-blue-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition shadow-2xs"
                    title="Buka dokumen di tab baru browser"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span class="hidden sm:inline">Tab Baru</span>
                </button>
            </div>
        </div>

        <!-- Body: Canvas Document Sheet Viewport -->
        <div class="flex-1 overflow-auto p-3 sm:p-5 flex flex-col items-center justify-start relative min-h-[460px]">
            <!-- Loading State -->
            <div v-if="isLoading || isLoadingPdf" class="flex flex-col items-center justify-center p-12 text-slate-500 m-auto">
                <svg class="animate-spin w-8 h-8 text-blue-600 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Memperbarui Pratinjau PDF...</span>
                <span class="text-[11px] text-slate-400 mt-0.5">Menyinkronkan redaksi kalimat dan tata letak dokumen</span>
            </div>

            <!-- Error State -->
            <div v-else-if="errorMessage || renderError" class="p-8 text-center max-w-md mx-auto space-y-3 m-auto">
                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">Gagal Memuat Pratinjau Dokumen</h4>
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">
                        {{ errorMessage || renderError }}
                    </p>
                </div>
            </div>

            <!-- Canvas Sheet -->
            <div
                v-show="!isLoading && !isLoadingPdf && !errorMessage && !renderError && pdfBase64"
                class="relative inline-block shadow-2xl rounded-sm overflow-hidden bg-white border border-slate-200 dark:border-slate-800 my-auto transition-transform"
            >
                <canvas ref="canvasRef" class="block"></canvas>
            </div>
        </div>
    </div>
</template>

