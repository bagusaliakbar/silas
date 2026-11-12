<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MailDispatch3D from '@/Components/MailDispatch3D.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    stats: Object,
    recentLetters: Array,
    availableTypes: Array,
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Dashboard Mahasiswa" />

    <AuthenticatedLayout>
        <div class="py-5 sm:py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">
                <!-- HERO WELCOME BANNER (COMPACT & MODERN) -->
                <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-r from-blue-950 via-slate-900 to-indigo-950 text-white p-5 sm:p-6 md:py-6 md:px-7 shadow-xl border border-blue-900/50">
                    <!-- Tech Ambient Glow -->
                    <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-72 h-72 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5 sm:gap-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 flex-1">
                            <div>
                                <h1 class="text-xl sm:text-2xl font-bold tracking-tight">
                                    Selamat Datang, {{ user.name }}
                                </h1>
                                <p class="text-xs sm:text-sm text-blue-100/80 mt-1 max-w-xl">
                                    Ajukan surat keterangan akademik dan dokumen lainnya secara mandiri
                                </p>

                                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 mt-4">
                                    <Link
                                        :href="route('mahasiswa.letters.create')"
                                        class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/40 transition hover:scale-[1.02] active:scale-[0.98]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Ajukan Surat Baru</span>
                                    </Link>

                                    <Link
                                        :href="route('mahasiswa.letters.index')"
                                        class="inline-flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-blue-100 bg-white/10 hover:bg-white/20 border border-white/20 transition hover:scale-[1.02] active:scale-[0.98]"
                                    >
                                        <span>Riwayat Pengajuan</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- 3D Interactive Mail Dispatch Animation -->
                        <div class="self-center lg:self-auto shrink-0">
                            <MailDispatch3D compact />
                        </div>
                    </div>
                </div>

                <!-- STATS CARDS (INTERACTIVE QUICK FILTERS) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total -->
                    <Link
                        :href="route('mahasiswa.letters.index', { status: 'all' })"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4 hover:border-blue-400 dark:hover:border-blue-600 hover:shadow-md transition-all group cursor-pointer"
                        title="Klik untuk melihat semua surat"
                    >
                        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ stats.total }}</div>
                            <div class="text-xs font-medium text-slate-500">Total Pengajuan</div>
                        </div>
                    </Link>

                    <!-- Menunggu TTE -->
                    <Link
                        :href="route('mahasiswa.letters.index', { status: 'submitted' })"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4 hover:border-amber-400 dark:hover:border-amber-600 hover:shadow-md transition-all group cursor-pointer"
                        title="Klik untuk melihat surat yang sedang diproses"
                    >
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">{{ stats.submitted }}</div>
                            <div class="text-xs font-medium text-slate-500">Diproses</div>
                        </div>
                    </Link>

                    <!-- Selesai (Signed) -->
                    <Link
                        :href="route('mahasiswa.letters.index', { status: 'signed' })"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4 hover:border-emerald-400 dark:hover:border-emerald-600 hover:shadow-md transition-all group cursor-pointer"
                        title="Klik untuk melihat surat yang sudah selesai dan ber-TTE"
                    >
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ stats.signed }}</div>
                            <div class="text-xs font-medium text-slate-500">Selesai</div>
                        </div>
                    </Link>

                    <!-- Ditolak -->
                    <Link
                        :href="route('mahasiswa.letters.index', { status: 'rejected' })"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4 hover:border-rose-400 dark:hover:border-rose-600 hover:shadow-md transition-all group cursor-pointer"
                        title="Klik untuk melihat surat yang ditolak"
                    >
                        <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">{{ stats.rejected }}</div>
                            <div class="text-xs font-medium text-slate-500">Permohonan Ditolak</div>
                        </div>
                    </Link>
                </div>

                <!-- QUICK SERVICE CATALOG -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                Layanan Permohonan Surat Tersedia
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Pilih salah satu jenis surat di bawah ini untuk memulai pengisian formulir
                            </p>
                        </div>
                        <Link
                            :href="route('mahasiswa.letters.create')"
                            class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Buka Formulir Lengkap →
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div
                            v-for="type in availableTypes.slice(0, 6)"
                            :key="type.id"
                            class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50/50 dark:bg-slate-950/50 hover:bg-white dark:hover:bg-slate-900 transition flex flex-col justify-between gap-3 group"
                        >
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ type.name }}
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                    {{ type.description }}
                                </p>
                            </div>

                            <Link
                                :href="route('mahasiswa.letters.create', { type: type.code })"
                                class="inline-flex items-center justify-center gap-1.5 w-full py-1.5 px-3 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition"
                            >
                                <span>Ajukan Surat Ini</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- RECENT SUBMISSIONS TABLE -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                Pengajuan Surat Terakhir Anda
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Pantau status penandatanganan berkas dan unduh surat yang telah sah disegel
                            </p>
                        </div>
                        <Link
                            :href="route('mahasiswa.letters.index')"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            <span>Lihat Semua Riwayat ({{ stats.total }})</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div v-if="recentLetters.length === 0" class="p-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Pengajuan Surat</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Anda belum pernah mengajukan surat permohonan. Klik tombol di bawah untuk membuat permohonan pertama Anda.
                        </p>
                        <Link
                            :href="route('mahasiswa.letters.create')"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition"
                        >
                            + Buat Pengajuan Surat Sekarang
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 font-semibold border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-3.5">Jenis & Nomor Surat</th>
                                    <th class="px-6 py-3.5">Keperluan</th>
                                    <th class="px-6 py-3.5">Penandatangan</th>
                                    <th class="px-6 py-3.5">Tanggal Pengajuan</th>
                                    <th class="px-6 py-3.5">Status</th>
                                    <th class="px-6 py-3.5 text-right">Aksi Dokumen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="letter in recentLetters"
                                    :key="letter.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ letter.letter_type?.name }}
                                        </div>
                                        <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                            No: {{ letter.letter_number || 'Draf / Menunggu TTE' }}
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
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Selesai
                                        </span>
                                        <span
                                            v-else-if="letter.status === 'rejected'"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800"
                                        >
                                            Ditolak
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Proses
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Download if signed & allowed -->
                                            <a
                                                v-if="letter.status === 'signed' && letter.can_download_pdf"
                                                :href="route('mahasiswa.letters.download', letter.uuid)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition"
                                                title="Unduh Berkas PDF Resmi yang sudah disegel TTE"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                <span>Unduh PDF</span>
                                            </a>
                                            <span
                                                v-else-if="letter.status === 'signed'"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800"
                                                title="Surat dicetak Fakultas. Ambil fisik di bagian TU."
                                            >
                                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Ambil di TU
                                            </span>

                                            <!-- Public Verification Link if available -->
                                            <a
                                                v-if="letter.document?.verification_token"
                                                :href="route('documents.verify', letter.document.verification_token)"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition"
                                                title="Periksa Validasi Sertifikat Digital & QR Code"
                                            >
                                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Cek QR</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

