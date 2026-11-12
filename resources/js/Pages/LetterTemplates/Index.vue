<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LetterPreviewModal from '@/Components/LetterPreviewModal.vue';

const props = defineProps({
    letterTypes: {
        type: Array,
        required: true,
    },
    placeholders: {
        type: Array,
        default: () => [],
    },
});

// Interactive state
const searchQuery = ref('');
const selectedStudio = ref('all'); // 'all' | 'surat' | 'sk' | 'mou'
const selectedCategory = ref('all');
const selectedStatus = ref('all');
const viewMode = ref('list'); // 'list' | 'grid'
const showVariableGuide = ref(false);
const copiedTag = ref(null);
const expandedDetails = ref({}); // { [id]: boolean }

// Delete Modal state
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);
const forceDelete = ref(false);

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    forceDelete.value = false;
    showDeleteModal.value = true;
};

const closeDeleteModal = (force = false) => {
    if (isDeleting.value && !force) return;
    showDeleteModal.value = false;
    itemToDelete.value = null;
    forceDelete.value = false;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('letter-templates.destroy', itemToDelete.value.id), {
        data: {
            force: forceDelete.value ? 1 : 0,
        },
        preserveScroll: true,
        onSuccess: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
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

// Inline PDF Preview Modal state
const showPreviewModal = ref(false);
const previewPdfBase64 = ref(null);
const isGeneratingPreview = ref(false);
const previewTitle = ref('');
const previewErrorMessage = ref(null);

const openPreview = async (letterType) => {
    showPreviewModal.value = true;
    isGeneratingPreview.value = true;
    previewPdfBase64.value = null;
    previewTitle.value = letterType.name;
    previewErrorMessage.value = null;

    try {
        const res = await window.axios.get(route('letter-templates.preview', letterType.id), {
            params: { return_base64: 1 },
        });
        if (res.data?.success && res.data?.pdfBase64) {
            previewPdfBase64.value = res.data.pdfBase64;
        } else {
            previewErrorMessage.value = res.data?.message || 'Format pratinjau tidak valid.';
        }
    } catch (e) {
        console.error('Error fetching preview:', e);
        previewErrorMessage.value = e.response?.data?.message || 'Terjadi kesalahan sistem saat memproses pratinjau surat.';
    } finally {
        isGeneratingPreview.value = false;
    }
};

const copyTag = (tag) => {
    if (navigator?.clipboard?.writeText) {
        navigator.clipboard.writeText(tag);
        copiedTag.value = tag;
        setTimeout(() => {
            if (copiedTag.value === tag) {
                copiedTag.value = null;
            }
        }, 1800);
    }
};

const toggleDetails = (id) => {
    expandedDetails.value[id] = !expandedDetails.value[id];
};

// Categorization helper matching Studio Surat, Studio SK, and Studio MOU
const getCategoryInfo = (code) => {
    // 1. STUDIO SK: Surat Keputusan Dekan
    if (code && code.startsWith('sk_')) {
        return {
            engine: 'sk',
            engineLabel: 'Studio SK',
            engineBadge: 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-300 dark:border-purple-800',
            id: 'sk_dekan',
            label: 'Surat Keputusan (SK)',
            color: 'purple',
            badgeClass: 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            studioRoute: 'letters.sk-studio',
            studioActionLabel: 'Buka di Studio SK',
        };
    }

    // 2. STUDIO MOU: Nota Kesepahaman Bilateral
    if (code && (code.startsWith('mou_') || code === 'mou_magang_mbkm')) {
        return {
            engine: 'mou',
            engineLabel: 'Studio MOU',
            engineBadge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800',
            id: 'mou',
            label: 'Nota Kesepahaman (MoU)',
            color: 'amber',
            badgeClass: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            studioRoute: 'letters.mou-studio',
            studioActionLabel: 'Buka di Studio MOU',
        };
    }

    // 3. STUDIO SURAT: Dinas & Operasional Fakultas
    const dinasCodes = [
        'pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras',
        'pengumuman_ujian_mahasiswa', 'perbaikan_data_pddikti', 'pembukaan_periode',
        'pengajuan_ttd_ijazah', 'pengajuan_blanko_ijazah_transkrip', 'rekomendasi_pinjaman_bjb',
    ];
    if (dinasCodes.includes(code)) {
        return {
            engine: 'surat',
            engineLabel: 'Studio Surat',
            engineBadge: 'bg-sky-100 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300 border-sky-300 dark:border-sky-800',
            id: 'surat_dinas',
            label: 'Dinas & Operasional',
            color: 'sky',
            badgeClass: 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
            studioRoute: 'letters.live-studio',
            studioActionLabel: 'Buka di Studio Surat',
        };
    }

    // 4. STUDIO SURAT: Tugas Dosen & Akademik
    const dosenCodes = [
        'tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta',
        'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas',
        'pemberitahuan_ujian_dosen',
    ];
    if (dosenCodes.includes(code)) {
        return {
            engine: 'surat',
            engineLabel: 'Studio Surat',
            engineBadge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800',
            id: 'surat_dosen',
            label: 'Tugas Dosen & Pengajaran',
            color: 'emerald',
            badgeClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            studioRoute: 'letters.live-studio',
            studioActionLabel: 'Buka di Studio Surat',
        };
    }

    // 5. STUDIO SURAT: Surat Akademik Mahasiswa (Default)
    return {
        engine: 'surat',
        engineLabel: 'Studio Surat',
        engineBadge: 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border-blue-300 dark:border-blue-800',
        id: 'surat_akademik',
        label: 'Akademik Mahasiswa',
        color: 'blue',
        badgeClass: 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        studioRoute: 'letters.live-studio',
        studioActionLabel: 'Buka di Studio Surat',
    };
};

// Studio Engine Filter Tabs
const studioTabs = computed(() => [
    {
        id: 'all',
        label: 'Semua Studio',
        count: props.letterTypes.length,
    },
    {
        id: 'surat',
        label: 'Studio Surat',
        count: props.letterTypes.filter((i) => getCategoryInfo(i.code).engine === 'surat').length,
    },
    {
        id: 'sk',
        label: 'Studio SK',
        count: props.letterTypes.filter((i) => getCategoryInfo(i.code).engine === 'sk').length,
    },
    {
        id: 'mou',
        label: 'Studio MOU',
        count: props.letterTypes.filter((i) => getCategoryInfo(i.code).engine === 'mou').length,
    },
]);

// Dynamic Categories with counts, scoped by selectedStudio if applicable
const categories = computed(() => {
    let list = props.letterTypes;
    if (selectedStudio.value !== 'all') {
        list = list.filter((i) => getCategoryInfo(i.code).engine === selectedStudio.value);
    }

    const allCat = [
        {
            id: 'all',
            label: 'Semua Kategori',
            count: list.length,
        },
        {
            id: 'surat_akademik',
            label: 'Akademik Mahasiswa',
            count: list.filter((i) => getCategoryInfo(i.code).id === 'surat_akademik').length,
        },
        {
            id: 'surat_dosen',
            label: 'Tugas Dosen & Pengajaran',
            count: list.filter((i) => getCategoryInfo(i.code).id === 'surat_dosen').length,
        },
        {
            id: 'surat_dinas',
            label: 'Dinas & Operasional',
            count: list.filter((i) => getCategoryInfo(i.code).id === 'surat_dinas').length,
        },
        {
            id: 'sk_dekan',
            label: 'Surat Keputusan (SK)',
            count: list.filter((i) => getCategoryInfo(i.code).id === 'sk_dekan').length,
        },
        {
            id: 'mou',
            label: 'Nota Kesepahaman (MoU)',
            count: list.filter((i) => getCategoryInfo(i.code).id === 'mou').length,
        },
    ];

    return allCat.filter((c) => c.id === 'all' || c.count > 0);
});

// Quick recommended tags for banner
const quickTags = [
    '{nama_mahasiswa}',
    '{npm}',
    '{program_studi}',
    '{tujuan_instansi}',
    '{periode_magang}',
    '{durasi_magang}',
    '{semester}',
    '{keperluan}',
];

// Highlight variable tags in narration text
const parseNarrationTokens = (text) => {
    if (!text) return [];
    const regex = /({[a-zA-Z0-9_]+})/g;
    const parts = text.split(regex);
    return parts.filter(Boolean).map((part) => ({
        text: part,
        isTag: part.startsWith('{') && part.endsWith('}'),
    }));
};

const extractUniqueTags = (text) => {
    if (!text) return [];
    const matches = text.match(/{[a-zA-Z0-9_]+}/g) || [];
    return [...new Set(matches)];
};

// Filtered templates
const filteredLetterTypes = computed(() => {
    return props.letterTypes.filter((item) => {
        const cat = getCategoryInfo(item.code);

        // Studio filter
        if (selectedStudio.value !== 'all' && cat.engine !== selectedStudio.value) {
            return false;
        }

        // Category filter
        if (selectedCategory.value !== 'all' && cat.id !== selectedCategory.value) {
            return false;
        }

        // Status filter
        if (selectedStatus.value === 'active' && !item.is_active) {
            return false;
        }
        if (selectedStatus.value === 'inactive' && item.is_active) {
            return false;
        }

        // Search query
        if (searchQuery.value.trim() !== '') {
            const q = searchQuery.value.toLowerCase().trim();
            const matchName = item.name?.toLowerCase().includes(q);
            const matchCode = item.letter_code?.toLowerCase().includes(q);
            const matchSlug = item.code?.toLowerCase().includes(q);
            const matchDesc = item.description?.toLowerCase().includes(q);
            const matchContent = item.content_template?.toLowerCase().includes(q);
            const matchStudio = cat.engineLabel?.toLowerCase().includes(q) || cat.label?.toLowerCase().includes(q);
            return matchName || matchCode || matchSlug || matchDesc || matchContent || matchStudio;
        }

        return true;
    });
});

const resetFilters = () => {
    searchQuery.value = '';
    selectedStudio.value = 'all';
    selectedCategory.value = 'all';
    selectedStatus.value = 'all';
};
</script>

<template>
    <Head title="Daftar Template Surat" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Jenis & Template Surat, SK dan MOU</h3>
                        </div>

                        <Link
                            :href="route('letter-templates.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Format Surat Baru</span>
                        </Link>
                    </div>

                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                        <!-- Search Bar -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama surat, kode (misal: PD.1.2), atau isi narasi..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="searchQuery = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Right Control Group: Status & View Toggle -->
                        <div class="flex items-center gap-2.5 shrink-0 self-end md:self-auto">
                            <!-- Status Filter -->
                            <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs">
                                <button
                                    type="button"
                                    @click="selectedStatus = 'all'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        selectedStatus === 'all'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    type="button"
                                    @click="selectedStatus = 'active'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        selectedStatus === 'active'
                                            ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                    ]"
                                >
                                    Aktif
                                </button>
                                <button
                                    type="button"
                                    @click="selectedStatus = 'inactive'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        selectedStatus === 'inactive'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                    ]"
                                >
                                    Non-aktif
                                </button>
                            </div>

                            <!-- View Mode Toggle (List vs Grid) -->
                            <div class="hidden sm:inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs">
                                <button
                                    type="button"
                                    @click="viewMode = 'list'"
                                    :class="[
                                        'p-1.5 rounded-lg transition',
                                        viewMode === 'list'
                                            ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-2xs'
                                            : 'text-slate-400 hover:text-slate-700'
                                    ]"
                                    title="Tampilan Rinci (Baris)"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="viewMode = 'grid'"
                                    :class="[
                                        'p-1.5 rounded-lg transition',
                                        viewMode === 'grid'
                                            ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-2xs'
                                            : 'text-slate-400 hover:text-slate-700'
                                    ]"
                                    title="Tampilan Grid (Kartu)"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Studio Engine Primary Filter Tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-1 scrollbar-none border-t border-slate-100 dark:border-slate-800">
                        <button
                            v-for="st in studioTabs"
                            :key="st.id"
                            type="button"
                            @click="selectedStudio = st.id; selectedCategory = 'all';"
                            :class="[
                                'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border',
                                selectedStudio === st.id
                                    ? 'bg-blue-600 text-white border-blue-600 shadow-xs'
                                    : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'
                            ]"
                        >
                            <span>{{ st.label }}</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                    selectedStudio === st.id
                                        ? 'bg-white/20 text-white'
                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                {{ st.count }}
                            </span>
                        </button>
                    </div>

                    <!-- Category Filter Sub-Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 pt-0.5 scrollbar-none">
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            @click="selectedCategory = cat.id"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition border',
                                selectedCategory === cat.id
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs'
                                    : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            <span>{{ cat.label }}</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                    selectedCategory === cat.id
                                        ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900'
                                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                {{ cat.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ filteredLetterTypes.length }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ letterTypes.length }}</span>
                        template format surat
                    </div>
                    <div v-if="searchQuery || selectedCategory !== 'all' || selectedStatus !== 'all'">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- EMPTY STATE -->
                <div
                    v-if="filteredLetterTypes.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center"
                >
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">
                        Format Surat Tidak Ditemukan
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-4">
                        Tidak ada template surat yang cocok dengan kriteria pencarian atau filter yang dipilih.
                    </p>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-xs"
                    >
                        Reset Filter & Pencarian
                    </button>
                </div>

                <!-- LIST VIEW (ROW CARDS) -->
                <div v-else-if="viewMode === 'list'" class="space-y-4">
                    <div
                        v-for="item in filteredLetterTypes"
                        :key="item.id"
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-xs hover:shadow-md hover:border-blue-300/80 dark:hover:border-blue-700/80 transition-all duration-200 group flex flex-col gap-4"
                    >
                        <!-- CARD HEADER -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <!-- Studio Engine Badge -->
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-lg text-[11px] font-bold border',
                                        getCategoryInfo(item.code).engineBadge
                                    ]"
                                >
                                    {{ getCategoryInfo(item.code).engineLabel }}
                                </span>

                                <!-- Category Pill -->
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-lg text-[11px] font-bold border',
                                        getCategoryInfo(item.code).badgeClass
                                    ]"
                                >
                                    {{ getCategoryInfo(item.code).label }}
                                </span>

                                <!-- Letter Code Chip -->
                                <span
                                    v-if="item.letter_code"
                                    class="px-2.5 py-1 rounded-lg font-mono text-[11px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700"
                                >
                                    {{ item.letter_code }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60"
                                    title="Dokumen pernyataan/komitmen ini resmi diterbitkan tanpa penomoran surat"
                                >
                                    <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tanpa Nomor Surat
                                </span>

                                <!-- Status Badge -->
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold',
                                        item.is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            item.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'
                                        ]"
                                    ></span>
                                    {{ item.is_active ? 'Aktif' : 'Non-aktif' }}
                                </span>
                            </div>

                            <!-- Document Usage Counter -->
                            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 shrink-0">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span><strong class="font-bold text-slate-900 dark:text-white">{{ item.letter_requests_count || 0 }}</strong> permohonan dibuat</span>
                            </div>
                        </div>

                        <!-- TITLE & DESCRIPTION -->
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base sm:text-lg group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                {{ item.name }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                                {{ item.description || 'Format surat resmi Fakultas Ilmu Komputer Universitas Subang.' }}
                            </p>
                        </div>

                        <!-- NARASI PERNYATAAN INTI WITH VARIABLE HIGHLIGHTING -->
                        <div class="rounded-xl bg-slate-50/90 dark:bg-slate-950/70 border border-slate-200/70 dark:border-slate-800 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="text-[11px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    <span>Narasi Pernyataan Inti Surat :</span>
                                </div>

                                <button
                                    v-if="item.opening_template || item.closing_template"
                                    type="button"
                                    @click="toggleDetails(item.id)"
                                    class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                                >
                                    <span>{{ expandedDetails[item.id] ? 'Sembunyikan Pembuka & Penutup' : 'Lihat Pembuka & Penutup' }}</span>
                                    <svg
                                        :class="['w-3 h-3 transition-transform duration-200', expandedDetails[item.id] ? 'rotate-180' : '']"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Formatted Narration Body with Pill Variables -->
                            <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-sans">
                                <template v-if="item.content_template">
                                    <template v-for="(token, idx) in parseNarrationTokens(item.content_template)" :key="idx">
                                        <span
                                            v-if="token.isTag"
                                            class="inline-block px-1.5 py-0.5 mx-0.5 rounded-md font-mono text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/70 dark:text-blue-200 border border-blue-200 dark:border-blue-700/60 shadow-2xs cursor-pointer hover:bg-blue-200"
                                            @click="copyTag(token.text)"
                                            :title="`Klik untuk salin ${token.text}`"
                                        >
                                            {{ token.text }}
                                        </span>
                                        <span v-else>{{ token.text }}</span>
                                    </template>
                                </template>
                                <span v-else class="text-slate-400 italic">
                                    Belum dikonfigurasi secara khusus (menggunakan narasi standar sistem).
                                </span>
                            </div>

                            <!-- Expanded Section: Pembuka & Penutup -->
                            <div
                                v-if="expandedDetails[item.id]"
                                class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 space-y-3 animate-in fade-in duration-200"
                            >
                                <div v-if="item.opening_template" class="space-y-1">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Kalimat Pembuka :
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 italic">
                                        {{ item.opening_template }}
                                    </p>
                                </div>
                                <div v-if="item.closing_template" class="space-y-1">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Kalimat Penutup :
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 italic">
                                        {{ item.closing_template }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- CARD FOOTER & ACTIONS -->
                        <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800">
                            <!-- Tags Summary Indicator -->
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                <span>
                                    {{ extractUniqueTags(item.content_template).length }} tag dinamis terdeteksi dalam format ini
                                </span>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-center">
                                <Link
                                    :href="route(getCategoryInfo(item.code).studioRoute)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-900/60 transition shadow-2xs hover:scale-[1.02] active:scale-[0.98]"
                                    :title="`Buka langsung di ${getCategoryInfo(item.code).engineLabel}`"
                                >
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span>{{ getCategoryInfo(item.code).studioActionLabel }}</span>
                                </Link>

                                <button
                                    type="button"
                                    @click="openPreview(item)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700/60 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 transition shadow-2xs active:scale-95"
                                    title="Lihat simulasi cetak PDF surat"
                                >
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Pratinjau PDF</span>
                                </button>

                                <Link
                                    :href="route('letter-templates.edit', item.id)"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-md shadow-blue-950/30 hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit Narasi</span>
                                </Link>

                                <button
                                    type="button"
                                    @click="openDeleteModal(item)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-900/60 transition shadow-2xs hover:scale-[1.02] active:scale-[0.98]"
                                    title="Hapus format surat ini"
                                >
                                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRID VIEW (CARD TILES) -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="item in filteredLetterTypes"
                        :key="item.id"
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 shadow-xs hover:shadow-md hover:border-blue-300/80 dark:hover:border-blue-700/80 transition-all duration-200 group flex flex-col justify-between gap-4"
                    >
                        <div class="space-y-3">
                            <!-- Category & Status -->
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-lg text-[10px] font-bold border',
                                            getCategoryInfo(item.code).engineBadge
                                        ]"
                                    >
                                        {{ getCategoryInfo(item.code).engineLabel }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-lg text-[10px] font-bold border',
                                            getCategoryInfo(item.code).badgeClass
                                        ]"
                                    >
                                        {{ getCategoryInfo(item.code).label }}
                                    </span>
                                </div>
                                <span
                                    v-if="item.letter_code"
                                    class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700"
                                >
                                    {{ item.letter_code }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9.5px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60"
                                    title="Dokumen pernyataan/komitmen ini resmi diterbitkan tanpa penomoran surat"
                                >
                                    <svg class="w-2.5 h-2.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tanpa Nomor
                                </span>
                            </div>

                            <!-- Name -->
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base group-hover:text-blue-600 dark:group-hover:text-blue-400 transition line-clamp-2">
                                    {{ item.name }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                    {{ item.description || 'Format surat resmi Fasilkom Universitas Subang.' }}
                                </p>
                            </div>

                            <!-- Narasi Preview Snippet -->
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/80 text-xs text-slate-700 dark:text-slate-300">
                                <div class="text-[10px] uppercase font-bold text-slate-400 mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    Narasi Inti :
                                </div>
                                <p class="line-clamp-3 leading-relaxed">
                                    {{ item.content_template || 'Belum dikonfigurasi secara khusus.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-slate-400">
                                {{ item.letter_requests_count || 0 }} permohonan
                            </span>

                            <div class="flex items-center gap-1.5">
                                <Link
                                    :href="route(getCategoryInfo(item.code).studioRoute)"
                                    class="p-2 rounded-xl text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 transition"
                                    :title="`Buka di ${getCategoryInfo(item.code).engineLabel}`"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </Link>
                                <button
                                    type="button"
                                    @click="openPreview(item)"
                                    class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition"
                                    title="Pratinjau PDF"
                                >
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <Link
                                    :href="route('letter-templates.edit', item.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-xs transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit</span>
                                </Link>
                                <button
                                    type="button"
                                    @click="openDeleteModal(item)"
                                    class="w-8 h-8 rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 transition inline-flex items-center justify-center shrink-0"
                                    title="Hapus format surat"
                                    aria-label="Hapus format surat"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
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
                                    Anda akan menghapus format surat <strong class="text-slate-800 dark:text-slate-200">{{ itemToDelete?.name }}</strong> (<code class="font-mono text-blue-600 dark:text-blue-400">{{ itemToDelete?.letter_code }}</code>).
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
                        v-if="itemToDelete && (itemToDelete.letter_requests_count > 0)"
                        class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 space-y-2 text-xs"
                    >
                        <div class="flex items-center gap-1.5 font-bold text-amber-800 dark:text-amber-300">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Perhatian: Terdapat {{ itemToDelete.letter_requests_count }} riwayat surat</span>
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
                            <span>{{ isDeleting ? 'Menghapus...' : (itemToDelete?.letter_requests_count > 0 && !forceDelete ? 'Nonaktifkan Format' : 'Ya, Hapus Format') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline PDF Preview Modal -->
        <LetterPreviewModal
            :show="showPreviewModal"
            :title="`Pratinjau: ${previewTitle}`"
            :pdfBase64="previewPdfBase64"
            :isLoading="isGeneratingPreview"
            :errorMessage="previewErrorMessage"
            @close="showPreviewModal = false"
        />
    </AuthenticatedLayout>
</template>
