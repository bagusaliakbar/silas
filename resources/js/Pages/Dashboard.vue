<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MailDispatch3D from '@/Components/MailDispatch3D.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    letterStats: Object,
    documentStats: Object,
    certificateStats: Object,
    pendingLetters: Array,
    recentLetters: Array,
    recentDocuments: Array,
    activeSigners: Array,
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'signed':
            return {
                label: 'Sudah TTE Sah',
                class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200/80',
            };
        case 'submitted':
            return {
                label: 'Menunggu TTE',
                class: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200/80 animate-pulse',
            };
        case 'rejected':
            return {
                label: 'Perlu Revisi',
                class: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-200/80',
            };
        default:
            return {
                label: 'Draft',
                class: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200/80',
            };
    }
};
</script>

<template>
    <Head title="Dashboard Admin" />

    <AuthenticatedLayout>
        <div class="py-5 sm:py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">
                <!-- HERO EXECUTIVE BANNER (COMPACT & MODERN) -->
                <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-r from-blue-950 via-slate-900 to-indigo-950 text-white p-5 sm:p-6 md:py-6 md:px-7 shadow-xl border border-blue-900/50">
                    <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-80 h-80 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5 sm:gap-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 flex-1">
                            <div>
                                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">
                                    Selamat Datang, {{ user.name }}
                                </h1>
                                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl leading-relaxed">
                                    Sistem Informasi Layanan Akademik & Tanda Tangan Elektronik (SILAS-TTE) Fakultas Ilmu Komputer Universitas Subang.
                                </p>

                                <!-- Quick Actions in Hero -->
                                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 mt-4">
                                    <Link
                                        :href="route('letters.create')"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/40 transition hover:scale-[1.02] active:scale-[0.98]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Buat Surat Baru</span>
                                    </Link>

                                    <Link
                                        :href="route('documents.create')"
                                        class="inline-flex items-center justify-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-blue-100 bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition hover:scale-[1.02] active:scale-[0.98]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span>Unggah Berkas</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- 3D Interactive Mail Dispatch Animation -->
                        <div class="self-center lg:self-auto shrink-0">
                            <MailDispatch3D />
                        </div>
                    </div>
                </div>

                <!-- 4 METRICS CARDS ROW -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Stat 1: Permohonan Surat Pending -->
                    <Link
                        :href="route('letters.index')"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-amber-400 dark:hover:border-amber-500/50 transition group"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                Menunggu TTE
                            </span>
                            <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">
                                {{ letterStats.pending || 0 }}
                            </span>
                            <span v-if="letterStats.pending > 0" class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                Butuh Tindakan
                            </span>
                            <span v-else class="text-xs font-medium text-slate-400">
                                Bersih
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">
                            Permohonan surat mahasiswa siap ditandatangani
                        </p>
                    </Link>

                    <!-- Stat 2: Surat Sah Diterbitkan -->
                    <Link
                        :href="route('letters.index')"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-emerald-400 dark:hover:border-emerald-500/50 transition group"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                Surat Terbit Sah
                            </span>
                            <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">
                                {{ letterStats.signed || 0 }}
                            </span>
                            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                / {{ letterStats.total || 0 }} Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">
                            Dokumen resmi ber-segel digital & QR Code
                        </p>
                    </Link>

                    <!-- Stat 3: Dokumen & Berkas Digital -->
                    <Link
                        :href="route('documents.index')"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-blue-400 dark:hover:border-blue-500/50 transition group"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                TTE Berkas Mandiri
                            </span>
                            <span class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">
                                {{ (documentStats.signed ?? documentStats.completed) || 0 }}
                            </span>
                            <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                                / {{ documentStats.total || 0 }} Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">
                            Berkas SK, Berita Acara & Dokumen PDF umum
                        </p>
                    </Link>

                    <!-- Stat 4: Sertifikat X.509 Aktif -->
                    <Link
                        :href="route('certificates.index')"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-indigo-400 dark:hover:border-indigo-500/50 transition group"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                Sertifikat Digital (PKI)
                            </span>
                            <span class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 dark:text-white">
                                {{ certificateStats.active || 0 }}
                            </span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                Aktif
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2 truncate">
                            {{ certificateStats.default?.name || 'Sertifikat Default Tersedia' }}
                        </p>
                    </Link>
                </div>

                <!-- MAIN WORKSPACE: 2 COLUMNS (8 COLS LEFT, 4 COLS RIGHT) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- LEFT COLUMN: PERMOHONAN MENUNGGU TTE (8 COLS) -->
                    <div class="lg:col-span-8 space-y-6">
                        <!-- Pending Letters Panel -->
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 flex items-center justify-center font-bold text-xs">
                                        {{ pendingLetters?.length || 0 }}
                                    </span>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                            Permohonan Surat Menunggu TTE
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Surat yang siap diverifikasi dan dibubuhkan tanda tangan elektronik pejabat
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    :href="route('letters.index')"
                                    class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                                >
                                    <span>Lihat Semua</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>

                            <!-- Table or Empty State -->
                            <div v-if="pendingLetters && pendingLetters.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800">
                                <div
                                    v-for="letter in pendingLetters"
                                    :key="letter.id"
                                    class="p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                >
                                    <div class="flex items-start gap-3.5 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                            DOC
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                                    {{ letter.student_name }}
                                                </h4>
                                                <span class="text-xs font-mono text-slate-400">
                                                    ({{ letter.npm || '-' }})
                                                </span>
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800">
                                                    {{ letter.letter_type?.name || 'Surat Akademik' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-slate-500 mt-1 flex-wrap">
                                                <span>Diajukan: {{ formatDate(letter.created_at) }}</span>
                                                <span>•</span>
                                                <span>Penandatangan: <strong>{{ letter.signer_title || 'Pejabat Fasilkom' }}</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 w-full sm:w-auto shrink-0 justify-end">
                                        <Link
                                            :href="route('letters.show', letter.uuid)"
                                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition active:scale-95"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Tinjau & TTE</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State When No Pending -->
                            <div v-else class="p-8 text-center space-y-3">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                    Semua Permohonan Telah Diproses!
                                </h4>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                    Tidak ada antrean permohonan surat yang menunggu tanda tangan saat ini.
                                </p>
                            </div>
                        </div>

                        <!-- Recent Letters Activity -->
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    Aktivitas Surat Terbit Terbaru
                                </h3>
                                <Link :href="route('letters.index')" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    Lihat Arsip
                                </Link>
                            </div>

                            <div v-if="recentLetters && recentLetters.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800">
                                <div
                                    v-for="item in recentLetters"
                                    :key="item.id"
                                    class="py-3 flex items-center justify-between text-xs gap-3"
                                >
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800 dark:text-slate-200 truncate">
                                            {{ item.student_name }}
                                            <span class="font-normal text-slate-400">({{ item.letter_type?.name }})</span>
                                        </p>
                                        <p class="text-[11px] font-mono text-slate-400 truncate">
                                            No: {{ item.letter_number || 'Dalam Proses' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                            :class="getStatusBadge(item.status).class"
                                        >
                                            {{ getStatusBadge(item.status).label }}
                                        </span>
                                        <Link
                                            :href="route('letters.show', item.uuid)"
                                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                                        >
                                            Detail
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: SYSTEM STATUS & QUICK SHORTCUTS (4 COLS) -->
                    <div class="lg:col-span-4 space-y-6">
                        <!-- Digital Signature & Certificate Status -->
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 flex items-center justify-center font-bold text-xs">
                                    PKI
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Sertifikat Digital (X.509)
                                    </h3>
                                    <p class="text-[11px] text-slate-400">Infrastruktur Kunci Publik Fasilkom</p>
                                </div>
                            </div>

                            <div v-if="certificateStats.default" class="p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/70 dark:border-blue-800/60 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Nama Pemilik:</span>
                                    <strong class="text-slate-900 dark:text-white">{{ certificateStats.default.name }}</strong>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Instansi / Lembaga:</span>
                                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ certificateStats.default.organization || 'Universitas Subang' }}</span>
                                </div>
                                <div v-if="certificateStats.default.organizational_unit" class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Unit Kerja:</span>
                                    <span class="text-slate-800 dark:text-slate-200 font-medium">{{ certificateStats.default.organizational_unit }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Masa Berlaku:</span>
                                    <span class="text-slate-800 dark:text-slate-200 font-mono text-[11px]">
                                        s/d {{ formatDate(certificateStats.default.valid_to) }}
                                    </span>
                                </div>
                                <div class="pt-2 border-t border-blue-200/50 dark:border-blue-800/50 flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Sertifikat Default Siap Pakai</span>
                                </div>
                            </div>

                            <div v-else class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-700 dark:text-amber-300">
                                Belum ada sertifikat default yang diatur. Silakan impor atau buat sertifikat baru.
                            </div>

                            <Link
                                :href="route('certificates.index')"
                                class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                            >
                                <span>Kelola Sertifikat X.509</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>

                        <!-- Active Signers Widget -->
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Pejabat Penandatangan
                                </h3>
                                <Link :href="route('settings.signers.index')" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    Atur
                                </Link>
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    v-for="signer in activeSigners"
                                    :key="signer.id"
                                    class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs"
                                >
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-slate-200">
                                            {{ signer.name }}
                                        </p>
                                        <p class="text-[11px] text-slate-400">
                                            {{ signer.role_title }}
                                        </p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick System Shortcuts -->
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-5 sm:p-6 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                Menu & Pengaturan Cepat
                            </h3>

                            <Link
                                :href="route('letter-templates.index')"
                                class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 hover:bg-blue-50 dark:bg-slate-800/50 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 transition group"
                            >
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Editor Template Surat</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>

                            <Link
                                :href="route('settings.signers.index')"
                                class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 hover:bg-blue-50 dark:bg-slate-800/50 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 transition group"
                            >
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Pejabat Penandatangan</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>

                            <Link
                                :href="route('verify.index')"
                                class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 hover:bg-blue-50 dark:bg-slate-800/50 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 transition group"
                            >
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Portal Verifikasi Publik</span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
