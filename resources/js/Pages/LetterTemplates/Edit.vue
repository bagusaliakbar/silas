<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import LetterPdfViewer from '@/Components/LetterPdfViewer.vue';
import LetterPreviewModal from '@/Components/LetterPreviewModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    letterType: {
        type: Object,
        required: true,
    },
    placeholders: {
        type: Array,
        required: true,
    },
    signers: {
        type: Array,
        default: () => [],
    },
    classifications: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.letterType.name,
    description: props.letterType.description || '',
    letter_code: props.letterType.letter_code || '',
    opening_template: props.letterType.opening_template || '',
    content_template: props.letterType.content_template || '',
    closing_template: props.letterType.closing_template || '',
    is_active: Boolean(props.letterType.is_active),
});

// Delete Modal state
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const forceDelete = ref(false);

const openDeleteModal = () => {
    forceDelete.value = false;
    showDeleteModal.value = true;
};

const closeDeleteModal = (force = false) => {
    if (isDeleting.value && !force) return;
    showDeleteModal.value = false;
    forceDelete.value = false;
};

const executeDelete = () => {
    isDeleting.value = true;
    router.delete(route('letter-templates.destroy', props.letterType.id), {
        data: {
            force: forceDelete.value ? 1 : 0,
        },
        onSuccess: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            forceDelete.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const activeField = ref('content_template');
const copiedTag = ref(null);
const viewMode = ref('split'); // 'split' | 'full'
const mobileTab = ref('editor'); // 'editor' | 'preview'
const tagCategory = ref('Semua');
const tagSearch = ref('');
const isGuideExpanded = ref(false);

const insertTag = (tag) => {
    if (activeField.value && typeof form[activeField.value] === 'string') {
        form[activeField.value] += (form[activeField.value].length > 0 ? ' ' : '') + tag;
    }
    navigator.clipboard?.writeText(tag);
    copiedTag.value = tag;
    setTimeout(() => {
        if (copiedTag.value === tag) {
            copiedTag.value = null;
        }
    }, 2000);
};

// LIVE PDF PREVIEW SYNC LOGIC
let liveSyncTimeout = null;
const isLiveSyncing = ref(false);
const previewPdfBase64 = ref(null);
const previewErrorMessage = ref(null);
const lastSyncedAt = ref(null);
const showModalPreview = ref(false);

const fetchLivePreview = async () => {
    isLiveSyncing.value = true;
    previewErrorMessage.value = null;

    try {
        const res = await window.axios.post(route('letter-templates.preview', props.letterType.id), {
            opening_template: form.opening_template,
            content_template: form.content_template,
            closing_template: form.closing_template,
            return_base64: true,
        });

        if (res.data?.success && res.data?.pdfBase64) {
            previewPdfBase64.value = res.data.pdfBase64;
            lastSyncedAt.value = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        } else {
            previewErrorMessage.value = res.data?.message || 'Format pratinjau tidak valid.';
        }
    } catch (e) {
        console.error('Error fetching preview:', e);
        previewErrorMessage.value = e.response?.data?.message || 'Terjadi kesalahan sistem saat memproses pratinjau surat.';
    } finally {
        isLiveSyncing.value = false;
    }
};

const triggerDebouncedSync = () => {
    clearTimeout(liveSyncTimeout);
    liveSyncTimeout = setTimeout(() => {
        fetchLivePreview();
    }, 600);
};

watch(
    [() => form.opening_template, () => form.content_template, () => form.closing_template],
    () => {
        triggerDebouncedSync();
    }
);

onMounted(() => {
    fetchLivePreview();
});

const openModal = () => {
    showModalPreview.value = true;
};

// Tag Categories & Search Filter
const availableCategories = computed(() => {
    const cats = ['Semua'];
    props.placeholders.forEach((p) => {
        if (!cats.includes(p.category)) {
            cats.push(p.category);
        }
    });
    return cats;
});

const filteredPlaceholders = computed(() => {
    return props.placeholders.filter((p) => {
        const matchCat = tagCategory.value === 'Semua' || p.category === tagCategory.value;
        const q = tagSearch.value.trim().toLowerCase();
        const matchSearch = !q || p.tag.toLowerCase().includes(q) || p.label.toLowerCase().includes(q);
        return matchCat && matchSearch;
    });
});

const submit = () => {
    form.put(route('letter-templates.update', props.letterType.id));
};

// Studio Engine Detection
const isSk = computed(() => Boolean(props.letterType.code?.startsWith('sk_')));
const isMou = computed(() => Boolean(props.letterType.code?.startsWith('mou_') || props.letterType.code === 'mou_magang_mbkm'));
const isSurat = computed(() => !isSk.value && !isMou.value);

const studioInfo = computed(() => {
    if (isSk.value) {
        return {
            name: 'Studio SK (Surat Keputusan Dekan)',
            engineTag: 'Studio SK',
            badge: 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-300 dark:border-purple-800',
            route: 'letters.sk-studio',
            actionLabel: 'Buka di Studio SK',
            description: 'Format ini terintegrasi dengan mesin Studio SK (Konsiderans Menimbang & Mengingat, Diktum Putusan KESATU-KEDUA, serta Lampiran Matriks Resmi). Pratinjau langsung di sebelah kanan merender format resmi SK Dekan secara live.',
        };
    }
    if (isMou.value) {
        return {
            name: 'Studio MOU (Nota Kesepahaman)',
            engineTag: 'Studio MOU',
            badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800',
            route: 'letters.mou-studio',
            actionLabel: 'Buka di Studio MOU',
            description: 'Format ini terintegrasi dengan mesin Studio MOU (Naskah Bilateral 6 Halaman, 8 Pasal Klausul Ketentuan Pokok, & Paraf Dokumen). Pratinjau langsung di sebelah kanan merender naskah perjanjian bilateral resmi secara live.',
        };
    }
    return {
        name: 'Studio Surat (Live Studio Generator)',
        engineTag: 'Studio Surat',
        badge: 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border-blue-300 dark:border-blue-800',
        route: 'letters.live-studio',
        actionLabel: 'Buka di Studio Surat',
        description: 'Format ini terintegrasi dengan Studio Surat Live. Redaksi kalimat pembuka, paragraf pernyataan inti, dan penutup tersinkronisasi otomatis dengan formulir permohonan dan dokumen cetak resmi.',
    };
});

const openingLabel = computed(() => isSk.value ? '1. Konsiderans & Menimbang (Pembuka SK)' : (isMou.value ? '1. Naskah Pembuka & Para Pihak (Pembuka MoU)' : '1. Kalimat Pembuka'));
const contentLabel = computed(() => isSk.value ? '2. Diktum Putusan & Ketetapan Pokok (Isi SK)' : (isMou.value ? '2. Klausul Pokok & Ruang Lingkup (Isi MoU)' : '2. Paragraf Pernyataan Inti (Isi Pokok)'));
const closingLabel = computed(() => isSk.value ? '3. Diktum Penutup & Pengesahan (Penutup SK)' : (isMou.value ? '3. Ketentuan Penutup & Pengesahan (Penutup MoU)' : '3. Kalimat Penutup'));
</script>

<template>
    <Head :title="`Edit Template - ${letterType.name}`" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                <!-- TOP HEADER CARD & WORKSPACE CONTROL BAR -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
                    <!-- Title & Identity -->
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('letter-templates.index')"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Kembali ke Daftar Template"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                                    Workspace Editor Template Surat
                                </h1>
                                <span
                                    v-if="letterType.letter_code"
                                    class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-900"
                                >
                                    {{ letterType.letter_code }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                >
                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Format Tanpa Nomor Surat
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ letterType.name }}
                            </p>
                        </div>
                    </div>

                    <!-- Center & Right: Mode Switcher & Actions -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <!-- Desktop Mode Switcher (Split-Screen vs Full Editor) -->
                        <div class="hidden lg:flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-xs">
                            <button
                                type="button"
                                @click="viewMode = 'split'"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition text-xs',
                                    viewMode === 'split'
                                        ? 'bg-blue-700 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                                ]"
                                title="Mode Layar Belah: Editor di kiri & Pratinjau Live PDF di kanan"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                                <span>Layar Belah (Split)</span>
                            </button>

                            <button
                                type="button"
                                @click="viewMode = 'full'"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition text-xs',
                                    viewMode === 'full'
                                        ? 'bg-blue-700 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                                ]"
                                title="Mode Editor Penuh: Area penulisan lebar dengan panel tag di samping"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                                <span>Editor Penuh</span>
                            </button>
                        </div>

                        <!-- Mobile / Tablet Tab Switcher (< 1024px) -->
                        <div class="lg:hidden flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-xs">
                            <button
                                type="button"
                                @click="mobileTab = 'editor'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-bold transition text-xs',
                                    mobileTab === 'editor'
                                        ? 'bg-blue-700 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-300'
                                ]"
                            >
                                📝 Editor
                            </button>
                            <button
                                type="button"
                                @click="mobileTab = 'preview'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-bold transition text-xs',
                                    mobileTab === 'preview'
                                        ? 'bg-blue-700 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-300'
                                ]"
                            >
                                👁️ Pratinjau PDF
                            </button>
                        </div>

                        <!-- Live Sync Indicator & Manual Refresh Button -->
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="fetchLivePreview"
                                :disabled="isLiveSyncing"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition disabled:opacity-50"
                                title="Segarkan pratinjau sekarang"
                            >
                                <svg
                                    class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"
                                    :class="{ 'animate-spin': isLiveSyncing }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span class="hidden sm:inline">{{ isLiveSyncing ? 'Menyinkronkan...' : 'Segarkan' }}</span>
                            </button>

                            <button
                                v-if="viewMode === 'full'"
                                type="button"
                                @click="openModal"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                            >
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Modal PDF</span>
                            </button>

                            <PrimaryButton @click="submit" :disabled="form.processing" class="text-xs">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Template' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- STUDIO ENGINE INTEGRATION BANNER -->
                <div
                    class="rounded-2xl border p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition shadow-xs"
                    :class="[
                        isSk
                            ? 'bg-purple-50/80 dark:bg-purple-950/40 border-purple-200 dark:border-purple-800/80'
                            : isMou
                                ? 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/80'
                                : 'bg-blue-50/80 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800/80'
                    ]"
                >
                    <div class="flex items-start gap-3.5 flex-1">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                            :class="[
                                isSk
                                    ? 'bg-purple-600 text-white'
                                    : isMou
                                        ? 'bg-amber-600 text-white'
                                        : 'bg-blue-600 text-white'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-bold text-sm text-slate-900 dark:text-white">
                                    Terhubung dengan {{ studioInfo.name }}
                                </h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="studioInfo.badge">
                                    {{ studioInfo.engineTag }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-w-3xl">
                                {{ studioInfo.description }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                        <Link
                            :href="route(studioInfo.route)"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-xs hover:scale-[1.02] active:scale-[0.98]"
                            :class="[
                                isSk
                                    ? 'bg-purple-600 hover:bg-purple-500 text-white shadow-purple-950/20'
                                    : isMou
                                        ? 'bg-amber-600 hover:bg-amber-500 text-white shadow-amber-950/20'
                                        : 'bg-blue-600 hover:bg-blue-500 text-white shadow-blue-950/20'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span>{{ studioInfo.actionLabel }}</span>
                        </Link>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- WORKSPACE CONTENT: SPLIT-SCREEN MODE (DESKTOP) OR FULL/MOBILE TAB MODE   -->
                <!-- ========================================================================= -->

                <!-- 1. SPLIT-SCREEN WORKSPACE LAYOUT (Default Desktop: 50% Editor / 50% Live PDF) -->
                <div v-if="viewMode === 'split'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start w-full">
                    <!-- LEFT COLUMN (50% / col-span-6): FORM EDITOR & INTERACTIVE TAG TOOLBAR -->
                    <div
                        class="lg:col-span-6 space-y-5 w-full"
                        :class="{ 'hidden lg:block': mobileTab === 'preview' }"
                    >
                        <!-- SMART INTERACTIVE TAG VARIABLES TOOLBAR (INLINE IN SPLIT MODE) -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-xs font-bold text-slate-900 dark:text-white leading-tight">
                                            Sisipkan Tag Variabel Dinamis
                                        </h3>
                                        <p class="text-[10px] text-slate-400">
                                            Target aktif: <strong class="text-blue-700 dark:text-blue-400 font-mono">{{ activeField === 'opening_template' ? openingLabel : activeField === 'closing_template' ? closingLabel : contentLabel }}</strong>
                                        </p>
                                    </div>
                                </div>

                                <!-- Category Filter Pills -->
                                <div class="flex items-center gap-1 overflow-x-auto max-w-[240px] text-[10px]">
                                    <button
                                        v-for="cat in availableCategories"
                                        :key="cat"
                                        type="button"
                                        @click="tagCategory = cat"
                                        :class="[
                                            'px-2 py-0.5 rounded-md font-semibold whitespace-nowrap transition',
                                            tagCategory === cat
                                                ? 'bg-blue-700 text-white shadow-2xs'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                                        ]"
                                    >
                                        {{ cat }}
                                    </button>
                                </div>
                            </div>

                            <!-- Tag Pills Grid -->
                            <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1">
                                <button
                                    v-for="p in filteredPlaceholders"
                                    :key="p.tag"
                                    type="button"
                                    @click="insertTag(p.tag)"
                                    class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800/80 hover:bg-blue-50 dark:hover:bg-blue-950/40 border border-slate-200/80 dark:border-slate-700/80 hover:border-blue-400 dark:hover:border-blue-700 text-blue-700 dark:text-blue-300 transition shadow-2xs"
                                    :title="`${p.label} (Contoh: ${p.example}) - Klik untuk menyisipkan`"
                                >
                                    <span>{{ p.tag }}</span>
                                    <span v-if="copiedTag === p.tag" class="text-[9px] font-sans font-bold text-emerald-600">✓</span>
                                    <span v-else class="text-[10px] font-sans text-slate-400 group-hover:text-blue-600">+</span>
                                </button>
                            </div>
                        </div>

                        <!-- Special Structure Guides (Collapsible in Split Mode) -->
                        <div v-if="letterType.code === 'keterangan_kuliah_pns' || letterType.code === 'observasi_matkul'" class="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/70 dark:bg-amber-950/30 overflow-hidden">
                            <button
                                type="button"
                                @click="isGuideExpanded = !isGuideExpanded"
                                class="w-full p-3.5 flex items-center justify-between text-left text-xs font-bold text-amber-900 dark:text-amber-200 hover:bg-amber-100/50 dark:hover:bg-amber-900/40 transition"
                            >
                                <span class="flex items-center gap-2">
                                    <span>💡</span>
                                    <span>Petunjuk Format Khusus Kedinasan ({{ letterType.code === 'keterangan_kuliah_pns' ? 'Surat PNS / BKN' : 'Surat Observasi Kelompok' }})</span>
                                </span>
                                <span class="text-xs text-amber-700 dark:text-amber-400">
                                    {{ isGuideExpanded ? '▲ Sembunyikan' : '▼ Buka Panduan Butir' }}
                                </span>
                            </button>

                            <div v-show="isGuideExpanded" class="p-4 pt-1 border-t border-amber-200/60 dark:border-amber-900/40 text-xs text-slate-700 dark:text-slate-300 space-y-2">
                                <p v-if="letterType.code === 'keterangan_kuliah_pns'" class="leading-relaxed">
                                    Surat ini menerapkan susunan butir bernomor <strong>1 s/d 12</strong> (1-3 Pejabat, 4-8 Mahasiswa, 9-12 Orang Tua PNS) secara otomatis. Anda cukup mengatur kalimat pembuka <code class="px-1 py-0.5 rounded bg-amber-100 dark:bg-amber-900/60 font-mono font-bold">Yang bertanda tangan dibawah ini :</code>, narasi pernyataan status semester, dan kalimat penutup.
                                </p>
                                <p v-else class="leading-relaxed">
                                    Surat observasi mata kuliah otomatis mencetak tabel bergaris anggota kelompok mahasiswa (NO, NAMA, NPM, ALAMAT). Gunakan tag <code class="px-1 py-0.5 rounded bg-amber-100 dark:bg-amber-900/60 font-mono font-bold">{mata_kuliah}</code> di narasi inti agar tercetak otomatis.
                                </p>
                            </div>
                        </div>

                        <!-- Card 1: Identitas & Klasifikasi -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                    Identitas & Klasifikasi Surat
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <InputLabel for="name_split" value="Nama Jenis Surat" class="font-bold text-xs" />
                                    <TextInput
                                        id="name_split"
                                        type="text"
                                        v-model="form.name"
                                        class="mt-1 block w-full text-xs"
                                        required
                                    />
                                    <InputError :message="form.errors.name" class="mt-1" />
                                </div>

                                <div>
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="code_split" value="Kode Klasifikasi Surat" class="font-bold text-xs" />
                                        <span v-if="!form.letter_code" class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                                            Opsional (Format Tanpa Nomor)
                                        </span>
                                    </div>
                                    <div class="mt-1 flex gap-2">
                                        <select
                                            v-if="classifications && classifications.length"
                                            v-model="form.letter_code"
                                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono text-xs uppercase focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="">-- Tanpa Kode (Format Pernyataan/Komitmen) --</option>
                                            <option v-for="c in classifications" :key="c.id" :value="c.code">
                                                {{ c.code }} - {{ c.name }}
                                            </option>
                                        </select>
                                        <TextInput
                                            v-else
                                            id="code_split"
                                            type="text"
                                            v-model="form.letter_code"
                                            class="block w-full font-mono text-xs uppercase"
                                            placeholder="Kosongkan jika tanpa nomor surat..."
                                        />
                                    </div>
                                    <p v-if="!form.letter_code" class="text-[10px] text-slate-400 mt-1">
                                        Format seperti Fakta Integritas dan Surat Pernyataan Kesanggupan Mengajar tidak memerlukan nomor surat resmi.
                                    </p>
                                    <InputError :message="form.errors.letter_code" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Konfigurasi 3 Bagian Narasi Surat -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                                        Redaksi Narasi Surat
                                    </h3>
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    Perubahan otomatis terlihat di layar kanan
                                </span>
                            </div>

                            <!-- 1. Opening Narrative -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label for="split_opening" class="block font-bold text-xs text-slate-800 dark:text-slate-100">
                                        {{ openingLabel }}
                                    </label>
                                    <span class="text-[10px] text-slate-400">
                                        {{ isSk ? 'Konsiderans penimbang / dasar terbit' : (isMou ? 'Sebelum butir ketentuan pasal' : 'Sebelum rincian data pemohon') }}
                                    </span>
                                </div>
                                <textarea
                                    id="split_opening"
                                    v-model="form.opening_template"
                                    @focus="activeField = 'opening_template'"
                                    rows="2"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:border-blue-600 focus:ring-blue-600 leading-relaxed font-sans"
                                    :placeholder="isSk ? 'Tuliskan konsiderans pembuka SK...' : (isMou ? 'Tuliskan naskah pembuka kesepahaman bersama...' : 'Kalimat pembuka surat...')"
                                ></textarea>
                                <InputError :message="form.errors.opening_template" />
                            </div>

                            <!-- 2. Content / Body Statement Narrative -->
                            <div class="space-y-1 pt-1">
                                <div class="flex items-center justify-between">
                                    <label for="split_content" class="block font-bold text-xs text-slate-800 dark:text-slate-100">
                                        {{ contentLabel }}
                                    </label>
                                    <span class="text-[10px] text-slate-400">
                                        {{ isSk ? 'Diktum ketetapan atau rincian keputusan' : (isMou ? 'Ruang lingkup kerja sama atau ketentuan pokok' : 'Inti permohonan atau pernyataan resmi') }}
                                    </span>
                                </div>
                                <textarea
                                    id="split_content"
                                    v-model="form.content_template"
                                    @focus="activeField = 'content_template'"
                                    rows="5"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:border-blue-600 focus:ring-blue-600 leading-relaxed font-sans"
                                    :placeholder="isSk ? 'Tuliskan diktum ketetapan isi SK...' : (isMou ? 'Tuliskan butir-butir pasal klausul kesepahaman...' : 'Tuliskan narasi inti permohonan di sini...')"
                                ></textarea>
                                <InputError :message="form.errors.content_template" />
                            </div>

                            <!-- 3. Closing Narrative -->
                            <div class="space-y-1 pt-1">
                                <div class="flex items-center justify-between">
                                    <label for="split_closing" class="block font-bold text-xs text-slate-800 dark:text-slate-100">
                                        {{ closingLabel }}
                                    </label>
                                    <span class="text-[10px] text-slate-400">
                                        {{ isSk ? 'Diktum penutup dan ketentuan berlaku' : (isMou ? 'Ketentuan penutup dan masa berlaku naskah' : 'Sebelum tanda tangan pejabat') }}
                                    </span>
                                </div>
                                <textarea
                                    id="split_closing"
                                    v-model="form.closing_template"
                                    @focus="activeField = 'closing_template'"
                                    rows="2"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:border-blue-600 focus:ring-blue-600 leading-relaxed font-sans"
                                    :placeholder="isSk ? 'Tuliskan diktum penutup SK...' : (isMou ? 'Tuliskan ketentuan penutup MoU...' : 'Kalimat penutup surat...')"
                                ></textarea>
                                <InputError :message="form.errors.closing_template" />
                            </div>
                        </div>

                        <!-- Card 3: Status & Action Bar -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500" />
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Aktifkan Template</span>
                                </label>
                                <button
                                    type="button"
                                    @click="openDeleteModal"
                                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 hover:underline flex items-center gap-1 transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus Format</span>
                                </button>
                            </div>

                            <PrimaryButton @click="submit" :disabled="form.processing" class="text-xs">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN (50% / col-span-6): LIVE PDF VIEWER (STICKY DESKTOP) -->
                    <div
                        class="lg:col-span-6 w-full lg:sticky lg:top-20 flex flex-col h-[calc(100vh-6.5rem)]"
                        :class="{ 'hidden lg:flex': mobileTab === 'editor' }"
                    >
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-lg flex-1 flex flex-col overflow-hidden">
                            <!-- Live Status Bar -->
                            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/90 dark:bg-slate-800/60 shrink-0">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="isLiveSyncing ? 'bg-amber-400 animate-ping' : 'bg-emerald-500'"></span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                                        Pratinjau Langsung Hasil Cetak PDF
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ isLiveSyncing ? 'Sinkronisasi...' : lastSyncedAt ? `Tersinkron ${lastSyncedAt}` : 'Siap' }}
                                </span>
                            </div>

                            <!-- Embedded Canvas Viewer -->
                            <div class="flex-1 min-h-0 p-2 sm:p-3 overflow-hidden flex flex-col bg-slate-100 dark:bg-slate-950">
                                <LetterPdfViewer
                                    :pdfBase64="previewPdfBase64"
                                    :isLoading="isLiveSyncing && !previewPdfBase64"
                                    :errorMessage="previewErrorMessage"
                                    :initialScale="0.95"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. FULL EDITOR MODE (Classic Wide Form + Dedicated Side Panel) -->
                <div v-else class="flex flex-col lg:flex-row gap-6 items-start w-full">
                    <!-- MAIN FORM -->
                    <div class="flex-1 min-w-0 w-full space-y-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Identitas & Klasifikasi -->
                            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 space-y-4">
                                <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Identitas & Klasifikasi Surat
                                    </h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="name_full" value="Nama Jenis Surat" class="font-bold text-xs" />
                                        <TextInput id="name_full" type="text" v-model="form.name" class="mt-1 block w-full text-sm" required />
                                        <InputError :message="form.errors.name" class="mt-1" />
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <InputLabel for="code_full" value="Kode Klasifikasi" class="font-bold text-xs" />
                                            <span v-if="!form.letter_code" class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                                                Opsional (Format Tanpa Nomor)
                                            </span>
                                        </div>
                                        <TextInput id="code_full" type="text" v-model="form.letter_code" class="mt-1 block w-full font-mono text-sm uppercase" placeholder="Kosongkan jika tanpa nomor surat..." />
                                        <InputError :message="form.errors.letter_code" class="mt-1" />
                                    </div>
                                </div>
                            </div>

                            <!-- Narasi Surat -->
                            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 space-y-5">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                            Konfigurasi Narasi Surat
                                        </h3>
                                    </div>
                                    <span class="text-xs text-slate-400">Klik textarea lalu klik tag di panel kanan</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label for="full_opening" class="block font-bold text-xs text-slate-800 dark:text-slate-100">{{ openingLabel }}</label>
                                    <textarea id="full_opening" v-model="form.opening_template" @focus="activeField = 'opening_template'" rows="3" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs sm:text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                </div>

                                <div class="space-y-1.5 pt-2">
                                    <label for="full_content" class="block font-bold text-xs text-slate-800 dark:text-slate-100">{{ contentLabel }}</label>
                                    <textarea id="full_content" v-model="form.content_template" @focus="activeField = 'content_template'" rows="6" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs sm:text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                </div>

                                <div class="space-y-1.5 pt-2">
                                    <label for="full_closing" class="block font-bold text-xs text-slate-800 dark:text-slate-100">{{ closingLabel }}</label>
                                    <textarea id="full_closing" v-model="form.closing_template" @focus="activeField = 'closing_template'" rows="3" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs sm:text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                </div>
                            </div>

                            <!-- Status & Save Button -->
                            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500" />
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Status Aktif</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="openDeleteModal"
                                        class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 hover:underline flex items-center gap-1 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Hapus Format</span>
                                    </button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button" @click="openModal" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        Pratinjau Modal
                                    </button>
                                    <PrimaryButton :disabled="form.processing">Simpan Perubahan</PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- DEDICATED TAG VARIABLES PANEL (FULL MODE) -->
                    <div class="w-full lg:w-[380px] shrink-0 space-y-4 lg:sticky lg:top-20">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5">
                            <div class="pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Variabel Tag Dinamis
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Klik tag di bawah untuk menyisipkan ke: <strong class="text-blue-700 dark:text-blue-400 font-mono">{{ activeField === 'opening_template' ? openingLabel : activeField === 'closing_template' ? closingLabel : contentLabel }}</strong>
                                </p>
                            </div>

                            <!-- Tag List -->
                            <div class="space-y-2 max-h-[560px] overflow-y-auto pr-1">
                                <button
                                    v-for="p in placeholders"
                                    :key="p.tag"
                                    type="button"
                                    @click="insertTag(p.tag)"
                                    class="w-full text-left p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50 dark:hover:bg-blue-950/40 border border-slate-200/80 dark:border-slate-700/60 hover:border-blue-400 dark:hover:border-blue-700 transition flex items-center justify-between text-xs"
                                >
                                    <div class="min-w-0 pr-2">
                                        <div class="flex items-center gap-1.5">
                                            <code class="font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400">{{ p.tag }}</code>
                                            <span v-if="copiedTag === p.tag" class="text-[9px] font-bold text-emerald-600">✓ Disisipkan!</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 block truncate mt-0.5">{{ p.label }} (<em>{{ p.example }}</em>)</span>
                                    </div>
                                    <span class="text-slate-400">+</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div
                @click="closeDeleteModal"
                class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-5 animate-in zoom-in-95 duration-200"
                >
                    <!-- Header Icon & Title -->
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-200 dark:border-rose-800">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div class="space-y-1 min-w-0 flex-1">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    Hapus Format Surat Resmi?
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Anda akan menghapus format surat <strong class="text-slate-800 dark:text-slate-200">{{ letterType.name }}</strong> (<code class="font-mono text-blue-600 dark:text-blue-400">{{ letterType.letter_code }}</code>).
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeDeleteModal()"
                            :disabled="isDeleting"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Tutup dialog"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Usage Notice if requests exist -->
                    <div
                        v-if="letterType.letter_requests_count > 0"
                        class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 space-y-2 text-xs"
                    >
                        <div class="flex items-center gap-1.5 font-bold text-amber-800 dark:text-amber-300">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Perhatian: Terdapat {{ letterType.letter_requests_count }} riwayat surat</span>
                        </div>
                        <p class="text-amber-700 dark:text-amber-400 leading-relaxed">
                            Format surat ini sudah digunakan oleh mahasiswa. Secara bawaan, sistem akan <strong>menonaktifkan</strong> format ini agar arsip dan dokumen legalitas mahasiswa terdahulu tetap terlindungi.
                        </p>
                        <label class="flex items-center gap-2 pt-1 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="forceDelete"
                                class="rounded border-amber-300 text-rose-600 focus:ring-rose-500"
                            />
                            <span class="text-[11px] font-semibold text-rose-700 dark:text-rose-400">
                                Hapus secara permanen (termasuk riwayat pengajuan)
                            </span>
                        </label>
                    </div>

                    <div
                        v-else
                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-400 leading-relaxed"
                    >
                        Format surat ini belum memiliki riwayat pengajuan. Menghapusnya akan menghapus seluruh rancangan narasi dan isian secara permanen dari basis data.
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            :disabled="isDeleting"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeDelete"
                            :disabled="isDeleting"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 transition shadow-md shadow-rose-950/20 active:scale-95 disabled:opacity-60"
                        >
                            <svg v-if="isDeleting" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isDeleting ? 'Menghapus...' : (letterType.letter_requests_count > 0 && !forceDelete ? 'Nonaktifkan Format' : 'Ya, Hapus Format') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optional Fullscreen Modal Preview -->
        <LetterPreviewModal
            :show="showModalPreview"
            :title="`Pratinjau Hasil Cetak: ${form.name}`"
            :pdfBase64="previewPdfBase64"
            :isLoading="isLiveSyncing"
            :errorMessage="previewErrorMessage"
            @close="showModalPreview = false"
        />
    </AuthenticatedLayout>
</template>
