<script setup>
import { ref, shallowRef, markRaw, onMounted, computed, watch, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SignaturePadModal from '@/Components/SignaturePadModal.vue';
import Modal from '@/Components/Modal.vue';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const props = defineProps({
    document: Object,
    pdfUrl: String,
    pdfBase64: String,
    certificates: Array,
    availableDrivers: Array,
    signingOfficials: {
        type: Array,
        default: () => [],
    },
    verificationUrl: String,
    qrDataUri: String,
    backUrl: String,
});

// PDF Rendering State (shallowRef prevents Vue Proxy from breaking PDF.js private members #pagesNumber)
const canvasRef = ref(null);
const containerRef = ref(null);
const pdfDoc = shallowRef(null);
const currentPage = ref(1);
const totalPages = ref(props.document.total_pages || 1);
const scale = ref(1.0);
const isLoadingPdf = ref(true);
const pdfError = ref(null);
const pdfPageWidth = ref(800);
const pdfPageHeight = ref(1100);
let currentRenderTask = null;

// Studio State
const selectedField = ref(null);
const showSignModal = ref(false);
const activeFieldToSign = ref(null);
const showFinalizeModal = ref(false);
const showAuditDrawer = ref(false);
const showAddFieldModal = ref(false);
const isPreviewMode = ref(false);

// PDF Preview Modal State (Rendered directly via PDF.js Canvas to prevent browser download triggers)
const showPreviewPdfModal = ref(false);
const isPreviewLoading = ref(false);
const previewError = ref(null);
const previewCanvasRef = ref(null);
const previewPdfDoc = shallowRef(null);
const previewCurrentPage = ref(1);
const previewTotalPages = ref(1);
const previewScale = ref(1.15);
let previewRenderTask = null;

const zoomPreview = async (delta) => {
    const newScale = Math.max(0.6, Math.min(2.5, previewScale.value + delta));
    if (newScale !== previewScale.value) {
        previewScale.value = Math.round(newScale * 100) / 100;
        await renderPreviewPage(previewCurrentPage.value);
    }
};

const openPdfPreviewModal = async () => {
    showPreviewPdfModal.value = true;
    isPreviewLoading.value = true;
    previewError.value = null;
    previewCurrentPage.value = currentPage.value;

    try {
        const response = await fetch(route('documents.preview-pdf', props.document.uuid) + '?t=' + Date.now());
        if (!response.ok) {
            throw new Error(`Gagal mengambil berkas pratinjau (HTTP ${response.status})`);
        }
        const arrayBuffer = await response.arrayBuffer();
        if (!arrayBuffer || arrayBuffer.byteLength === 0) {
            throw new Error('Berkas pratinjau yang diterima kosong.');
        }

        const uint8Array = new Uint8Array(arrayBuffer);
        const loadingTask = pdfjsLib.getDocument({
            data: uint8Array,
            cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4/cmaps/',
            cMapPacked: true,
        });

        const rawDoc = await loadingTask.promise;
        previewPdfDoc.value = markRaw(rawDoc);
        previewTotalPages.value = rawDoc.numPages;
        isPreviewLoading.value = false;

        await nextTick();
        await renderPreviewPage(previewCurrentPage.value);
    } catch (err) {
        console.error('Preview error:', err);
        previewError.value = err.message || 'Gagal memuat pratinjau PDF.';
        isPreviewLoading.value = false;
    }
};

const renderPreviewPage = async (pageNumber) => {
    if (!previewPdfDoc.value || !previewCanvasRef.value) return;

    try {
        if (previewRenderTask) {
            try {
                previewRenderTask.cancel();
                await previewRenderTask.promise;
            } catch (cancelErr) {
                // Ignore cancel exception
            }
        }

        const page = await previewPdfDoc.value.getPage(pageNumber);
        const viewport = page.getViewport({ scale: previewScale.value });
        const canvas = previewCanvasRef.value;
        const context = canvas.getContext('2d');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        previewRenderTask = page.render({ canvasContext: context, viewport });
        await previewRenderTask.promise;
    } catch (err) {
        if (err?.name !== 'RenderingCancelledException') {
            console.error('Error rendering preview page:', err);
        }
    }
};

const changePreviewPage = async (delta) => {
    const target = previewCurrentPage.value + delta;
    if (target >= 1 && target <= previewTotalPages.value) {
        previewCurrentPage.value = target;
        await renderPreviewPage(target);
    }
};

const proceedToFinalizeFromPreview = () => {
    showPreviewPdfModal.value = false;
    showFinalizeModal.value = true;
};

// New Field Form State
const setQuickSigner = (name, email) => {
    newFieldForm.value.signer_name = name;
    newFieldForm.value.signer_email = email;
};

const newFieldForm = ref({
    signer_name: props.document.user?.name || 'Penandatangan',
    signer_email: props.document.user?.email || 'signer@example.com',
    role: 'signer',
    field_type: 'signature',
    page_number: 1,
    pos_x: 65,
    pos_y: 75,
    width: 28,
    height: 12,
});

// Watch field_type to dynamically adjust width & height aspect ratio
watch(
    () => newFieldForm.value.field_type,
    (type) => {
        const aspect = (pdfPageWidth.value && pdfPageHeight.value)
            ? (pdfPageWidth.value / pdfPageHeight.value)
            : (210 / 297);
        if (type === 'qr_code') {
            newFieldForm.value.width = 13;
            newFieldForm.value.height = Math.round((13 * aspect) * 10) / 10;
        } else if (type === 'stamp') {
            newFieldForm.value.width = 15;
            newFieldForm.value.height = Math.round((15 * aspect) * 10) / 10;
            newFieldForm.value.signer_name = 'Stempel Resmi Fakultas';
            newFieldForm.value.signer_email = 'fasilkom@unsub.ac.id';
        } else {
            newFieldForm.value.width = 28;
            newFieldForm.value.height = 12;
            newFieldForm.value.signer_name = props.document.user?.name || 'Penandatangan';
        }
    }
);

// Finalize Form State
const finalizeForm = ref({
    driver: 'local',
    digital_certificate_id: props.certificates.find((c) => c.is_default)?.id || (props.certificates[0]?.id ?? null),
});

const isFinalizing = ref(false);

// Filter fields on current page (handling Eloquent snake_case 'signature_fields')
const currentFields = computed(() => {
    const fields = props.document.signature_fields || props.document.signatureFields || [];
    return fields.filter(
        (field) => Number(field.page_number) === Number(currentPage.value)
    );
});

// Load PDF Document
const loadPdf = async () => {
    isLoadingPdf.value = true;
    pdfError.value = null;
    try {
        let uint8Array = null;

        // If Base64 data is provided directly via Inertia props, use it immediately (zero network fetch)
        if (props.pdfBase64) {
            const binaryString = atob(props.pdfBase64);
            const len = binaryString.length;
            uint8Array = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                uint8Array[i] = binaryString.charCodeAt(i);
            }
        } else if (props.pdfUrl) {
            // Fallback fetch from streaming endpoint
            const response = await fetch(props.pdfUrl);
            if (!response.ok) {
                throw new Error(`Gagal mengambil berkas PDF dari server (HTTP ${response.status})`);
            }
            const arrayBuffer = await response.arrayBuffer();
            if (!arrayBuffer || arrayBuffer.byteLength === 0) {
                throw new Error('Berkas PDF yang diterima kosong (0 bytes). Silakan periksa kembali berkas.');
            }
            uint8Array = new Uint8Array(arrayBuffer);
        }

        if (!uint8Array || uint8Array.length === 0) {
            throw new Error('Berkas PDF kosong atau tidak ditemukan di server.');
        }

        // Pass typed array data object required by modern PDF.js
        const loadingTask = pdfjsLib.getDocument({
            data: uint8Array,
            cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4/cmaps/',
            cMapPacked: true,
        });
        const rawDoc = await loadingTask.promise;
        pdfDoc.value = markRaw(rawDoc);
        totalPages.value = rawDoc.numPages;

        isLoadingPdf.value = false;
        await nextTick();
        await renderPage(currentPage.value);
    } catch (error) {
        console.error('Error loading PDF:', error);
        pdfError.value = error.message || 'Gagal memuat dokumen PDF.';
        isLoadingPdf.value = false;
    }
};

