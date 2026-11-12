<script setup>
import { ref, shallowRef, markRaw, onMounted, nextTick, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const props = defineProps({
    letter: Object,
    pdfBase64: String,
    verificationUrl: String,
    signers: {
        type: Array,
        default: () => [
            {
                id: 'kaprodi',
                role_title: 'Ketua Program Studi',
                signature_title: 'Ka. Program Studi',
                name: 'BAGUS ALI AKBAR, S.Si., M.Kom',
                display_name: 'Bagus Ali Akbar, S.SI., M.Kom',
                email: 'bagusaliakbar@unsub.ac.id',
                description: 'Ketua Program Studi Sistem Informasi',
            },
            {
                id: 'dekan',
                role_title: 'Dekan',
                signature_title: 'Dekan',
                name: 'Dr. TEPI PEIRISAL, M.Si',
                display_name: 'Dr. Tepi Peirisal, M.Si',
                email: 'tepipeirisal@unsub.ac.id',
                description: 'Dekan Fakultas Ilmu Komputer',
            },
            {
                id: 'wadek',
                role_title: 'Wakil Dekan',
                signature_title: 'Wakil Dekan',
                name: 'BAMBANG TJAHJO UTOMO, MT',
                display_name: 'Bambang Tjahjo Utomo, MT',
                email: 'bambangtjahjoutomo@unsub.ac.id',
                description: 'Wakil Dekan Fakultas Ilmu Komputer',
            },
        ],
    },
});

const canvasRef = ref(null);
const pdfDoc = shallowRef(null);
const currentPage = ref(1);
const totalPages = ref(1);
const isLoadingPdf = ref(true);
const pdfError = ref(null);

const showSignModal = ref(false);
const showRejectModal = ref(false);
const showChangeSignerModal = ref(false);
const showManualUploadModal = ref(false);

const manualUploadType = ref('drive'); // 'drive' or 'file'
const manualUploadForm = useForm({
    signed_file: null,
    drive_link: props.letter.manual_drive_link || '',
});

const handleManualUpload = () => {
    if (manualUploadType.value === 'file' && !manualUploadForm.signed_file) {
        alert('Silakan pilih berkas dokumen yang akan diunggah.');
        return;
    }
    if (manualUploadType.value === 'drive' && !manualUploadForm.drive_link) {
        alert('Silakan masukkan link Google Drive dokumen surat.');
        return;
    }
    manualUploadForm.post(route('letters.upload-manual-signed', props.letter.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            showManualUploadModal.value = false;
        },
    });
};

const getCurrentSignerId = () => {
    const title = (props.letter.signer_title || '').toLowerCase();
    const name = (props.letter.signer_name || '').toLowerCase();
    if (title.includes('wakil') || name.includes('bambang')) return 'wadek';
    if (title.includes('dekan') || name.includes('tepi')) return 'dekan';
    return 'kaprodi';
};

const signerForm = useForm({
    signer_id: getCurrentSignerId(),
});

const downloadToggleForm = useForm({
    can_download_pdf: props.letter.can_download_pdf !== false,
    pickup_note: props.letter.pickup_note || '',
});

// Deteksi metode pengesahan secara komprehensif
const signingMethodInfo = computed(() => {
    if (props.letter.status !== 'signed') return null;

    if (props.letter.signing_mode === 'manual') {
        return {
            type: 'manual',
            label: 'Tanda Tangan & Cap Basah (Manual/Scan)',
            badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300',
            icon: '✍️',
        };
    }

    const doc = props.letter.document;
    const fields = doc?.signature_fields || doc?.signatureFields || [];

    const hasVisualSig = fields.some(f => f.field_type === 'signature' && f.status === 'signed' && !!f.signature_data);
    const hasStamp = fields.some(f => f.field_type === 'stamp');
    const hasQr = fields.some(f => f.field_type === 'qr_code');

    if (hasVisualSig && hasStamp) {
        return {
            type: 'visual_stamp',
            label: 'TTD Visual & Cap Stempel Digital',
            badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300',
            icon: '🖋️',
        };
    }

    if (hasVisualSig) {
        return {
            type: 'visual',
            label: 'TTD Visual Pejabat (Digital)',
            badgeClass: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-300',
            icon: '✍️',
        };
    }

    if (hasStamp) {
        return {
            type: 'stamp',
            label: 'Cap Stempel Resmi & QR Code',
            badgeClass: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300',
            icon: '🏵️',
        };
    }

    // Default TTE Digital QR Code
    return {
        type: 'digital',
        label: 'TTE Digital (QR Code Keabsahan)',
        badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300',
        icon: '🛡️',
    };
});

