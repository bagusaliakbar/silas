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

let searchTimeout = null;
watch([search, status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('letters.index'),
            {
                search: search.value,
                status: status.value,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 300);
});

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
};

const hasActiveFilters = computed(() => {
    return !!search.value || status.value !== 'all';
});

const statusTabs = computed(() => [
    { id: 'all', label: 'Semua', count: props.stats?.total ?? 0 },
    { id: 'submitted', label: 'Diproses', count: props.stats?.submitted ?? 0 },
    { id: 'signed', label: 'Selesai', count: props.stats?.signed ?? 0 },
    { id: 'rejected', label: 'Ditolak', count: props.stats?.rejected ?? 0 },
]);

const getRecipientTitle = (letter) => {
    const code = letter.letter_type?.code || '';
    const name = letter.letter_type?.name || '';
    if (code.includes('mou') || name.toLowerCase().includes('mou') || name.toLowerCase().includes('nota kesepahaman')) {
        return {
            name: letter.extra_data?.partner_name || letter.student_name,
            sub: letter.extra_data?.partner_official_name ? `${letter.extra_data.partner_official_name} (${letter.extra_data.partner_official_position || 'Mitra'})` : 'Institusi Mitra',
            type: 'mou'
        };
    }
    if (code.startsWith('sk_') || code.includes('surat_keputusan') || name.toLowerCase().includes('surat keputusan') || name.toLowerCase().startsWith('sk ')) {
        return {
            name: letter.extra_data?.study_program || letter.study_program || 'Fakultas Ilmu Komputer',
            sub: `Penetapan: ${letter.signer_title || 'Dekan'} (${letter.extra_data?.dekan_name || letter.student_name})`,
            type: 'sk'
        };
    }
    if (['pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'perbaikan_data_pddikti', 'pembukaan_periode', 'pengajuan_ttd_ijazah', 'pengajuan_blanko_ijazah_transkrip', 'pemberitahuan_ujian_dosen'].includes(code)) {
        return {
            name: 'Fakultas Ilmu Komputer',
            sub: letter.extra_data?.target_recipient ? `Kepada: ${letter.extra_data.target_recipient}` : 'Surat Dinas Fakultas',
            type: 'fakultas'
        };
    }
    if (['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas'].includes(code)) {
        return {
            name: letter.student_name,
            sub: letter.npm ? `NIDN/NIP: ${letter.npm}` : 'Dosen / Tenaga Pengajar',
            type: 'dosen'
        };
    }
    return {
        name: letter.student_name,
        sub: letter.npm ? `NPM: ${letter.npm}` : 'Mahasiswa',
        type: 'mahasiswa'
    };
};

const getSemesterLabel = (letter) => {
    const code = letter.letter_type?.code || '';
    const name = letter.letter_type?.name || '';

    // Khusus MoU: Tampilkan Masa Berlaku Kerjasama & Tahun Akademik
    if (code.includes('mou') || name.toLowerCase().includes('mou') || name.toLowerCase().includes('nota kesepahaman')) {
        const years = letter.extra_data?.agreement_duration_years || 2;
        return `Berlaku ${years} Thn (${letter.academic_year})`;
    }

    // Khusus SK: Tampilkan Semester Ganjil / Genap & Tahun Akademik
    if (code.startsWith('sk_') || code.includes('surat_keputusan') || name.toLowerCase().includes('surat keputusan') || name.toLowerCase().startsWith('sk ')) {
        const semType = letter.extra_data?.semester_type || (letter.semester ? (letter.semester % 2 === 1 ? 'Ganjil' : 'Genap') : 'Ganjil');
        return `${semType} (${letter.academic_year})`;
    }

    // Khusus Dokumen Dinas Fakultas
    if (['pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'perbaikan_data_pddikti', 'pembukaan_periode', 'pengajuan_ttd_ijazah', 'pengajuan_blanko_ijazah_transkrip', 'pemberitahuan_ujian_dosen'].includes(code)) {
        return letter.extra_data?.period_month_year ? letter.extra_data.period_month_year : `T.A. ${letter.academic_year}`;
    }

    // Surat Mahasiswa Reguler
    return `Semester ${letter.semester} (${letter.academic_year})`;
};

const getStatusBadge = (s) => {
    switch (s) {
        case 'signed':
            return {
                text: 'Selesai',
                class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800',
            };
        case 'submitted':
            return {
                text: 'Proses',
                class: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800',
            };
        case 'rejected':
            return {
                text: 'Ditolak',
                class: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800',
            };
        default:
            return {
                text: 'Draf',
                class: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
            };
    }
};

const toggleDownload = (letter) => {
    router.patch(
        route('letters.toggle-download', letter.uuid),
        {
            can_download_pdf: !letter.can_download_pdf,
        },
        {
            preserveScroll: true,
        }
    );
};

const archivingId = ref(null);
const archiveOutgoing = (letter) => {
    if (!confirm(`Arsipkan surat "${letter.letter_number}" ke Pusat Arsip?`)) return;
    archivingId.value = letter.id;
    router.post(
        route('archives.outgoing', letter.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                archivingId.value = null;
            },
        }
    );
};

const showDeleteConfirmModal = ref(false);
const letterToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (letter) => {
    letterToDelete.value = letter;
    showDeleteConfirmModal.value = true;
};

const executeDelete = () => {
    if (!letterToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('letters.destroy', letterToDelete.value.uuid), {
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
    <Head title="Daftar Surat Keluar" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR (UNIFIED STYLE) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Surat Keluar</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Total {{ stats?.total ?? 0 }} permohonan surat ({{ stats?.submitted ?? 0 }} diproses, {{ stats?.signed ?? 0 }} selesai, {{ stats?.rejected ?? 0 }} ditolak)
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <Link
                                :href="route('letters.live-studio')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                            >
                                <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Buat Surat</span>
                            </Link>

                            <Link
                                :href="route('letters.sk-studio')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                            >
                                <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Buat SK</span>
                            </Link>

                            <Link
                                :href="route('letters.mou-studio')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                            >
                                <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>Buat MOU</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Search & Filter Controls -->
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
                                placeholder="Cari nama pemohon, NPM, atau no. surat..."
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
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                            <button
                                v-for="tab in statusTabs"
                                :key="tab.id"
                                type="button"
                                @click="status = tab.id"
                                :class="[
                                    'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                    status === tab.id
                                        ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs'
                                        : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                <span>{{ tab.label }}</span>
                                <span
                                    :class="[
                                        'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                        status === tab.id
                                            ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900'
                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                    ]"
                                >
                                    {{ tab.count }}
                                </span>
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
                        permohonan surat
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

                <!-- LETTER LIST TABLE -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div v-if="letters.data.length === 0" class="p-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum ada permohonan surat</h3>
                        <p class="text-xs text-slate-500 mt-1">Silakan ajukan surat permohonan baru untuk keperluan akademik Anda.</p>
                        <Link
                            :href="route('letters.create')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 mt-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition"
                        >
                            + Ajukan Surat Pertama
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3.5 px-4">Nomor & Tanggal</th>
                                    <th class="py-3.5 px-4">Jenis Surat</th>
                                    <th class="py-3.5 px-4">Pemohon</th>
                                    <th class="py-3.5 px-4">Pihak / Subjek / Mahasiswa</th>
                                    <th class="py-3.5 px-4">Periode / T.A.</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4 text-center">Unduh Mahasiswa</th>
                                    <th class="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                <tr
                                    v-for="letter in letters.data"
                                    :key="letter.id"
                                    class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition"
                                >
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold font-mono text-slate-900 dark:text-white">
                                            <span v-if="['kesanggupan_mengajar', 'pakta_integritas'].includes(letter.letter_type?.code)" class="text-slate-400 font-sans font-normal italic text-xs">
                                                (Format Dokumen / Tanpa No.)
                                            </span>
                                            <span v-else>
                                                {{ letter.letter_number || 'Draf / Menunggu No.' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            {{ new Date(letter.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-slate-900 dark:text-white">
                                            {{ letter.letter_type?.name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 truncate max-w-xs">
                                            {{ letter.purpose || 'Keperluan Akademik' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span
                                            v-if="letter.user?.role === 'mahasiswa'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-cyan-50 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800"
                                            title="Diajukan secara mandiri oleh Mahasiswa"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Mahasiswa</span>
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800"
                                            title="Diterbitkan langsung oleh Staf / Admin TU"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span>Admin / TU</span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">
                                            {{ getRecipientTitle(letter).name }}
                                        </div>
                                        <div
                                            :class="[
                                                'text-[10px] font-medium',
                                                getRecipientTitle(letter).type === 'mou'
                                                    ? 'text-teal-600 dark:text-teal-400 font-semibold'
                                                    : getRecipientTitle(letter).type === 'sk'
                                                    ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
                                                    : 'text-slate-500 dark:text-slate-400'
                                            ]"
                                        >
                                            {{ getRecipientTitle(letter).sub }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">
                                            {{ getSemesterLabel(letter) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold',
                                                getStatusBadge(letter.status).class
                                            ]"
                                        >
                                            {{ getStatusBadge(letter.status).text }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button
                                            v-if="letter.status === 'signed'"
                                            type="button"
                                            @click="toggleDownload(letter)"
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition-all',
                                                letter.can_download_pdf
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100'
                                                    : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100'
                                            ]"
                                            :title="letter.can_download_pdf ? 'Klik untuk matikan unduh mandiri (Cetak di Fakultas)' : 'Klik untuk izinkan mahasiswa unduh PDF'"
                                        >
                                            <span :class="['w-1.5 h-1.5 rounded-full', letter.can_download_pdf ? 'bg-emerald-500' : 'bg-amber-500']"></span>
                                            <span>{{ letter.can_download_pdf ? 'Aktif' : 'Cetak Fakultas' }}</span>
                                        </button>
                                        <span v-else class="text-slate-400 text-[10px] italic">-</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <template v-if="letter.status === 'signed'">
                                                <a
                                                    v-if="letter.manual_drive_link"
                                                    :href="letter.manual_drive_link"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 rounded-lg border border-blue-200 dark:border-blue-800 transition shadow-2xs"
                                                    title="Buka Berkas di Google Drive"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                                    </svg>
                                                    <span>Buka Drive</span>
                                                </a>
                                                <a
                                                    v-else
                                                    :href="route('letters.download', letter.uuid)"
                                                    class="px-2.5 py-1 text-[11px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-2xs transition"
                                                    title="Unduh PDF Resmi"
                                                >
                                                    Unduh PDF
                                                </a>

                                                <!-- Tombol Arsipkan Surat Keluar -->
                                                <button
                                                    v-if="!letter.archive"
                                                    type="button"
                                                    @click="archiveOutgoing(letter)"
                                                    :disabled="archivingId === letter.id"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 rounded-lg border border-indigo-200 dark:border-indigo-800 transition shadow-2xs disabled:opacity-50"
                                                    title="Simpan surat keluar ini ke Pusat Arsip"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                    </svg>
                                                    <span>{{ archivingId === letter.id ? 'Mengarsipkan...' : 'Arsipkan' }}</span>
                                                </button>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800"
                                                    title="Surat ini sudah tersimpan di Pusat Arsip"
                                                >
                                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Diarsipkan</span>
                                                </span>
                                            </template>
                                            <Link
                                                :href="route('letters.show', letter.uuid)"
                                                class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-lg transition"
                                            >
                                                Lihat Surat
                                            </Link>

                                            <!-- Tombol Hapus Permohonan Surat -->
                                            <button
                                                type="button"
                                                @click="confirmDelete(letter)"
                                                class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                                title="Hapus Permohonan Surat"
                                                aria-label="Hapus Permohonan Surat"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS SURAT KELUAR -->
        <div
            v-if="showDeleteConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in"
        >
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-slate-800">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="text-center space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Hapus Permohonan Surat?
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Anda yakin ingin menghapus surat
                        <strong class="text-slate-700 dark:text-slate-200">
                            {{ letterToDelete?.letter_type?.name }}
                            {{ letterToDelete?.letter_number ? `(${letterToDelete?.letter_number})` : '' }}
                        </strong>
                        untuk <strong>{{ letterToDelete?.student_name }}</strong>? Dokumen PDF yang telah diterbitkan juga akan dihapus permanen.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showDeleteConfirmModal = false"
                        :disabled="isDeleting"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-md transition disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <svg v-if="isDeleting" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Surat' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