const renderPage = async (pageNumber) => {
    if (!pdfDoc.value) return;
    try {
        if (currentRenderTask) {
            try {
                currentRenderTask.cancel();
                await currentRenderTask.promise;
            } catch (cancelErr) {
                // Ignore cancel exception
            }
        }

        const page = await pdfDoc.value.getPage(pageNumber);
        const viewport = page.getViewport({ scale: scale.value });

        const canvas = canvasRef.value;
        if (!canvas) return;

        const context = canvas.getContext('2d');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        pdfPageWidth.value = Math.floor(viewport.width);
        pdfPageHeight.value = Math.floor(viewport.height);

        const renderContext = {
            canvasContext: context,
            viewport: viewport,
        };
        currentRenderTask = page.render(renderContext);
        await currentRenderTask.promise;
    } catch (err) {
        if (err?.name !== 'RenderingCancelledException') {
            console.error('Error rendering page:', err);
            pdfError.value = `Gagal merender lembar PDF: ${err.message}`;
        }
    } finally {
        currentRenderTask = null;
    }
};

const changePage = (offset) => {
    const target = currentPage.value + offset;
    if (target >= 1 && target <= totalPages.value) {
        currentPage.value = target;
        newFieldForm.value.page_number = target;
        renderPage(target);
    }
};

const zoom = (factor) => {
    scale.value = Math.min(2.5, Math.max(0.6, scale.value * factor));
    renderPage(currentPage.value);
};

// Field Placement on Canvas Click
const handleCanvasClick = (event) => {
    if (props.document.status === 'signed') return;

    const canvas = canvasRef.value;
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const clickY = event.clientY - rect.top;

    const posXPercent = Math.max(0, Math.min(90, (clickX / rect.width) * 100));
    const posYPercent = Math.max(0, Math.min(90, (clickY / rect.height) * 100));

    newFieldForm.value.pos_x = Math.round(posXPercent);
    newFieldForm.value.pos_y = Math.round(posYPercent);
    newFieldForm.value.page_number = currentPage.value;

    showAddFieldModal.value = true;
};

// DRAG AND DROP & POSITIONING STATE
const activeDragField = ref(null);
const isDraggingField = ref(false);
const dragStart = ref({ mouseX: 0, mouseY: 0, fieldX: 0, fieldY: 0 });

const startFieldDrag = (field, event) => {
    if (props.document.status === 'signed') return;

    selectedField.value = field;
    activeDragField.value = field;
    isDraggingField.value = false;

    dragStart.value = {
        mouseX: event.clientX,
        mouseY: event.clientY,
        fieldX: Number(field.pos_x),
        fieldY: Number(field.pos_y),
    };

    window.addEventListener('mousemove', onFieldDragMove);
    window.addEventListener('mouseup', onFieldDragEnd);
};