const isLecturerLetter = computed(() => {
    const code = props.letter.letter_type?.code;
    return ['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas'].includes(code);
});

const isAnnouncementLetter = computed(() => {
    const code = props.letter.letter_type?.code;
    return ['pengumuman_ujian_mahasiswa'].includes(code);
});

const isFacultyLetter = computed(() => {
    const code = props.letter.letter_type?.code;
    return ['pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'perbaikan_data_pddikti', 'pembukaan_periode', 'pengajuan_ttd_ijazah', 'pengajuan_blanko_ijazah_transkrip', 'pemberitahuan_ujian_dosen'].includes(code);
});

const isSelfSignedLetter = computed(() => {
    const code = props.letter.letter_type?.code;
    return ['kesanggupan_mengajar', 'pakta_integritas'].includes(code);
});

const isMouLetter = computed(() => {
    const code = props.letter.letter_type?.code || '';
    const name = props.letter.letter_type?.name || '';
    return code.includes('mou') || name.toLowerCase().includes('mou') || name.toLowerCase().includes('nota kesepahaman');
});

const isSkLetter = computed(() => {
    const code = props.letter.letter_type?.code || '';
    const name = props.letter.letter_type?.name || '';
    return code.startsWith('sk_') || code.includes('surat_keputusan') || name.toLowerCase().includes('surat keputusan') || name.toLowerCase().startsWith('sk ');
});

const handleToggleDownload = (enabled) => {
    downloadToggleForm.can_download_pdf = enabled;
    downloadToggleForm.patch(route('letters.toggle-download', props.letter.uuid), {
        preserveScroll: true,
    });
};

const rejectForm = useForm({
    rejection_reason: '',
});

const isSigning = ref(false);

const handleChangeSigner = () => {
    signerForm.patch(route('letters.update-signer', props.letter.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            showChangeSignerModal.value = false;
        },
    });
};

watch(
    () => props.letter.signer_name,
    () => {
        signerForm.signer_id = getCurrentSignerId();
    }
);

watch(
    () => props.pdfBase64,
    () => {
        loadPdf();
    }
);

const loadPdf = async () => {
    if (!props.pdfBase64) {
        isLoadingPdf.value = false;
        return;
    }

    isLoadingPdf.value = true;
    pdfError.value = null;

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

        const rawDoc = await loadingTask.promise;
        pdfDoc.value = markRaw(rawDoc);
        totalPages.value = rawDoc.numPages;
        currentPage.value = 1;
        isLoadingPdf.value = false;

        await nextTick();
        renderPage(1);
    } catch (err) {
        console.error('Error rendering letter PDF:', err);
        pdfError.value = err.message || 'Gagal merender dokumen surat.';
        isLoadingPdf.value = false;
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

const renderPage = async (pageNumber) => {
    if (!pdfDoc.value || !canvasRef.value) return;
    try {
        const page = await pdfDoc.value.getPage(pageNumber);
        const viewport = page.getViewport({ scale: 1.25 });

        const canvas = canvasRef.value;
        const context = canvas.getContext('2d');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        await page.render({
            canvasContext: context,
            viewport: viewport,
        }).promise;
    } catch (e) {
        console.error(e);
    }
};

const handleSign = () => {
    isSigning.value = true;
    router.post(
        route('letters.sign', props.letter.uuid),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isSigning.value = false;
                showSignModal.value = false;
            },
        }
    );
};

const handleReject = () => {
    rejectForm.post(route('letters.reject', props.letter.uuid), {
        onSuccess: () => {
            showRejectModal.value = false;
        },
    });
};

