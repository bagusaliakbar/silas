<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    verificationResult: {
        type: Object,
        default: null,
    },
});

const inputToken = ref('');

const uploadForm = useForm({
    pdf_file: null,
});

const isDragging = ref(false);
const fileInput = ref(null);
const fileName = ref('');

const searchToken = () => {
    if (!inputToken.value.trim()) return;
    router.visit(route('documents.verify', inputToken.value.trim()));
};

const onFileSelect = (event) => {
    const file = event.target.files[0];
    handleFile(file);
};

const onDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    handleFile(file);
};

const handleFile = (file) => {
    if (!file || !file.name.endsWith('.pdf')) {
        alert('Silakan pilih berkas PDF');
        return;
    }
    uploadForm.pdf_file = file;
    fileName.value = file.name;
};

const submitVerifyFile = () => {
    uploadForm.post(route('verify.file'), {
        forceFormData: true,
    });
};
</script>

<template>
    <div>
        <Head title="Portal Verifikasi Dokumen" />

        <!-- TAMPILAN UNTUK MAHASISWA & ADMIN (TERAUTENTIKASI) -->
        <AuthenticatedLayout v-if="$page.props.auth?.user">
            <div class="py-8">
                <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    <!-- HERO / HEADER SECTION -->
                    <div class="text-center max-w-xl mx-auto">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/80 text-blue-900 dark:text-blue-200 border border-blue-200 dark:border-blue-800 mb-3 shadow-xs">
                            Verifikasi Keabsahan Tanda Tangan Elektronik
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            Buktikan Keaslian Dokumen Anda
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                            Periksa apakah dokumen PDF telah ditandatangani secara sah dan pastikan berkas belum dimodifikasi atau dipalsukan.
                        </p>
                    </div>

                    <!-- HASIL VERIFIKASI (JIKA ADA) -->
                    <div v-if="verificationResult" class="space-y-4">
                        <div
                            v-if="verificationResult.found && verificationResult.is_intact"
                            class="p-6 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 font-bold text-xl shadow-md shadow-emerald-500/20">
                                    ✓
                                </div>
                                <div>
                                    <h3 class="font-bold text-base">Dokumen ASLI & Terverifikasi</h3>
                                    <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                                        {{ verificationResult.message }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                v-if="verificationResult.document"
                                :href="route('documents.verify', verificationResult.document.verification_token)"
                                class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shrink-0"
                            >
                                Lihat Rincian Sertifikat →
                            </Link>
                        </div>

                        <div
                            v-else
                            class="p-6 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-100 flex items-center gap-4 shadow-sm"
                        >
                            <div class="w-12 h-12 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 font-bold text-xl shadow-md shadow-rose-500/20">
                                ✕
                            </div>
                            <div>
                                <h3 class="font-bold text-base">Dokumen Tidak Cocok / Terdeteksi Modifikasi</h3>
                                <p class="text-xs text-rose-700 dark:text-rose-300 mt-0.5">
                                    {{ verificationResult.message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2 METODE VERIFIKASI -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- METHOD 1: UPLOAD PDF FILE -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-xs font-bold">1</span>
                                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Unggah Berkas PDF</h3>
                                </div>
                                <p class="text-xs text-slate-500 mb-4">
                                    Sistem akan menghitung sidik jari kriptografis (SHA-256) file dan membandingkannya dengan segel resmi.
                                </p>

                                <div
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="onDrop"
                                    @click="fileInput.click()"
                                    :class="[
                                        'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition',
                                        isDragging
                                            ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20'
                                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-950'
                                    ]"
                                >
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="application/pdf"
                                        class="hidden"
                                        @change="onFileSelect"
                                    />
                                    <div v-if="fileName" class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                        Berkas: {{ fileName }}
                                    </div>
                                    <div v-else class="text-xs text-slate-500">
                                        Tarik berkas PDF ke sini atau <span class="text-blue-600 dark:text-blue-400 font-semibold underline">pilih berkas</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="submitVerifyFile"
                                :disabled="!uploadForm.pdf_file || uploadForm.processing"
                                class="w-full mt-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-40 transition shadow-sm"
                            >
                                <span v-if="uploadForm.processing">Menganalisis Hash SHA-256...</span>
                                <span v-else>Verifikasi Integritas File PDF</span>
                            </button>
                        </div>

                        <!-- METHOD 2: ENTER VERIFICATION CODE -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-xs font-bold">2</span>
                                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Masukkan Token / ID Verifikasi</h3>
                                </div>
                                <p class="text-xs text-slate-500 mb-4">
                                    Masukkan token verifikasi yang tertera di bawah QR Code tanda tangan pada dokumen.
                                </p>

                                <div class="space-y-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">ID Dokumen / Token:</label>
                                    <input
                                        v-model="inputToken"
                                        @keyup.enter="searchToken"
                                        type="text"
                                        placeholder="a1b2c3d4e5f6..."
                                        class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="searchToken"
                                :disabled="!inputToken.trim()"
                                class="w-full mt-4 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-40 transition shadow-sm"
                            >
                                Cari Bukti Tanda Tangan Elektronik →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>

        <!-- TAMPILAN UNTUK PUBLIK (TAMU / BELUM LOGIN) -->
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

                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('login')"
                            class="text-xs font-semibold px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-slate-700 dark:text-slate-200"
                        >
                            Masuk Akun →
                        </Link>
                    </div>
                </div>
            </header>

            <!-- MAIN BODY PUBLIK -->
            <main class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
                <div class="text-center max-w-xl mx-auto">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/80 text-blue-900 dark:text-blue-200 border border-blue-200 dark:border-blue-800 mb-3 shadow-xs">
                        Verifikasi Keabsahan Tanda Tangan Elektronik
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        Buktikan Keaslian Dokumen Anda
                    </h1>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                        Periksa apakah dokumen PDF telah ditandatangani secara sah dan pastikan berkas belum dimodifikasi atau dipalsukan.
                    </p>
                </div>

                <!-- HASIL VERIFIKASI (JIKA ADA) -->
                <div v-if="verificationResult" class="space-y-4">
                    <div
                        v-if="verificationResult.found && verificationResult.is_intact"
                        class="p-6 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 font-bold text-xl shadow-md shadow-emerald-500/20">
                                ✓
                            </div>
                            <div>
                                <h3 class="font-bold text-base">Dokumen ASLI & Terverifikasi</h3>
                                <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                                    {{ verificationResult.message }}
                                </p>
                            </div>
                        </div>
                        <Link
                            v-if="verificationResult.document"
                            :href="route('documents.verify', verificationResult.document.verification_token)"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shrink-0"
                        >
                            Lihat Rincian Sertifikat →
                        </Link>
                    </div>

                    <div
                        v-else
                        class="p-6 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-100 flex items-center gap-4 shadow-sm"
                    >
                        <div class="w-12 h-12 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 font-bold text-xl shadow-md shadow-rose-500/20">
                            ✕
                        </div>
                        <div>
                            <h3 class="font-bold text-base">Dokumen Tidak Cocok / Terdeteksi Modifikasi</h3>
                            <p class="text-xs text-rose-700 dark:text-rose-300 mt-0.5">
                                {{ verificationResult.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2 METODE VERIFIKASI -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- METHOD 1: UPLOAD PDF FILE -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-xs font-bold">1</span>
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white">Unggah Berkas PDF</h3>
                            </div>
                            <p class="text-xs text-slate-500 mb-4">
                                Sistem akan menghitung sidik jari kriptografis (SHA-256) file dan membandingkannya dengan segel resmi.
                            </p>

                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="onDrop"
                                @click="fileInput.click()"
                                :class="[
                                    'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition',
                                    isDragging
                                        ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20'
                                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50 dark:bg-slate-950'
                                ]"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="application/pdf"
                                    class="hidden"
                                    @change="onFileSelect"
                                />
                                <div v-if="fileName" class="text-xs font-bold text-blue-600 dark:text-blue-400">
                                    Berkas: {{ fileName }}
                                </div>
                                <div v-else class="text-xs text-slate-500">
                                    Tarik berkas PDF ke sini atau <span class="text-blue-600 dark:text-blue-400 font-semibold underline">pilih berkas</span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitVerifyFile"
                            :disabled="!uploadForm.pdf_file || uploadForm.processing"
                            class="w-full mt-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-40 transition shadow-sm"
                        >
                            <span v-if="uploadForm.processing">Menganalisis Hash SHA-256...</span>
                            <span v-else>Verifikasi Integritas File PDF</span>
                        </button>
                    </div>

                    <!-- METHOD 2: ENTER VERIFICATION CODE -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-xs font-bold">2</span>
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white">Masukkan Token / ID Verifikasi</h3>
                            </div>
                            <p class="text-xs text-slate-500 mb-4">
                                Masukkan token verifikasi yang tertera di bawah QR Code tanda tangan pada dokumen.
                            </p>

                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">ID Dokumen / Token:</label>
                                <input
                                    v-model="inputToken"
                                    @keyup.enter="searchToken"
                                    type="text"
                                    placeholder="a1b2c3d4e5f6..."
                                    class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="searchToken"
                            :disabled="!inputToken.trim()"
                            class="w-full mt-4 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-40 transition shadow-sm"
                        >
                            Cari Bukti Tanda Tangan Elektronik →
                        </button>
                    </div>
                </div>
            </main>

            <!-- FOOTER PUBLIK -->
            <footer class="border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-400">
                Platform Tanda Tangan Elektronik (TTE) • Dilindungi Kriptografi Asimetris & SHA-256
            </footer>
        </div>
    </div>
</template>