const onFieldDragMove = (event) => {
    if (!activeDragField.value || !canvasRef.value) return;

    const deltaX = event.clientX - dragStart.value.mouseX;
    const deltaY = event.clientY - dragStart.value.mouseY;

    if (!isDraggingField.value && Math.hypot(deltaX, deltaY) > 3) {
        isDraggingField.value = true;
    }

    if (!isDraggingField.value) return;

    const rect = canvasRef.value.getBoundingClientRect();
    const deltaXPercent = (deltaX / rect.width) * 100;
    const deltaYPercent = (deltaY / rect.height) * 100;

    const newX = Math.max(0, Math.min(100 - activeDragField.value.width, dragStart.value.fieldX + deltaXPercent));
    const newY = Math.max(0, Math.min(100 - activeDragField.value.height, dragStart.value.fieldY + deltaYPercent));

    activeDragField.value.pos_x = Math.round(newX * 10) / 10;
    activeDragField.value.pos_y = Math.round(newY * 10) / 10;
};

const onFieldDragEnd = () => {
    window.removeEventListener('mousemove', onFieldDragMove);
    window.removeEventListener('mouseup', onFieldDragEnd);

    if (!activeDragField.value) return;

    const field = activeDragField.value;
    activeDragField.value = null;

    if (isDraggingField.value) {
        isDraggingField.value = false;
        saveFieldPosition(field);
    }
};

const saveFieldPosition = (field) => {
    router.patch(
        route('documents.fields.update', [props.document.uuid, field.id]),
        {
            pos_x: Math.round(Number(field.pos_x) * 10) / 10,
            pos_y: Math.round(Number(field.pos_y) * 10) / 10,
            width: Number(field.width),
            height: Number(field.height),
        },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};

// Nudge field position by delta percent
const nudgeField = (field, deltaX, deltaY) => {
    if (!field || props.document.status === 'signed') return;
    field.pos_x = Math.max(0, Math.min(100 - field.width, Math.round((Number(field.pos_x) + deltaX) * 10) / 10));
    field.pos_y = Math.max(0, Math.min(100 - field.height, Math.round((Number(field.pos_y) + deltaY) * 10) / 10));
    saveFieldPosition(field);
};

// Adjust field size by delta percent
const resizeField = (field, deltaW, deltaH) => {
    if (!field || props.document.status === 'signed') return;
    if (field.field_type === 'qr_code' || field.field_type === 'stamp') {
        const aspect = (pdfPageWidth.value && pdfPageHeight.value)
            ? (pdfPageWidth.value / pdfPageHeight.value)
            : (210 / 297);
        field.width = Math.max(6, Math.min(35, Math.round((Number(field.width) + deltaW) * 10) / 10));
        field.height = Math.round((field.width * aspect) * 10) / 10;
    } else {
        field.width = Math.max(4, Math.min(60, Math.round((Number(field.width) + deltaW) * 10) / 10));
        field.height = Math.max(3, Math.min(60, Math.round((Number(field.height) + deltaH) * 10) / 10));
    }
    saveFieldPosition(field);
};

// Compute pixel-accurate style matching PDF coordinate system
const getFieldStyle = (field) => {
    let w = Number(field.width);
    let h = Number(field.height);

    // If QR Code or Stamp, ensure strict 1:1 aspect ratio matching the canvas/PDF coordinate
    if ((field.field_type === 'qr_code' || field.field_type === 'stamp') && pdfPageWidth.value && pdfPageHeight.value) {
        h = Math.round((w * (pdfPageWidth.value / pdfPageHeight.value)) * 10) / 10;
    }

    return {
        position: 'absolute',
        left: `${field.pos_x}%`,
        top: `${field.pos_y}%`,
        width: `${w}%`,
        height: `${h}%`,
        cursor: props.document.status !== 'signed' && !isPreviewMode.value
            ? (activeDragField.value?.id === field.id ? 'grabbing' : 'grab')
            : 'default',
    };
};

const createField = () => {
    router.post(route('documents.fields.store', props.document.uuid), newFieldForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAddFieldModal.value = false;
        },
    });
};

const activeSignerSignatureData = computed(() => {
    if (!activeFieldToSign.value) return null;

    const field = activeFieldToSign.value;
    const officials = props.signingOfficials || [];

    // Find official by email or name matching
    const matched = officials.find((o) => {
        if (field.signer_email && o.email && o.email.toLowerCase() === field.signer_email.toLowerCase()) {
            return true;
        }
        if (field.signer_name && o.name && o.name.trim().toLowerCase() === field.signer_name.trim().toLowerCase()) {
            return true;
        }
        if (field.signer_name && o.display_name && o.display_name.trim().toLowerCase() === field.signer_name.trim().toLowerCase()) {
            return true;
        }
        return false;
    });

    if (matched && (matched.signature_image_base64 || matched.signature_image_url)) {
        return matched.signature_image_base64 || matched.signature_image_url;
    }

    // If only one official has signature image or fallback to the first active official with signature
    const officialWithSig = officials.find((o) => o.signature_image_base64 || o.signature_image_url);
    if (officialWithSig) {
        return officialWithSig.signature_image_base64 || officialWithSig.signature_image_url;
    }

    return null;
});