onMounted(() => {
    loadPdf();
});
</script>

<template>
    <Head :title="`Detail Surat - ${letter.student_name || letter.reference_number || 'Dokumen'}`" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- TOP HEADER -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('letters.index')"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ letter.letter_type?.name }}
                                </h1>
                                <span
                                    v-if="!isSelfSignedLetter && !isMouLetter"
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                        letter.status === 'signed'
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300'
                                            : letter.status === 'rejected'
                                            ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                            : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300'
                                    ]"
                                >
                                    {{ letter.status === 'signed' ? '✓ Disetujui & TTE Selesai' : letter.status === 'rejected' ? 'Ditolak' : `Proses Tandatangan ${letter.signer_title || 'Pejabat'}` }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 flex items-center gap-2 mt-0.5 font-mono">
                                <span v-if="['kesanggupan_mengajar', 'pakta_integritas'].includes(letter.letter_type?.code)" class="font-sans italic">
                                    Format Dokumen (Tanpa Nomor Surat)
                                </span>
                                <span v-else-if="isMouLetter">
                                    No. Mitra: {{ letter.extra_data?.partner_mou_number || '-' }} • No. Fasilkom: {{ letter.letter_number || 'Draf' }}
                                </span>
                                <span v-else>No: {{ letter.letter_number || 'Draf' }}</span>
                                <span v-if="!isMouLetter">•</span>
                                <span v-if="isMouLetter" class="font-sans font-medium text-slate-600 dark:text-slate-300">
                                    Mitra: {{ letter.extra_data?.partner_name || letter.student_name }}
                                </span>
                                <span v-else-if="isSkLetter" class="font-sans font-medium text-slate-600 dark:text-slate-300">
                                    Penetapan: {{ letter.signer_title || 'Dekan' }} ({{ letter.extra_data?.study_program || letter.study_program || 'Fakultas Ilmu Komputer' }})
                                </span>
                                <span v-else>{{ ['kesanggupan_mengajar', 'pakta_integritas'].includes(letter.letter_type?.code) ? 'Dosen: ' : 'Pemohon: ' }}{{ letter.student_name }} ({{ letter.npm }})</span>
                            </p>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <template v-if="letter.status === 'signed'">
                            <!-- Toggle Unduh Mahasiswa (Khusus Admin/Staf) -->
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs">
                                <span class="text-slate-600 dark:text-slate-300 font-medium">Izin Unduh Mahasiswa:</span>
                                <button
                                    type="button"
                                    @click="handleToggleDownload(!downloadToggleForm.can_download_pdf)"
                                    :class="[
                                        'px-2.5 py-1 rounded-lg font-bold text-[11px] transition-all flex items-center gap-1',
                                        downloadToggleForm.can_download_pdf
                                            ? 'bg-emerald-600 text-white shadow-xs'
                                            : 'bg-amber-500 text-white shadow-xs'
                                    ]"
                                    :title="downloadToggleForm.can_download_pdf ? 'Klik untuk nonaktifkan unduhan mandiri (jika dicetak oleh Fakultas)' : 'Klik untuk izinkan mahasiswa mengunduh file PDF'"
                                >
                                    <span v-if="downloadToggleForm.can_download_pdf">✓ Diizinkan</span>
                                    <span v-else>🔒 Nonaktif (Cetak Fakultas)</span>
                                </button>
                            </div>

                            <!-- JIKA MENGGUNAKAN TAUTAN GOOGLE DRIVE -->
                            <a
                                v-if="letter.manual_drive_link"
                                :href="letter.manual_drive_link"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 transition border border-blue-200 dark:border-blue-800 shadow-xs"
                                title="Buka berkas di Google Drive"
                            >
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                </svg>
                                Buka di Google Drive
                            </a>

                            <!-- JIKA MENGGUNAKAN FILE PDF LANGSUNG (TTE DIGITAL ATAU SCAN DIUNGGAH LANGSUNG) -->
                            <a
                                v-else
                                :href="route('letters.download', letter.uuid)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Unduh PDF Resmi
                            </a>

                            <a
                                v-if="verificationUrl"
                                :href="verificationUrl"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition"
                            >
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Cek Keabsahan QR
                            </a>
                        </template>

                        <template v-else-if="letter.status === 'submitted'">
                            <a
                                :href="route('letters.download', letter.uuid)"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition"
                                title="Unduh draf surat kosong untuk dicetak dan ditandatangani basah"
                            >
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak Draf Kosong
                            </a>

                            <button
                                type="button"
                                @click="showManualUploadModal = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 transition"
                                title="Unggah dokumen hasil scan tanda tangan & cap basah manual"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Unggah Scan TTD & Cap
                            </button>

                            <button
                                type="button"
                                @click="showRejectModal = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 transition"
                            >
                                Tolak
                            </button>

                            <Link
                                v-if="letter.document"
                                :href="route('documents.studio', letter.document.uuid)"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-900/20 transition hover:scale-[1.02] active:scale-[0.98]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Lanjutkan ke Studio Tanda Tangan
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- MAIN DETAIL GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- LEFT 2 COLS: PDF VISUAL PREVIEW -->
                    <div class="lg:col-span-2 flex flex-col items-center">
                        <!-- Multi-page Navigator if totalPages > 1 -->
                        <div v-if="totalPages > 1" class="w-full mb-4 flex flex-wrap items-center justify-between gap-2 px-2">
                            <div class="flex items-center p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs text-xs">
                                <button
                                    v-for="p in totalPages"
                                    :key="p"
                                    type="button"
                                    @click="goToPage(p)"
                                    :class="[
                                        'px-3 py-1 rounded-lg font-bold transition flex items-center gap-1.5',
                                        currentPage === p
                                            ? 'bg-blue-600 text-white shadow-xs'
                                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    <span>Halaman {{ p }}</span>
                                    <span v-if="p === 1" class="hidden sm:inline text-[10px] opacity-80">(Surat)</span>
                                    <span v-else-if="p === 2" class="hidden sm:inline text-[10px] opacity-80">(Lampiran)</span>
                                </button>
                            </div>

                            <div class="flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs text-xs">
                                <button
                                    type="button"
                                    @click="changePage(-1)"
                                    :disabled="currentPage <= 1"
                                    class="p-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:hover:bg-transparent transition"
                                    title="Halaman Sebelumnya"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span class="px-2 font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap text-xs">
                                    Hal {{ currentPage }} / {{ totalPages }}
                                </span>
                                <button
                                    type="button"
                                    @click="changePage(1)"
                                    :disabled="currentPage >= totalPages"
                                    class="p-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:hover:bg-transparent transition"
                                    title="Halaman Berikutnya"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="w-full bg-slate-200 dark:bg-slate-950 p-6 rounded-3xl border border-slate-300 dark:border-slate-800 shadow-inner flex justify-center min-h-[500px]">
                            <div v-if="isLoadingPdf" class="flex flex-col items-center justify-center p-20 text-slate-400">
                                <svg class="animate-spin w-8 h-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span class="text-xs">Memuat Pratinjau Dokumen Surat...</span>
                            </div>

                            <div v-else-if="pdfError" class="p-8 text-center text-xs text-rose-600">
                                {{ pdfError }}
                            </div>

                            <div v-show="!isLoadingPdf && !pdfError" class="relative inline-block shadow-2xl rounded-sm overflow-hidden bg-white">
                                <canvas ref="canvasRef" class="block"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT 1 COL: LETTER DETAILS & SIGNER INFO -->
                    <div class="space-y-4">
                        <!-- DETAIL CARD -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                {{ isMouLetter ? 'Detail Nota Kesepahaman (MoU)' : (isSkLetter ? 'Detail Surat Keputusan (SK Dekan)' : (isAnnouncementLetter ? 'Detail Pengumuman Resmi' : (isFacultyLetter ? 'Detail Dokumen Fakultas' : (isLecturerLetter ? 'Detail Dokumen Dosen' : 'Detail Surat Akademik')))) }}
                            </h3>

                            <!-- KHUSUS SURAT KEPUTUSAN (SK DEKAN) -->
                            <div v-if="isSkLetter" class="space-y-3 text-xs">
                                <div>
                                    <div class="text-slate-400 text-[10px]">Pejabat Menetapkan</div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ letter.extra_data?.dekan_name || letter.signer_name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ letter.signer_title || 'Dekan Fakultas Ilmu Komputer' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Perihal / Judul Ketetapan (Tentang)</div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ letter.extra_data?.about_title || letter.purpose || letter.letter_type?.name }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Program Studi / Unit Kerja</div>
                                    <div class="font-semibold text-indigo-600 dark:text-indigo-400 font-mono">
                                        {{ letter.extra_data?.study_program || letter.study_program || 'Fakultas Ilmu Komputer Universitas Subang' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Semester / Tahun Akademik</div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        Semester {{ letter.extra_data?.semester_type || (letter.semester ? (letter.semester % 2 === 1 ? 'Ganjil' : 'Genap') : '-') }} Tahun Akademik {{ letter.academic_year }}
                                    </div>
                                </div>
                                <div v-if="letter.extra_data?.decision_city || letter.extra_data?.decision_date_text">
                                    <div class="text-slate-400 text-[10px]">Waktu & Tempat Penetapan</div>
                                    <div class="text-slate-700 dark:text-slate-300">
                                        Ditetapkan di {{ letter.extra_data?.decision_city || 'Subang' }}, pada tanggal {{ letter.extra_data?.decision_date_text || '-' }}
                                    </div>
                                </div>
                                <div v-if="letter.rejection_reason" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
                                    <div class="font-bold text-[10px] uppercase">Alasan Penolakan:</div>
                                    <div class="mt-0.5">{{ letter.rejection_reason }}</div>
                                </div>
                            </div>

                            <!-- KHUSUS NOTA KESEPAHAMAN (MOU) -->
                            <div v-else-if="isMouLetter" class="space-y-3 text-xs">
                                <div>
                                    <div class="text-slate-400 text-[10px]">Institusi / Perusahaan Mitra (Pihak I)</div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ letter.extra_data?.partner_name || letter.student_name }}
                                    </div>
                                    <div v-if="letter.extra_data?.partner_business_field" class="text-[11px] text-slate-500">
                                        Bidang: {{ letter.extra_data.partner_business_field }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Pimpinan / Pejabat Penandatangan Mitra</div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ letter.extra_data?.partner_official_name || '-' }}
                                    </div>
                                    <div v-if="letter.extra_data?.partner_official_position" class="text-[11px] text-slate-500">
                                        {{ letter.extra_data.partner_official_position }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Pihak Kedua (Fakultas)</div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        Fakultas Ilmu Komputer Universitas Subang
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ letter.extra_data?.faculty_official_name || letter.signer_name }} ({{ letter.extra_data?.faculty_official_position || letter.signer_title }})
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Tahun Akademik & Masa Berlaku</div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        T.A. {{ letter.academic_year }} • Masa Berlaku: {{ letter.extra_data?.agreement_duration_years || 2 }} Tahun (Durasi Magang: {{ letter.extra_data?.program_duration_months || 5 }} Bulan)
                                    </div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Judul Kerjasama / Keperluan</div>
                                    <div class="font-medium text-teal-700 dark:text-teal-400">
                                        {{ letter.extra_data?.mou_title || letter.purpose || 'Penyelenggaraan Program Magang Bersertifikat - MBKM' }}
                                    </div>
                                </div>
                                <div v-if="letter.rejection_reason" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
                                    <div class="font-bold text-[10px] uppercase">Alasan Penolakan:</div>
                                    <div class="mt-0.5">{{ letter.rejection_reason }}</div>
                                </div>
                            </div>

                            <!-- KHUSUS SURAT PENGUMUMAN RESMI (MAHASISWA) -->
                            <div v-else-if="isAnnouncementLetter" class="space-y-3 text-xs">
                                <div>
                                    <div class="text-slate-400 text-[10px]">Penerbit Pengumuman</div>
                                    <div class="font-bold text-slate-900 dark:text-white">Fakultas Ilmu Komputer Universitas Subang</div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Sasaran Pengumuman</div>
                                    <div class="font-semibold text-blue-600 dark:text-blue-400">
                                        Seluruh Mahasiswa Fakultas Ilmu Komputer
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Universitas Subang
                                    </div>
                                </div>
                                <div v-if="letter.rejection_reason" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
                                    <div class="font-bold text-[10px] uppercase">Alasan Penolakan:</div>
                                    <div class="mt-0.5">{{ letter.rejection_reason }}</div>
                                </div>
                            </div>

                            <!-- KHUSUS SURAT DINAS FAKULTAS -->
                            <div v-else-if="isFacultyLetter" class="space-y-3 text-xs">
                                <div>
                                    <div class="text-slate-400 text-[10px]">Instansi / Pengirim</div>
                                    <div class="font-bold text-slate-900 dark:text-white">Fakultas Ilmu Komputer Universitas Subang</div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">Tujuan Surat (Kepada Yth)</div>
                                    <div class="font-semibold text-blue-600 dark:text-blue-400">
                                        {{ letter.extra_data?.target_recipient || 'Rektor Universitas Subang' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ letter.extra_data?.target_city || 'Subang' }}
                                    </div>
                                </div>
                                <div v-if="letter.extra_data?.period_month_year">
                                    <div class="text-slate-400 text-[10px]">Periode / Bulan Pengajuan</div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ letter.extra_data.period_month_year }}
                                    </div>
                                </div>
                                <div v-if="letter.extra_data?.lampiran_count">
                                    <div class="text-slate-400 text-[10px]">Lampiran</div>
                                    <div class="text-slate-700 dark:text-slate-300">
                                        {{ letter.extra_data.lampiran_count }}
                                    </div>
                                </div>
                                <div v-if="letter.rejection_reason" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
                                    <div class="font-bold text-[10px] uppercase">Alasan Penolakan:</div>
                                    <div class="mt-0.5">{{ letter.rejection_reason }}</div>
                                </div>
                            </div>

                            <!-- SURAT REGULER MAHASISWA / DOSEN -->
                            <div v-else class="space-y-3 text-xs">
                                <div>
                                    <div class="text-slate-400 text-[10px]">
                                        {{ isLecturerLetter ? 'Nama Lengkap Dosen / Pengajar' : 'Nama Mahasiswa' }}
                                    </div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ letter.student_name }}</div>
                                </div>
                                <div>
                                    <div class="text-slate-400 text-[10px]">
                                        {{ isLecturerLetter ? 'NIDN / NIP • Program Studi' : 'NPM / Program Studi' }}
                                    </div>
                                    <div class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ letter.npm }} • {{ letter.study_program }}
                                    </div>
                                </div>
                                <!-- Sembunyikan Semester & Tahun Akademik untuk Pakta Integritas / jenis dokumen dosen yang tidak memerlukan semester mahasiswa -->
                                <div v-if="letter.letter_type?.code !== 'pakta_integritas'">
                                    <div class="text-slate-400 text-[10px]">Semester / Tahun Akademik</div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        Semester {{ letter.semester }} ({{ letter.academic_year }})
                                    </div>
                                </div>
                                <!-- Tampilkan rincian mata kuliah yang diampu jika ada (khusus pakta integritas / kesanggupan mengajar) -->
                                <div v-if="letter.extra_data?.courses_taught">
                                    <div class="text-slate-400 text-[10px]">Mata Kuliah Yang Diampu</div>
                                    <div class="font-medium text-slate-800 dark:text-slate-200">
                                        {{ letter.extra_data.courses_taught }}
                                    </div>
                                </div>
                                <div v-if="letter.letter_type?.code !== 'pakta_integritas'">
                                    <div class="text-slate-400 text-[10px]">Keperluan</div>
                                    <div class="text-slate-700 dark:text-slate-300">{{ letter.purpose || '-' }}</div>
                                </div>
                                <div v-if="letter.rejection_reason" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
                                    <div class="font-bold text-[10px] uppercase">Alasan Penolakan:</div>
                                    <div class="mt-0.5">{{ letter.rejection_reason }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- SIGNER OFFICIAL INFO -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    {{ isSelfSignedLetter ? 'Penandatangan Pernyataan' : 'Penandatangan Elektronik' }}
                                </h4>

                                <button
                                    v-if="letter.status === 'submitted' && !isSelfSignedLetter"
                                    type="button"
                                    @click="showChangeSignerModal = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:hover:bg-indigo-900 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800 transition shadow-2xs"
                                    title="Tentukan / ubah pejabat yang berwenang menandatangani surat"
                                >
                                    <span>⚙️</span> Sesuaikan Pejabat
                                </button>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div>
                                    <div class="text-slate-400 text-[10px]">
                                        {{ isSelfSignedLetter ? 'Dosen Pembuat Pernyataan:' : 'Pejabat Penandatangan:' }}
                                    </div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ letter.signer_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium">{{ letter.signer_title }}</div>
                                </div>
                                <div v-if="letter.signed_at">
                                    <div class="text-slate-400 text-[10px]">Waktu Pengesahan:</div>
                                    <div class="font-mono text-slate-700 dark:text-slate-300">
                                        {{ new Date(letter.signed_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) }} WIB
                                    </div>
                                </div>
                                <div v-if="letter.status === 'signed' && signingMethodInfo">
                                    <div class="text-slate-400 text-[10px]">Metode Pengesahan:</div>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 mt-0.5 px-2.5 py-1 rounded-lg text-[11px] font-bold shadow-2xs',
                                            signingMethodInfo.badgeClass
                                        ]"
                                    >
                                        <span>{{ signingMethodInfo.icon }}</span>
                                        <span>{{ signingMethodInfo.label }}</span>
                                    </span>

                                    <div v-if="letter.manual_drive_link" class="mt-2 pt-2 border-t border-indigo-200/40 dark:border-indigo-900/40">
                                        <div class="text-slate-400 text-[10px]">Tautan Berkas Drive:</div>
                                        <a
                                            :href="letter.manual_drive_link"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 underline font-semibold mt-0.5 break-all"
                                        >
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                            </svg>
                                            Buka Google Drive
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI TTE -->
        <Modal :show="showSignModal" @close="showSignModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>

                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Setujui & Tanda Tangani Surat?
                </h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Surat akan secara resmi disegel secara elektronik (TTE) dan disematkan QR Code verifikasi Universitas Subang atas nama <strong>{{ letter.signer_name }}</strong>.
                </p>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showSignModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleSign"
                        :disabled="isSigning"
                        class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition disabled:opacity-50"
                    >
                        {{ isSigning ? 'Menyegel TTE...' : 'Ya, Setujui & Segel TTE' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- MODAL TOLAK PERMOHONAN -->
        <Modal :show="showRejectModal" @close="showRejectModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                    Tolak Permohonan Surat
                </h3>
                <p class="text-xs text-slate-500 mb-4">
                    Berikan alasan penolakan agar mahasiswa dapat memperbaiki permohonannya.
                </p>

                <form @submit.prevent="handleReject" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alasan Penolakan</label>
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            rows="3"
                            required
                            placeholder="Lampiran belum lengkap, format NPM salah..."
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showRejectModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            Tolak Permohonan
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL SESUAIKAN PEJABAT PENANDATANGAN -->
        <Modal :show="showChangeSignerModal" @close="showChangeSignerModal = false" max-width="lg">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Sesuaikan Pejabat Penandatangan
                        </h3>
                        <p class="text-xs text-slate-500">
                            Pilih pejabat fakultas yang berwenang menandatangani surat ini. Draf PDF akan disesuaikan otomatis.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showChangeSignerModal = false"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 transition"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-2.5">
                    <label
                        v-for="signer in signers"
                        :key="signer.id"
                        :class="[
                            'flex items-center justify-between p-3.5 rounded-xl border-2 cursor-pointer transition select-none',
                            signerForm.signer_id === signer.id
                                ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20'
                                : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                type="radio"
                                name="change_signer_id"
                                :value="signer.id"
                                v-model="signerForm.signer_id"
                                class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-slate-300"
                            />
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ signer.display_name }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ signer.role_title }} • {{ signer.description }}
                                </div>
                            </div>
                        </div>
                        <span
                            :class="[
                                'px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider',
                                signerForm.signer_id === signer.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                            ]"
                        >
                            {{ signer.signature_title }}
                        </span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showChangeSignerModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleChangeSigner"
                        :disabled="signerForm.processing"
                        class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition disabled:opacity-50"
                    >
                        {{ signerForm.processing ? 'Menyesuaikan...' : 'Simpan & Perbarui Draf Surat' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- MODAL UNGGAH HASIL SCAN TTD & CAP BASAH MANUAL / LINK DRIVE -->
        <Modal :show="showManualUploadModal" @close="showManualUploadModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-start gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Hasil TTD & Cap Basah Manual
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Simpan arsip surat yang telah dicetak, ditandatangani basah oleh pejabat, dan dicap stempel fakultas.
                        </p>
                    </div>
                </div>

                <!-- TABS: GOOGLE DRIVE LINK / UPLOAD FILE LANGSUNG -->
                <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                    <button
                        type="button"
                        @click="manualUploadType = 'drive'"
                        :class="[
                            'flex-1 py-1.5 text-xs font-bold rounded-lg transition flex items-center justify-center gap-1.5',
                            manualUploadType === 'drive'
                                ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                        </svg>
                        Tautan Google Drive
                    </button>
                    <button
                        type="button"
                        @click="manualUploadType = 'file'"
                        :class="[
                            'flex-1 py-1.5 text-xs font-bold rounded-lg transition flex items-center justify-center gap-1.5',
                            manualUploadType === 'file'
                                ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Unggah Berkas Langsung
                    </button>
                </div>

                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs text-amber-900 dark:text-amber-200 space-y-1">
                    <div class="font-bold">💡 Informasi Alur Manual:</div>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] text-amber-800 dark:text-amber-300">
                        <li>Draf surat kosong dicetak melalui tombol <strong>Cetak Draf Kosong</strong>.</li>
                        <li>Setelah ditandatangani basah & dicap stempel, arsip dapat dimasukkan berupa tautan Google Drive atau diunggah langsung (PDF / Foto).</li>
                        <li>Status surat akan berubah otomatis menjadi <strong>Selesai (Signed)</strong>.</li>
                    </ul>
                </div>

                <form @submit.prevent="handleManualUpload" class="space-y-4">
                    <!-- OPTION A: GOOGLE DRIVE LINK -->
                    <div v-if="manualUploadType === 'drive'" class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Link Google Drive Dokumen Surat
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                </svg>
                            </div>
                            <input
                                type="url"
                                v-model="manualUploadForm.drive_link"
                                placeholder="https://drive.google.com/file/d/.../view"
                                class="block w-full pl-9 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 focus:ring-blue-500 focus:border-blue-500 py-2.5 text-slate-900 dark:text-white"
                                required
                            />
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Pastikan akses link Google Drive telah disetel ke <em>"Siapa saja yang memiliki link dapat melihat"</em>.
                        </p>
                        <div v-if="manualUploadForm.errors.drive_link" class="text-xs text-rose-500 mt-1">
                            {{ manualUploadForm.errors.drive_link }}
                        </div>
                    </div>

                    <!-- OPTION B: FILE UPLOAD -->
                    <div v-else class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Pilih Berkas Scan (PDF / JPG / PNG, maks 10MB)
                        </label>
                        <input
                            type="file"
                            accept=".pdf,image/png,image/jpeg,image/jpg"
                            @change="(e) => manualUploadForm.signed_file = e.target.files[0]"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-950 dark:file:text-amber-300 cursor-pointer border border-slate-200 dark:border-slate-800 rounded-xl p-1"
                            required
                        />
                        <div v-if="manualUploadForm.errors.signed_file" class="text-xs text-rose-500 mt-1">
                            {{ manualUploadForm.errors.signed_file }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showManualUploadModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="manualUploadForm.processing || (manualUploadType === 'drive' ? !manualUploadForm.drive_link : !manualUploadForm.signed_file)"
                            class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span v-if="manualUploadForm.processing">Menyimpan...</span>
                            <span v-else>Simpan & Finalisasi Surat</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

