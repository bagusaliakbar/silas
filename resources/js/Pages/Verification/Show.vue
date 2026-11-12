<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import VerificationDetailsCard from '@/Components/VerificationDetailsCard.vue';

const props = defineProps({
    verification: {
        type: Object,
        required: true,
    },
    token: {
        type: String,
        default: '',
    },
    qrDataUri: {
        type: String,
        default: '',
    },
});

const doc = props.verification?.document;
</script>

<template>
    <div>
        <Head :title="`Hasil Validasi - ${doc?.title || 'Dokumen TTE'}`" />

        <!-- TAMPILAN UNTUK MAHASISWA & ADMIN (TERAUTENTIKASI) -->
        <AuthenticatedLayout v-if="$page.props.auth?.user">
            <div class="py-8">
                <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <!-- TOP BAR NAVIGASI INTERNAL -->
                    <div class="flex items-center justify-between pb-2">
                        <Link
                            :href="route('verify.index')"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition group"
                        >
                            <span class="group-hover:-translate-x-0.5 transition-transform">←</span>
                            <span>Kembali ke Cek Berkas Mandiri</span>
                        </Link>
                    </div>

                    <!-- STATUS HERO BANNER -->
                    <div
                        v-if="verification.is_intact"
                        class="rounded-3xl p-6 sm:p-8 bg-emerald-50 dark:bg-emerald-950/60 border-2 border-emerald-500/30 text-emerald-950 dark:text-emerald-100 shadow-sm"
                    >
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/30">
                                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                                    {{ doc?.title || 'Dokumen Terverifikasi' }}
                                </h1>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                                    {{ verification.message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- TAMPER ALERT BANNER -->
                    <div
                        v-else
                        class="rounded-3xl p-6 sm:p-8 bg-rose-50 dark:bg-rose-950/60 border-2 border-rose-500/30 text-rose-950 dark:text-rose-100 shadow-sm"
                    >
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
                            <div class="w-16 h-16 rounded-2xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/30">
                                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 mb-2">
                                    ⚠️ PERINGATAN INTEGRITAS
                                </div>
                                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                                    Integritas Dokumen Tidak Terverifikasi
                                </h1>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                                    {{ verification.message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- DETAILS GRID COMPONENT -->
                    <VerificationDetailsCard
                        :verification="verification"
                        :token="token"
                        :qr-data-uri="qrDataUri"
                    />
                </div>
            </div>
        </AuthenticatedLayout>

        <!-- TAMPILAN UNTUK PUBLIK (TAMU / SCAN QR CODE DARI PONSEL) -->
        <div v-else class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col justify-between">
            <!-- HEADER PUBLIK -->
            <header class="border-b border-slate-200/80 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-40">
                <div class="w-full px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
                    <Link :href="route('login')" class="flex items-center gap-2.5 group">
                        <img
                            src="/images/logos/universitas-subang.png"
                            alt="Universitas Subang"
                            class="h-9 w-auto object-contain group-hover:scale-105 transition-transform"
                        />
                        <div class="flex flex-col text-left">
                            <span class="font-bold text-sm tracking-tight text-slate-900 dark:text-white leading-tight">
                                SILAS-TTE
                            </span>
                            <span class="text-[9px] font-extrabold tracking-wider uppercase text-amber-600 dark:text-amber-400">
                                FASILKOM UNSUB
                            </span>
                        </div>
                    </Link>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('verify.index')"
                            class="text-xs font-semibold px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition"
                        >
                            Cek Berkas PDF Mandiri →
                        </Link>
                    </div>
                </div>
            </header>

            <!-- MAIN VERIFICATION BODY PUBLIK -->
            <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
                <!-- STATUS HERO BANNER -->
                <div
                    v-if="verification.is_intact"
                    class="rounded-3xl p-6 sm:p-8 bg-emerald-50 dark:bg-emerald-950/60 border-2 border-emerald-500/30 text-emerald-950 dark:text-emerald-100 shadow-sm"
                >
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/30">
                            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 mb-2">
                                ✓ DOKUMEN ASLI & TERVERIFIKASI
                            </div>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                                {{ doc?.title || 'Dokumen Terverifikasi' }}
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                                {{ verification.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- TAMPER ALERT BANNER -->
                <div
                    v-else
                    class="rounded-3xl p-6 sm:p-8 bg-rose-50 dark:bg-rose-950/60 border-2 border-rose-500/30 text-rose-950 dark:text-rose-100 shadow-sm"
                >
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
                        <div class="w-16 h-16 rounded-2xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/30">
                            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 mb-2">
                                ⚠️ PERINGATAN INTEGRITAS
                            </div>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                                Integritas Dokumen Tidak Terverifikasi
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                                {{ verification.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DETAILS GRID COMPONENT -->
                <VerificationDetailsCard
                    :verification="verification"
                    :token="token"
                    :qr-data-uri="qrDataUri"
                />
            </main>

            <!-- FOOTER PUBLIK -->
            <footer class="border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-400">
                Platform Tanda Tangan Elektronik (TTE) • Sesuai Standar UU ITE No. 11/2008 & PP 71/2019
            </footer>
        </div>
    </div>
</template>