const openSignModal = (field) => {
    activeFieldToSign.value = field;
    showSignModal.value = true;
};

const handleSignatureSaved = ({ signatureData, signatureType }) => {
    if (!activeFieldToSign.value) return;

    router.post(
        route('documents.fields.sign', [props.document.uuid, activeFieldToSign.value.id]),
        {
            signature_data: signatureData,
            signature_type: signatureType,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSignModal.value = false;
                activeFieldToSign.value = null;
            },
        }
    );
};

const deleteField = (field) => {
    if (!confirm('Hapus area tanda tangan ini?')) return;
    router.delete(route('documents.fields.destroy', [props.document.uuid, field.id]), {
        preserveScroll: true,
    });
};

const handleFinalizeSigning = () => {
    isFinalizing.value = true;
    router.post(route('documents.process-sign', props.document.uuid), finalizeForm.value, {
        preserveScroll: true,
        onFinish: () => {
            isFinalizing.value = false;
            showFinalizeModal.value = false;
            loadPdf();
        },
    });
};

onMounted(() => {
    loadPdf();
});
</script>

<template>
    <Head :title="`Studio TTE - ${document.title}`" />

    <AuthenticatedLayout>
        <!-- STUDIO TOP BAR -->
        <div class="w-full px-4 sm:px-6 lg:px-8 mb-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="backUrl || route('documents.index')"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-bold text-slate-900 dark:text-white truncate max-w-xs md:max-w-md">
                                {{ document.title }}
                            </h1>
                        </div>
                        <p class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                            <span>{{ document.original_filename }}</span>
                            <span>•</span>
                            <span>{{ totalPages }} Halaman</span>
                            <span>•</span>
                            <span class="font-mono text-[11px] text-slate-400">SHA-256: {{ document.sha256_original.substring(0, 10) }}...</span>
                        </p>
                    </div>
                </div>

                <!-- TOP ACTIONS -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- If draft: Add field, Sign, Finalize -->
                    <template v-if="document.status !== 'signed'">
                        <button
                            type="button"
                            @click="showAddFieldModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                        >
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Bidang
                        </button>

                        <button
                            type="button"
                            @click="openPdfPreviewModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 transition"
                            title="Buka pratinjau berkas PDF sebelum finalisasi"
                        >
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Pratinjau PDF
                        </button>

                        <button
                            type="button"
                            @click="showFinalizeModal = true"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm shadow-blue-900/20 transition hover:scale-[1.02] active:scale-[0.98]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Finalisasi
                        </button>
                    </template>

                    <!-- If signed: Download, Verify -->
                    <template v-else>
                        <a
                            :href="route('documents.download', document.uuid)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Unduh PDF Hasil TTE
                        </a>

                        <a
                            :href="route('documents.verify', document.verification_token)"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                        >
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Verifikasi Publik
                        </a>
                    </template>

                    <!-- Audit Trail Button -->
                    <button
                        type="button"
                        @click="showAuditDrawer = !showAuditDrawer"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Jejak Audit"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- MAIN STUDIO CANVAS & TOOLBAR -->
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- PDF VIEWER AREA (Col Span 3) -->
                <div class="lg:col-span-3 flex flex-col items-center">
                    <!-- Page controls bar -->
                    <div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 mb-3 shadow-xs flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                            <button
                                @click="changePage(-1)"
                                :disabled="currentPage <= 1"
                                class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <span>Halaman {{ currentPage }} dari {{ totalPages }}</span>
                            <button
                                @click="changePage(1)"
                                :disabled="currentPage >= totalPages"
                                class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <!-- View mode & Clean Preview toggle -->
                        <div class="flex items-center gap-2">
                            <button
                                v-if="document.status !== 'signed'"
                                type="button"
                                @click="isPreviewMode = !isPreviewMode"
                                :class="[
                                    'px-3 py-1 text-xs font-semibold rounded-lg transition flex items-center gap-1.5 border shadow-2xs',
                                    isPreviewMode
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'
                                ]"
                                title="Sembunyikan bingkai kotak untuk melihat tampilan bersih dokumen"
                            >
                                <svg v-if="!isPreviewMode" class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>{{ isPreviewMode ? 'Kembali ke Mode Edit' : 'Pratinjau Bersih' }}</span>
                            </button>
                        </div>

                        <!-- Zoom controls -->
                        <div class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                            <button
                                @click="zoom(0.85)"
                                class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Perkecil"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            <span class="w-12 text-center font-mono">{{ Math.round(scale * 100) }}%</span>
                            <button
                                @click="zoom(1.15)"
                                class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Perbesar"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- PDF Page Container -->
                    <div
                        ref="containerRef"
                        class="relative bg-slate-200 dark:bg-slate-950 p-4 rounded-2xl border border-slate-300 dark:border-slate-800 shadow-inner overflow-auto max-w-full flex justify-center min-h-[480px] items-center"
                    >
                        <!-- Loading State -->
                        <div v-if="isLoadingPdf" class="flex flex-col items-center justify-center p-20 text-slate-400">
                            <svg class="animate-spin w-8 h-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="text-xs font-medium">Memuat Dokumen PDF...</span>
                        </div>

                        <!-- Error State -->
                        <div
                            v-else-if="pdfError"
                            class="flex flex-col items-center justify-center p-8 text-center max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 dark:border-rose-900 shadow-sm"
                        >
                            <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-600 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Gagal Menampilkan Berkas PDF</h4>
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 leading-relaxed">{{ pdfError }}</p>
                            <button
                                @click="loadPdf"
                                class="mt-4 px-4 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition shadow-xs"
                            >
                                Coba Muat Ulang PDF
                            </button>
                        </div>

                        <!-- PDF Canvas & Field Overlays -->
                        <div v-show="!isLoadingPdf && !pdfError" class="relative inline-block shadow-2xl rounded-sm overflow-hidden bg-white">
                            <!-- Canvas for PDF Page -->
                            <canvas
                                ref="canvasRef"
                                @click="handleCanvasClick"
                                :class="[
                                    'block transition-opacity',
                                    document.status !== 'signed' ? 'cursor-crosshair' : 'cursor-default'
                                ]"
                            ></canvas>

                            <!-- Signature Fields Overlays -->
                            <div
                                v-for="field in currentFields"
                                :key="field.id"
                                :style="getFieldStyle(field)"
                                @mousedown.stop="!isPreviewMode && startFieldDrag(field, $event)"
                                @click.stop="!isPreviewMode && (selectedField = field)"
                                :class="[
                                    'transition-all select-none',
                                    isPreviewMode
                                        ? 'pointer-events-none'
                                        : [
                                            'rounded-lg flex flex-col justify-between backdrop-blur-2xs',
                                            selectedField?.id === field.id
                                                ? 'ring-4 ring-indigo-500/50 border-2 border-indigo-600 bg-white/40 dark:bg-slate-900/40 shadow-xl z-20'
                                                : (field.status === 'signed' || field.field_type === 'qr_code' || field.field_type === 'stamp')
                                                ? 'border-2 border-indigo-400 border-dashed bg-white/30 dark:bg-slate-900/30 shadow-md ring-2 ring-indigo-500/20'
                                                : 'border-2 border-indigo-500 border-dashed bg-indigo-50/50 dark:bg-indigo-950/50 shadow-sm'
                                        ]
                                ]"
                            >
                                <!-- Floating Pill Tag for QR Code in Edit Mode -->
                                <div
                                    v-if="!isPreviewMode && field.field_type === 'qr_code'"
                                    class="absolute -top-6 left-0 flex items-center gap-1.5 bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow z-30 whitespace-nowrap"
                                >
                                    <span>TTE / QR Code</span>
                                    <button
                                        v-if="document.status !== 'signed'"
                                        @click.stop="deleteField(field)"
                                        class="text-rose-200 hover:text-white ml-0.5 font-bold"
                                        title="Hapus Bidang"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Floating Pill Tag for Stamp in Edit Mode -->
                                <div
                                    v-if="!isPreviewMode && field.field_type === 'stamp'"
                                    class="absolute -top-6 left-0 flex items-center gap-1.5 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow z-30 whitespace-nowrap"
                                >
                                    <span>Stempel</span>
                                    <button
                                        v-if="document.status !== 'signed'"
                                        @click.stop="deleteField(field)"
                                        class="text-rose-200 hover:text-white ml-0.5 font-bold"
                                        title="Hapus Bidang"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Header for Visual Signature in Edit Mode -->
                                <div
                                    v-if="!isPreviewMode && field.field_type === 'signature' && field.status !== 'signed'"
                                    class="flex items-center justify-between text-[10px] font-semibold text-slate-700 dark:text-slate-200 p-1"
                                >
                                    <span class="truncate flex items-center gap-1">
                                        <span>{{ field.signer_name }}</span>
                                    </span>
                                    <button
                                        v-if="document.status !== 'signed'"
                                        @click.stop="deleteField(field)"
                                        class="text-rose-500 hover:text-rose-700 p-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/50 transition font-bold"
                                        title="Hapus Bidang"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Floating Tag for Signed Field in Edit Mode -->
                                <div
                                    v-if="!isPreviewMode && field.field_type === 'signature' && field.status === 'signed'"
                                    class="absolute -top-6 left-0 flex items-center gap-1.5 bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow z-30 whitespace-nowrap"
                                >
                                    <span>{{ field.signer_name }}</span>
                                    <button
                                        v-if="document.status !== 'signed'"
                                        @click.stop="openSignModal(field)"
                                        class="text-indigo-200 hover:text-white ml-1 text-[9px] underline font-normal"
                                        title="Ubah TTD"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        v-if="document.status !== 'signed'"
                                        @click.stop="deleteField(field)"
                                        class="text-rose-200 hover:text-white ml-0.5 font-bold"
                                        title="Hapus Bidang"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Field Body / Content -->
                                <div class="flex-1 flex items-center justify-center overflow-hidden w-full h-full p-0 m-0">
                                    <!-- If QR Code field -->
                                    <div v-if="field.field_type === 'qr_code'" class="w-full h-full flex items-center justify-center">
                                        <img
                                            :src="qrDataUri || '/images/demo-qr-subang.svg'"
                                            alt="QR Code Universitas Subang"
                                            class="w-full h-full object-contain pointer-events-none drop-shadow-2xs"
                                        />
                                    </div>
                                    <!-- If Standalone Stamp field -->
                                    <div v-else-if="field.field_type === 'stamp'" class="w-full h-full flex items-center justify-center p-0.5">
                                        <img
                                            src="/images/stamps/stempel-fasilkom.png"
                                            alt="Stempel Fakultas Ilmu Komputer"
                                            class="w-full h-full object-contain pointer-events-none drop-shadow-xs"
                                        />
                                    </div>
                                    <!-- If signed with canvas or uploaded image -->
                                    <img
                                        v-else-if="field.status === 'signed' && field.signature_data && field.signature_data.startsWith('data:image')"
                                        :src="field.signature_data"
                                        alt="Tanda Tangan"
                                        class="w-full h-full object-contain pointer-events-none"
                                    />
                                    <!-- If signed with typed name -->
                                    <div
                                        v-else-if="field.status === 'signed' && field.signature_data"
                                        class="text-center italic font-serif font-bold text-slate-900 dark:text-white text-xs w-full px-1"
                                    >
                                        {{ field.signature_data }}
                                    </div>
                                    <!-- Pending signature -->
                                    <div v-else class="text-[10px] text-slate-400 italic">
                                        Klik untuk tanda tangan
                                    </div>
                                </div>

                                <!-- Footer for Visual Signature -->
                                <template v-if="!isPreviewMode && field.field_type === 'signature' && field.status !== 'signed'">
                                    <button
                                        v-if="document.status !== 'signed'"
                                        @click.stop="openSignModal(field)"
                                        class="w-full py-0.5 px-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-semibold tracking-wide text-center transition"
                                    >
                                        ✍ Bubuhkan TTE
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR: FIELD & ENGINE DETAILS (Col Span 1) -->
                <div class="space-y-4">
                    <!-- Studio Control Guide -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            Panduan Studio Tanda Tangan
                        </h3>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                            {{ document.status === 'signed'
                                ? 'Dokumen ini telah selesai ditandatangani dan disegel secara kriptografis. Anda dapat mengunduh atau memverifikasi keabsahannya.'
                                : 'Klik di mana saja pada lembar PDF untuk menaruh kotak tanda tangan, atau klik tombol "Tambah Bidang". Setelah semua bidang terisi, klik "Finalisasi".'
                            }}
                        </p>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Bidang pada Halaman Ini:</div>
                            <div v-if="currentFields.length === 0" class="text-xs text-slate-400 italic">
                                Belum ada bidang tanda tangan di halaman {{ currentPage }}.
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="field in currentFields"
                                    :key="field.id"
                                    class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-between"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                            <span>{{ field.signer_name }}</span>
                                            <span v-if="field.field_type === 'qr_code'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">QR</span>
                                            <span v-else-if="field.field_type === 'stamp'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">Stempel</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ field.field_type === 'stamp' ? 'Fakultas Ilmu Komputer' : (field.signer_email || 'Verifikasi Keaslian') }}
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-semibold',
                                            (field.status === 'signed' || field.field_type === 'qr_code' || field.field_type === 'stamp')
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                        ]"
                                    >
                                        {{ (field.status === 'signed' || field.field_type === 'qr_code' || field.field_type === 'stamp') ? '✓' : '✗' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KONTROL ATUR POSISI & UKURAN BIDANG -->
                    <div
                        v-if="document.status !== 'signed' && (selectedField || currentFields[0])"
                        class="bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-900/60 rounded-2xl p-4 shadow-sm space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                Atur Posisi & Ukuran Bidang
                            </h4>
                            <span class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400 font-semibold">
                                {{ (selectedField || currentFields[0]).field_type === 'qr_code' ? 'QR Code' : 'Tanda Tangan' }}
                            </span>
                        </div>

                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            💡 <strong>Tarik (drag)</strong> langsung kotak pada dokumen untuk menggeser posisinya, atau gunakan tombol arah presisi di bawah ini:
                        </p>

                        <!-- Tombol Arah Geser Presisi -->
                        <div class="flex flex-col items-center gap-1 pt-1">
                            <button
                                type="button"
                                @click="nudgeField(selectedField || currentFields[0], 0, -1)"
                                class="px-3 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg shadow-2xs transition"
                                title="Geser ke Atas"
                            >
                                ▲ Atas
                            </button>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="nudgeField(selectedField || currentFields[0], -1.5, 0)"
                                    class="px-3 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg shadow-2xs transition"
                                    title="Geser ke Kiri"
                                >
                                    ◄ Kiri
                                </button>
                                <div class="px-2 py-0.5 font-mono text-[10px] text-slate-500 text-center w-28">
                                    X: {{ Math.round((selectedField || currentFields[0]).pos_x) }}% • Y: {{ Math.round((selectedField || currentFields[0]).pos_y) }}%
                                </div>
                                <button
                                    type="button"
                                    @click="nudgeField(selectedField || currentFields[0], 1.5, 0)"
                                    class="px-3 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg shadow-2xs transition"
                                    title="Geser ke Kanan"
                                >
                                    Kanan ►
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="nudgeField(selectedField || currentFields[0], 0, 1)"
                                class="px-3 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg shadow-2xs transition"
                                title="Geser ke Bawah"
                            >
                                ▼ Bawah
                            </button>
                        </div>

                        <!-- Ukuran Bidang -->
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Ukuran Bidang:</span>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="resizeField(selectedField || currentFields[0], -1, -1)"
                                    class="px-2.5 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 rounded-lg transition"
                                    title="Perkecil Ukuran"
                                >
                                    - Kecil
                                </button>
                                <button
                                    type="button"
                                    @click="resizeField(selectedField || currentFields[0], 1, 1)"
                                    class="px-2.5 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 rounded-lg transition"
                                    title="Perbesar Ukuran"
                                >
                                    + Besar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Security & Cryptography Summary Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Spesifikasi Keamanan
                        </h4>
                        <div class="mt-3 space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Standar:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">PDF PAdES / PKCS#7</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Fungsi Hash:</span>
                                <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">SHA-256</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Integritas:</span>
                                <span class="font-semibold text-emerald-600">Tamper-Evident</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Regulasi:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">UU ITE & PP 71/2019</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: PRATINJAU DOKUMEN PDF -->
        <Modal :show="showPreviewPdfModal" @close="showPreviewPdfModal = false" max-width="5xl">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl flex flex-col h-[88vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    Pratinjau Hasil Dokumen PDF
                                </h3>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                    Draft (Belum Disegel)
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">
                                Berkas PDF dirender langsung dengan stempel & tanda tangan digital pada posisi yang Anda tentukan.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Page Switcher (if multi-page) -->
                        <div v-if="previewTotalPages > 1" class="flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="changePreviewPage(-1)"
                                :disabled="previewCurrentPage <= 1"
                                class="px-1.5 py-0.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent"
                                title="Halaman Sebelumnya"
                            >
                                ◀
                            </button>
                            <span>Hal {{ previewCurrentPage }} / {{ previewTotalPages }}</span>
                            <button
                                type="button"
                                @click="changePreviewPage(1)"
                                :disabled="previewCurrentPage >= previewTotalPages"
                                class="px-1.5 py-0.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:hover:bg-transparent"
                                title="Halaman Berikutnya"
                            >
                                ▶
                            </button>
                        </div>

                        <!-- Zoom Controls -->
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="zoomPreview(-0.15)"
                                class="px-2 py-0.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 font-bold"
                                title="Perkecil"
                            >
                                -
                            </button>
                            <span class="w-12 text-center select-none text-[11px]">{{ Math.round(previewScale * 100) }}%</span>
                            <button
                                type="button"
                                @click="zoomPreview(0.15)"
                                class="px-2 py-0.5 rounded hover:bg-slate-200 dark:hover:bg-slate-700 font-bold"
                                title="Perbesar"
                            >
                                +
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="showPreviewPdfModal = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Tutup"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- PDF Preview Canvas Area -->
                <div class="flex-1 my-3 bg-slate-200/90 dark:bg-slate-950 rounded-xl p-4 overflow-auto flex justify-center items-start min-h-[460px] border border-slate-300/80 dark:border-slate-800 shadow-inner">
                    <!-- Loading State -->
                    <div v-if="isPreviewLoading" class="flex flex-col items-center justify-center p-16 text-slate-400 self-center">
                        <svg class="animate-spin w-9 h-9 text-indigo-600 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Merender Pratinjau Dokumen PDF...</span>
                        <span class="text-[11px] text-slate-400 mt-0.5">Memuat simulasi stempel & tanda tangan digital</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="previewError" class="p-8 text-center text-xs text-rose-600 max-w-md self-center bg-rose-50 dark:bg-rose-950/30 rounded-xl border border-rose-200 dark:border-rose-900">
                        <p class="font-bold mb-1">Gagal Menampilkan Pratinjau</p>
                        <p class="mb-3">{{ previewError }}</p>
                        <button
                            type="button"
                            @click="openPdfPreviewModal"
                            class="px-3.5 py-1.5 bg-rose-600 text-white rounded-lg text-xs font-semibold hover:bg-rose-700 transition"
                        >
                            Coba Lagi
                        </button>
                    </div>

                    <!-- Rendered Canvas -->
                    <div v-show="!isPreviewLoading && !previewError" class="relative inline-block shadow-2xl rounded-sm overflow-hidden bg-white">
                        <canvas ref="previewCanvasRef" class="block"></canvas>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between flex-wrap gap-2">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        <span>Periksa apakah letak tanda tangan atau QR code sudah pas dan tidak menimpa teks.</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="showPreviewPdfModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                        >
                            Kembali Atur Posisi di Studio
                        </button>
                        <button
                            type="button"
                            @click="proceedToFinalizeFromPreview"
                            class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition hover:scale-[1.01] active:scale-[0.99]"
                        >
                            Posisi Sudah Pas, Lanjutkan Finalisasi
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- MODAL: TAMBAH BIDANG TANDA TANGAN -->
        <Modal :show="showAddFieldModal" @close="showAddFieldModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Tambah Area Tanda Tangan</h3>
                <p class="text-xs text-slate-500 mb-4">Tentukan penandatangan untuk area yang dipilih pada halaman {{ currentPage }}</p>

                <form @submit.prevent="createField" class="space-y-3">
                    <div v-if="signingOfficials && signingOfficials.length > 0">
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Pilih Cepat Pejabat Fasilkom UNSUB:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 mb-2.5">
                            <button
                                v-for="official in signingOfficials"
                                :key="official.id"
                                type="button"
                                @click="setQuickSigner(official.name, official.email)"
                                :class="[
                                    'p-2 rounded-xl border text-left transition',
                                    newFieldForm.signer_email === official.email
                                        ? 'border-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/60 ring-1 ring-indigo-500'
                                        : 'border-slate-200 dark:border-slate-800 hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/40 bg-slate-50 dark:bg-slate-950'
                                ]"
                            >
                                <div class="font-bold text-[11px] text-slate-800 dark:text-slate-200 truncate">
                                    {{ official.signature_title || official.role_title }}
                                </div>
                                <div class="text-[10px] text-slate-500 truncate" :title="official.name">
                                    {{ official.display_name || official.name }}
                                </div>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Penandatangan</label>
                        <input
                            v-model="newFieldForm.signer_name"
                            type="text"
                            required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email Penandatangan</label>
                        <input
                            v-model="newFieldForm.signer_email"
                            type="email"
                            required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis Bidang</label>
                            <select
                                v-model="newFieldForm.field_type"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="signature">Tanda Tangan</option>
                                <option value="stamp">Stempel / Cap</option>
                                <option value="qr_code">QR Code</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Halaman</label>
                            <input
                                v-model="newFieldForm.page_number"
                                type="number"
                                min="1"
                                :max="totalPages"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button
                            type="button"
                            @click="showAddFieldModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition"
                        >
                            Pasang Bidang
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: FINALISASI & SEGEL TTE -->
        <Modal :show="showFinalizeModal" @close="showFinalizeModal = false" max-width="lg">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <div class="flex items-center gap-3 mb-2">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Finalisasi & Segel Dokumen TTE</h3>
                        <p class="text-xs text-slate-500">Pilih mesin penandatanganan elektronik untuk menyegel berkas PDF ini</p>
                    </div>
                </div>

                <form @submit.prevent="handleFinalizeSigning" class="space-y-4 mt-4">
                    <!-- DRIVER SELECTION -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Pilih Mesin / Driver Penandatangan:
                            </label>
                        </div>

                        <!-- Info Helper -->
                        <div class="mb-3 p-3 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-900/60 text-xs text-indigo-900 dark:text-indigo-300 leading-relaxed flex items-start gap-2">
                            <span class="text-sm">💡</span>
                            <div>
                                <span class="font-bold">Standar Persuratan Akademik:</span> Gunakan opsi <strong>Local PKI & Visual E-Sign</strong> untuk seluruh persuratan resmi mahasiswa. Pilihan pihak ketiga (Privy/Peruri/DocuSign) merupakan arsitektur integrasi eksternal (mode simulasi) untuk rencana kerja sama berbayar di masa depan.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <label
                                v-for="driver in availableDrivers"
                                :key="driver.id"
                                :class="[
                                    'p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between relative',
                                    finalizeForm.driver === driver.id
                                        ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/30'
                                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'
                                ]"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            v-model="finalizeForm.driver"
                                            :value="driver.id"
                                            class="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ driver.name }}</span>
                                    </div>
                                    <div class="mt-1">
                                        <span
                                            v-if="driver.id === 'local'"
                                            class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                        >
                                            Direkomendasikan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-block px-1.5 py-0.5 text-[9px] font-semibold rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                        >
                                            PSrE Eksternal (Simulasi)
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-500 mt-2">
                                    {{ driver.id === 'local' ? 'Sertifikat Digital PKI X.509 Lokal & QR Verifikasi Kampus' : 'Integrasi eksternal berinduk (mencatat token audit eksternal)' }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- CERTIFICATE SELECTION (if driver === 'local') -->
                    <div v-if="finalizeForm.driver === 'local'">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Sertifikat Digital X.509:
                            </label>
                        </div>

                        <select
                            v-if="certificates.length > 0"
                            v-model="finalizeForm.digital_certificate_id"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="null">Tanpa Sertifikat Kriptografis (Hanya Visual & QR Code)</option>
                            <option v-for="cert in certificates" :key="cert.id" :value="cert.id">
                                {{ cert.name }} ({{ cert.organization || 'Personal' }}) - SN: {{ cert.serial_number }}
                            </option>
                        </select>
                        <div v-else class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-xs">
                            Anda belum memiliki sertifikat digital X.509. Dokumen akan disegel menggunakan Visual Stempel & QR Code Verifikasi. Anda juga bisa
                            <Link :href="route('certificates.index')" class="underline font-bold">menerbitkan sertifikat gratis di sini</Link>.
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-500 leading-relaxed">
                        ⚠️ <strong>Peringatan Integritas:</strong> Setelah dokumen difinalisasi, seluruh koordinat tanda tangan dan stempel akan dirender secara permanen ke dalam file PDF. Dokumen tidak dapat diubah lagi.
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showFinalizeModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isFinalizing"
                            class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            <span v-if="isFinalizing">Sedang Memproses & Menyegel...</span>
                            <span v-else>Ya, Segel Dokumen Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: SIGNATURE PAD -->
        <SignaturePadModal
            :show="showSignModal"
            :signer-name="activeFieldToSign?.signer_name || ''"
            :default-signature-url="activeSignerSignatureData"
            @close="showSignModal = false"
            @save="handleSignatureSaved"
        />

        <!-- DRAWER: AUDIT TRAIL -->
        <div
            v-if="showAuditDrawer"
            class="fixed inset-y-0 right-0 w-80 sm:w-96 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl z-50 p-6 overflow-y-auto animate-slide-left"
        >
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Jejak Audit Integritas</h3>
                </div>
                <button @click="showAuditDrawer = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div class="mt-4 space-y-4">
                <div
                    v-for="log in (document.audit_logs || document.auditLogs || [])"
                    :key="log.id"
                    class="relative pl-6 pb-2 border-l-2 border-indigo-200 dark:border-indigo-900"
                >
                    <div class="absolute -left-[5px] top-1 w-2 h-2 rounded-full bg-indigo-600"></div>
                    <div class="text-[11px] font-semibold text-slate-800 dark:text-slate-200">{{ log.description }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2">
                        <span>{{ new Date(log.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }} WIB</span>
                        <span v-if="log.ip_address">• IP: {{ log.ip_address }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
