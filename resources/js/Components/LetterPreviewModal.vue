<script setup>
import LetterPdfViewer from '@/Components/LetterPdfViewer.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Pratinjau Hasil Cetak PDF Surat',
    },
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
});

const emit = defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div
            @click="emit('close')"
            class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
        ></div>

        <div class="flex min-h-full items-center justify-center p-2 sm:p-4">
            <!-- Modal Dialog Container (max-w-5xl for full readability) -->
            <div
                class="relative w-full max-w-5xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col h-[92vh]"
            >
                <!-- Modal Header -->
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/90 dark:bg-slate-800/60 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-700 text-white flex items-center justify-center shadow-xs shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white leading-tight">
                                {{ title }}
                            </h3>
                            <p class="text-[11px] text-slate-400">
                                Simulasi cetak surat resmi Fasilkom Universitas Subang dengan data contoh
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition"
                        title="Tutup"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body: Embedded LetterPdfViewer -->
                <div class="flex-1 p-2 sm:p-4 overflow-hidden flex flex-col min-h-0 bg-slate-50 dark:bg-slate-950">
                    <LetterPdfViewer
                        :pdfBase64="pdfBase64"
                        :isLoading="isLoading"
                        :errorMessage="errorMessage"
                        :initialScale="1.15"
                    />
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between text-xs shrink-0">
                    <span class="text-[11px] text-slate-400">
                        💡 Pratinjau ini langsung menggunakan narasi yang sedang diketik pada formulir template
                    </span>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-700 hover:bg-blue-800 shadow-xs transition"
                    >
                        Tutup Pratinjau
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
