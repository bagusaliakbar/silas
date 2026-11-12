<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    letters: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const layoutMode = ref('card'); // 'card' | 'table'
const expandedLetters = ref({});

const showRejectReasonModal = ref(false);
const activeRejectionReason = ref('');
const showDeleteConfirmModal = ref(false);
const letterToDelete = ref(null);
const isDeleting = ref(false);

const toggleExpand = (id) => {
    expandedLetters.value[id] = !expandedLetters.value[id];
};

const applyFilter = () => {
    router.get(
        route('mahasiswa.letters.index'),
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimeout = null;
watch([search, status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilter();
    }, 300);
});

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
};

const hasActiveFilters = computed(() => {
    return !!search.value || status.value !== 'all';
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const viewRejectReason = (reason) => {
    activeRejectionReason.value = reason || 'Tidak ada catatan alasan dari pejabat penandatangan.';
    showRejectReasonModal.value = true;
};

const confirmDelete = (letter) => {
    letterToDelete.value = letter;
    showDeleteConfirmModal.value = true;
};

const deleteLetter = () => {
    if (!letterToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('mahasiswa.letters.destroy', letterToDelete.value.uuid), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteConfirmModal.value = false;
            letterToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Riwayat Pengajuan Surat" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR (UNIFIED STYLE) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Riwayat & Status Pengajuan Surat</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Total {{ stats?.total ?? 0 }} permohonan diajukan ({{ stats?.submitted ?? 0 }} diproses, {{ stats?.signed ?? 0 }} selesai, {{ stats?.rejected ?? 0 }} ditolak)
                            </p>
                        </div>

                        <Link
                            :href="route('mahasiswa.letters.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Ajukan Surat Baru</span>
                        </Link>
                    </div>

                    <!-- Search, Status Tabs & Layout Switcher Controls -->
                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                        <!-- Search Bar -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari perihal, nomor registrasi, atau keperluan surat..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="search = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Hapus pencarian"
                                aria-label="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Status Filter Tabs with Count Badges -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 text-xs">
                            <button
                                type="button"
                                @click="status = 'all'"
                                :class="[
                                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                    status === 'all'
                                        ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs'
                                        : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                <span>Semua</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                        status === 'all'
                                            ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900'
                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                    ]"
                                >
                                    {{ stats.total }}
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="status = 'submitted'"
                                :class="[
                                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                    status === 'submitted'
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                        : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                <span>Diproses</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                        status === 'submitted'
                                            ? 'bg-white/20 text-white'
                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                    ]"
                                >
                                    {{ stats.submitted }}
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="status = 'signed'"
                                :class="[
                                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                    status === 'signed'
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                        : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                <span>Selesai</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                        status === 'signed'
                                            ? 'bg-white/20 text-white'
                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                    ]"
                                >
                                    {{ stats.signed }}
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="status = 'rejected'"
                                :class="[
                                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                    status === 'rejected'
                                        ? 'bg-rose-600 text-white border-rose-600 shadow-xs'
                                        : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                <span>Ditolak</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                        status === 'rejected'
                                            ? 'bg-white/20 text-white'
                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                    ]"
                                >
                                    {{ stats.rejected }}
                                </span>
                            </button>
                        </div>

                        <!-- View Switcher (Cards vs Table) -->
                        <div class="flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs shrink-0 self-end md:self-auto">
                            <button
                                type="button"
                                @click="layoutMode = 'card'"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition text-xs',
                                    layoutMode === 'card'
                                        ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                ]"
                                title="Tampilan Kartu Timeline"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Timeline</span>
                            </button>

                            <button
                                type="button"
                                @click="layoutMode = 'table'"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition text-xs',
                                    layoutMode === 'table'
                                        ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                ]"
                                title="Tampilan Tabel Ringkas"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                <span>Tabel</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ letters?.data?.length || 0 }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ letters?.total || 0 }}</span>
                        pengajuan surat
                        <span v-if="hasActiveFilters" class="text-blue-600 dark:text-blue-400 font-medium">
                            (terfilter)
                        </span>
                    </div>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="resetFilters"
                        class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold flex items-center gap-1"
                    >
                        <span>Reset Filter</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- EMPTY STATE -->
                <div v-if="letters.data.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-xs">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak Ada Riwayat Surat Ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Belum ada permohonan surat pada filter ini. Buat permohonan surat baru untuk memulai proses administrasi akademik.
                    </p>
                    <Link
                        :href="route('mahasiswa.letters.create')"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-sm"
                    >
                        + Buat Permohonan Surat Sekarang
                    </Link>
                </div>

                <!-- ========================================================== -->
                <!-- VIEW 1: TIMELINE STEPPER CARDS (DEFAULT & MOBILE-FIRST)    -->
                <!-- ========================================================== -->
                <div v-else-if="layoutMode === 'card'" class="space-y-5">
                    <div
                        v-for="letter in letters.data"
                        :key="letter.id"
                        class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition-shadow p-5 sm:p-6 space-y-5"
                    >
                        <!-- CARD TOP BAR: TITLE, NUMBER, AND STATUS BADGE -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight">
                                        {{ letter.letter_type?.name }}
                                    </h3>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-500 flex-wrap">
                                    <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">
                                        {{ letter.letter_number ? `No: ${letter.letter_number}` : 'Nomor registrasi terbit otomatis saat TTE' }}
                                    </span>
                                    <span>•</span>
                                    <span>Diajukan: {{ formatDateTime(letter.created_at) }}</span>
                                </div>
                            </div>

                            <!-- Overall Status Badge -->
                            <div class="shrink-0">
                                <span
                                    v-if="letter.status === 'signed'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 shadow-xs"
                                >
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Surat Diterbitkan</span>
                                </span>

                                <span
                                    v-else-if="letter.status === 'rejected'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800 shadow-xs"
                                >
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span>Permohonan Ditolak</span>
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80 shadow-xs"
                                >
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Diproses</span>
                                </span>
                            </div>
                        </div>

                        <!-- CARD MIDDLE: 4-STEP VISUAL TIMELINE STEPPER -->
                        <div class="py-2 px-1">
                            <div class="relative grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-2">
                                <!-- Connecting Line (Desktop) -->
                                <div class="hidden sm:block absolute top-4 left-6 right-6 h-0.5 bg-slate-200 dark:bg-slate-700 -z-0 overflow-hidden">
                                    <div
                                        class="h-full transition-all duration-500"
                                        :class="[
                                            letter.status === 'signed'
                                                ? 'w-full bg-emerald-500'
                                                : letter.status === 'rejected'
                                                ? 'w-1/2 bg-rose-500'
                                                : 'w-1/2 bg-amber-500'
                                        ]"
                                    ></div>
                                </div>

                                <!-- Connecting Line (Mobile Vertical) -->
                                <div class="sm:hidden absolute top-4 bottom-4 left-4 w-0.5 bg-slate-200 dark:bg-slate-700 -z-0 overflow-hidden">
                                    <div
                                        class="w-full transition-all duration-500"
                                        :class="[
                                            letter.status === 'signed'
                                                ? 'h-full bg-emerald-500'
                                                : letter.status === 'rejected'
                                                ? 'h-1/2 bg-rose-500'
                                                : 'h-1/2 bg-amber-500'
                                        ]"
                                    ></div>
                                </div>

                                <!-- STEP 1: Pengajuan Terkirim -->
                                <div class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-2 relative z-10">
                                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-md shrink-0 ring-4 ring-white dark:ring-slate-900">
                                        ✓
                                    </div>
                                    <div class="sm:pt-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">
                                            1. Permohonan Diajukan
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ formatDate(letter.created_at) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- STEP 2: Verifikasi Berkas TU -->
                                <div class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-2 relative z-10">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow-md shrink-0 ring-4 ring-white dark:ring-slate-900 transition-colors"
                                        :class="[
                                            letter.status === 'signed'
                                                ? 'bg-emerald-600 text-white'
                                                : letter.status === 'rejected'
                                                ? 'bg-rose-600 text-white'
                                                : 'bg-amber-500 text-white animate-pulse'
                                        ]"
                                    >
                                        <span v-if="letter.status === 'signed'">✓</span>
                                        <span v-else-if="letter.status === 'rejected'">✕</span>
                                        <span v-else>2</span>
                                    </div>
                                    <div class="sm:pt-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">
                                            2. Verifikasi BAAK
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ letter.status === 'signed' ? 'Berkas Terverifikasi' : (letter.status === 'rejected' ? 'Ditolak' : 'Pemeriksaan Berkas') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- STEP 3: Proses TTE Pejabat -->
                                <div class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-2 relative z-10">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow-md shrink-0 ring-4 ring-white dark:ring-slate-900 transition-colors"
                                        :class="[
                                            letter.status === 'signed'
                                                ? 'bg-emerald-600 text-white'
                                                : letter.status === 'rejected'
                                                ? 'bg-rose-600 text-white'
                                                : 'bg-amber-500 text-white animate-pulse'
                                        ]"
                                    >
                                        <span v-if="letter.status === 'signed'">✓</span>
                                        <span v-else-if="letter.status === 'rejected'">✕</span>
                                        <span v-else>3</span>
                                    </div>
                                    <div class="sm:pt-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">
                                            3. Tanda Tangan (TTE)
                                        </div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-[150px]">
                                            {{ letter.status === 'signed' ? 'Telah Ditandatangani' : (letter.status === 'rejected' ? 'Tidak Disetujui' : (letter.signer_title || 'Pejabat Penandatangan')) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- STEP 4: Surat Sah Diterbitkan -->
                                <div class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-2 relative z-10">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow-md shrink-0 ring-4 ring-white dark:ring-slate-900 transition-colors"
                                        :class="[
                                            letter.status === 'signed'
                                                ? 'bg-emerald-600 text-white'
                                                : letter.status === 'rejected'
                                                ? 'bg-slate-200 dark:bg-slate-800 text-slate-400'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                        ]"
                                    >
                                        <span v-if="letter.status === 'signed'">✓</span>
                                        <span v-else>4</span>
                                    </div>
                                    <div class="sm:pt-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-900 dark:text-white">
                                            4. Selesai
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ letter.status === 'signed' ? (letter.can_download_pdf ? 'Siap Diunduh' : 'Dicetak di Fakultas') : 'Menunggu Penyelesaian' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- REJECTION ALERT CALLOUT (PROMINENT IF STATUS REJECTED) -->
                        <div
                            v-if="letter.status === 'rejected'"
                            class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                        >
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-xs sm:text-sm text-rose-900 dark:text-rose-200">
                                        Catatan Penolakan dari {{ letter.signer_title || 'Pejabat Penandatangan' }}
                                    </h4>
                                    <p class="text-xs text-rose-800 dark:text-rose-300 leading-relaxed font-medium">
                                        "{{ letter.rejection_reason || 'Persyaratan atau data formulir belum sesuai. Silakan periksa dan perbaiki kembali.' }}"
                                    </p>
                                </div>
                            </div>

                            <!-- 1-Click Action: Revisi & Ajukan Ulang -->
                            <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
                                <Link
                                    :href="route('mahasiswa.letters.edit', letter.uuid)"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-900/20 transition hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Revisi & Ajukan Ulang Sekarang</span>
                                </Link>
                            </div>
                        </div>

                        <!-- CARD FOOTER: DETAILS & ACTION BUTTONS -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                            <div class="text-slate-600 dark:text-slate-400">
                                <span class="font-medium text-slate-500">Keperluan:</span>
                                <span class="ms-1 font-semibold text-slate-800 dark:text-slate-200">{{ letter.purpose || '-' }}</span>
                            </div>

                            <!-- Action Buttons Group -->
                            <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
                                <!-- When Signed: Download & Public Verify -->
                                <template v-if="letter.status === 'signed'">
                                    <!-- Jika Unduhan Diizinkan oleh Admin/Fakultas -->
                                    <template v-if="letter.can_download_pdf">
                                        <!-- Opsi 1: Jika admin upload via Google Drive Link -->
                                        <a
                                            v-if="letter.manual_drive_link"
                                            :href="letter.manual_drive_link"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 border border-blue-200 dark:border-blue-800 transition shadow-xs"
                                            title="Buka Berkas Surat di Google Drive"
                                        >
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                            </svg>
                                            <span>Buka Drive</span>
                                        </a>

                                        <!-- Opsi 2: Jika berkas PDF asli/scan diunggah ke sistem -->
                                        <a
                                            v-else
                                            :href="route('mahasiswa.letters.download', letter.uuid)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition hover:scale-[1.02]"
                                            title="Unduh Berkas PDF Resmi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span>Unduh PDF</span>
                                        </a>
                                    </template>

                                    <!-- Default: Dicetak oleh Fakultas -->
                                    <div
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800"
                                        :title="letter.pickup_note || 'Surat dicetak oleh Fakultas. Silakan ambil di TU.'"
                                    >
                                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span>Dicetak Fakultas (Ambil di BAAK)</span>
                                    </div>

                                    <a
                                        v-if="letter.document?.verification_token"
                                        :href="route('documents.verify', letter.document.verification_token)"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition"
                                        title="Buka Halaman Verifikasi QR Code Resmi"
                                    >
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Cek Detail Surat</span>
                                    </a>
                                </template>

                                <!-- When Not Signed (Submitted or Rejected): Edit & Cancel -->
                                <template v-else>
                                    <Link
                                        :href="route('mahasiswa.letters.edit', letter.uuid)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800 transition"
                                        title="Edit permohonan surat"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit Data</span>
                                    </Link>

                                    <button
                                        type="button"
                                        @click="confirmDelete(letter)"
                                        class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 transition"
                                        title="Batalkan pengajuan surat ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Batal</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- VIEW 2: COMPACT TABLE (WITH EXPANDABLE ACCORDION ROW)     -->
                <!-- ========================================================== -->
                <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 font-semibold border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-3.5">Jenis & Nomor Surat</th>
                                    <th class="px-6 py-3.5">Keperluan Permohonan</th>
                                    <th class="px-6 py-3.5">Pejabat Pengesah</th>
                                    <th class="px-6 py-3.5">Tanggal</th>
                                    <th class="px-6 py-3.5">Status</th>
                                    <th class="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template v-for="letter in letters.data" :key="letter.id">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                {{ letter.letter_type?.name }}
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                                {{ letter.letter_number ? `No: ${letter.letter_number}` : 'Nomor diterbitkan saat TTE' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs truncate text-slate-600 dark:text-slate-400">
                                            {{ letter.purpose || '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-slate-800 dark:text-slate-200">
                                                {{ letter.signer_name }}
                                            </div>
                                            <div class="text-[10px] text-slate-500">
                                                {{ letter.signer_title }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500">
                                            {{ formatDate(letter.created_at) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                v-if="letter.status === 'signed'"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                            >
                                                ✓ Selesai
                                            </span>
                                            <button
                                                v-else-if="letter.status === 'rejected'"
                                                type="button"
                                                @click="viewRejectReason(letter.rejection_reason)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 hover:underline"
                                            >
                                                ✕ Ditolak
                                            </button>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80"
                                            >
                                                ● Diproses
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="toggleExpand(letter.id)"
                                                    class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    title="Lihat Timeline Detail"
                                                >
                                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expandedLetters[letter.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>

                                                <!-- Action if Signed: Download PDF / Buka Drive jika diizinkan, atau info Cetak TU -->
                                                <template v-if="letter.status === 'signed'">
                                                    <template v-if="letter.can_download_pdf">
                                                        <a
                                                            v-if="letter.manual_drive_link"
                                                            :href="letter.manual_drive_link"
                                                            target="_blank"
                                                            class="p-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition shadow-2xs"
                                                            title="Buka Berkas Surat di Google Drive"
                                                        >
                                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                                            </svg>
                                                        </a>
                                                        <a
                                                            v-else
                                                            :href="route('mahasiswa.letters.download', letter.uuid)"
                                                            class="p-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition"
                                                            title="Unduh PDF Resmi"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                            </svg>
                                                        </a>
                                                    </template>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800"
                                                        title="Surat dicetak oleh Fakultas. Silakan ambil fisik surat di TU."
                                                    >
                                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                        </svg>
                                                        Dicetak Fakultas
                                                    </span>
                                                </template>
                                                <Link
                                                    v-else
                                                    :href="route('mahasiswa.letters.edit', letter.uuid)"
                                                    class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 hover:bg-blue-200"
                                                    title="Edit Permohonan"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- EXPANDED TIMELINE ROW -->
                                    <tr v-if="expandedLetters[letter.id]" class="bg-slate-50/70 dark:bg-slate-950/70">
                                        <td colspan="6" class="px-6 py-4 space-y-3">
                                            <!-- Stepper -->
                                            <div class="grid grid-cols-4 gap-2 text-center py-2">
                                                <div>
                                                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white inline-flex items-center justify-center text-[10px] font-bold mb-1">✓</span>
                                                    <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200">1. Diajukan</div>
                                                    <div class="text-[10px] text-slate-400">{{ formatDate(letter.created_at) }}</div>
                                                </div>
                                                <div>
                                                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white inline-flex items-center justify-center text-[10px] font-bold mb-1">✓</span>
                                                    <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200">2. Validasi TU</div>
                                                    <div class="text-[10px] text-slate-400">Berkas Lengkap</div>
                                                </div>
                                                <div>
                                                    <span
                                                        class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px] font-bold mb-1"
                                                        :class="letter.status === 'signed' ? 'bg-emerald-600 text-white' : letter.status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white animate-pulse'"
                                                    >
                                                        {{ letter.status === 'signed' ? '✓' : letter.status === 'rejected' ? '✕' : '●' }}
                                                    </span>
                                                    <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200">3. Proses Tandatangan</div>
                                                    <div class="text-[10px] text-slate-400 truncate">{{ letter.signer_title }}</div>
                                                </div>
                                                <div>
                                                    <span
                                                        class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px] font-bold mb-1"
                                                        :class="letter.status === 'signed' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400'"
                                                    >
                                                        {{ letter.status === 'signed' ? '✓' : '○' }}
                                                    </span>
                                                    <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200">4. Surat Selesai</div>
                                                    <div class="text-[10px] text-slate-400">{{ letter.status === 'signed' ? (letter.can_download_pdf ? 'Siap Diunduh' : 'Dicetak Fakultas') : 'Menunggu' }}</div>
                                                </div>
                                            </div>

                                            <!-- Rejection if any -->
                                            <div v-if="letter.status === 'rejected'" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900/60 flex items-center justify-between gap-3">
                                                <span class="text-xs text-rose-800 dark:text-rose-300">
                                                    <strong>Alasan Penolakan:</strong> "{{ letter.rejection_reason || 'Berkas belum memenuhi syarat.' }}"
                                                </span>
                                                <Link
                                                    :href="route('mahasiswa.letters.edit', letter.uuid)"
                                                    class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-bold shrink-0 hover:bg-rose-700"
                                                >
                                                    Revisi & Ajukan Ulang →
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PAGINATION -->
                <div v-if="letters.links && letters.links.length > 3" class="p-4 flex justify-center gap-1">
                    <Link
                        v-for="(link, idx) in letters.links"
                        :key="idx"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                            link.active
                                ? 'bg-blue-700 text-white shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                            !link.url && 'opacity-40 pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- MODAL ALASAN PENOLAKAN LENGKAP -->
        <div v-if="showRejectReasonModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 animate-in zoom-in-95">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Catatan Penolakan Surat</h3>
                        <p class="text-[11px] text-slate-500">Keterangan dari pejabat penandatangan Fasilkom</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-rose-50/50 dark:bg-rose-950/30 border border-rose-200/80 dark:border-rose-900/60 text-xs text-rose-900 dark:text-rose-200 leading-relaxed font-medium">
                    "{{ activeRejectionReason }}"
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="showRejectReasonModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI BATALKAN SURAT -->
        <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 animate-in zoom-in-95">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Batalkan Pengajuan Surat?</h3>
                        <p class="text-[11px] text-slate-500">Tindakan ini akan menghapus berkas permohonan</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Apakah Anda yakin ingin membatalkan permohonan
                    <strong class="text-slate-900 dark:text-white">{{ letterToDelete?.letter_type?.name }}</strong>?
                    Data permohonan ini akan dihapus dari antrean verifikasi.
                </p>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="showDeleteConfirmModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="deleteLetter"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition disabled:opacity-50"
                    >
                        <svg v-if="isDeleting" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isDeleting ? 'Membatalkan...' : 'Ya, Batalkan Surat' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
