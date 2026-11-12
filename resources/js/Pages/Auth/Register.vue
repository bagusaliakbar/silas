<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    npm: '',
    study_program: 'Sistem Informasi',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registrasi Akun" />

        <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-stretch py-2 sm:py-6">
            <!-- LEFT COLUMN: INSTITUTIONAL BRANDING & WORKFLOW HIGHLIGHT (7 Cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-sm flex flex-col justify-between">
                <!-- Top: Seal & Welcome Title -->
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/80 text-blue-900 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Portal Registrasi Mahasiswa & Civitas Fasilkom</span>
                    </div>

                    <div class="space-y-2 pt-1">
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-snug">
                            Layanan Surat & Tanda Tangan Elektronik Mandiri
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Buat akun resmi untuk mengajukan surat izin penelitian skripsi, observasi mata kuliah, surat keterangan aktif kuliah, dan memantau status persetujuan pejabat penandatangan secara *real-time*.
                        </p>
                    </div>
                </div>

                <!-- Middle: THREE SIMPLE STEPS HIGHLIGHT -->
                <div class="my-6 p-5 sm:p-6 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-blue-900 dark:text-blue-200 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>3 Langkah Mudah Persuratan Digital</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-1">
                            <div class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 text-xs font-black flex items-center justify-center">1</div>
                            <h4 class="font-bold text-xs text-slate-900 dark:text-white">Ajukan Online</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Pilih jenis surat & isi permohonan tanpa antre fisik.</p>
                        </div>

                        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-1">
                            <div class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 text-xs font-black flex items-center justify-center">2</div>
                            <h4 class="font-bold text-xs text-slate-900 dark:text-white">TTE Pejabat</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Dekan / Wadek / Kaprodi menandatangani secara digital.</p>
                        </div>

                        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-1">
                            <div class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 text-xs font-black flex items-center justify-center">3</div>
                            <h4 class="font-bold text-xs text-slate-900 dark:text-white">Unduh Berkas</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Dapatkan PDF sah ber-QR Code untuk diverifikasi publik.</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom: Key Security Features Pill List -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <span class="font-medium text-[11px]">Sertifikat Digital X.509</span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-medium text-[11px]">Segel Hash SHA-256</span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <div class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <span class="font-medium text-[11px]">Verifikasi QR Instan</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: HIGH-FIDELITY REGISTRATION CARD (5 Cols) -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="mb-5">
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Daftar Akun Baru
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Lengkapi data diri Anda di bawah ini untuk memulai akses sistem.
                        </p>
                    </div>

                    <!-- Registration Form -->
                    <form @submit.prevent="submit" class="space-y-3.5">
                        <!-- Nama Lengkap -->
                        <div>
                            <InputLabel for="name" value="Nama Lengkap" class="text-xs font-bold" />
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="pl-9 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="Budi Santoso"
                                />
                            </div>
                            <InputError class="mt-1 text-xs" :message="form.errors.name" />
                        </div>

                        <!-- NPM (Nomor Pokok Mahasiswa) & Program Studi Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- NPM -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="npm" value="NPM" class="text-xs font-bold" />
                                </div>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                        </svg>
                                    </div>
                                    <TextInput
                                        id="npm"
                                        type="text"
                                        class="pl-9 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 uppercase font-mono"
                                        v-model="form.npm"
                                        required
                                        placeholder="D1A2xxxx"
                                    />
                                </div>
                                <InputError class="mt-1 text-xs" :message="form.errors.npm" />
                            </div>

                            <!-- Program Studi -->
                            <div>
                                <InputLabel for="study_program" value="Program Studi" class="text-xs font-bold" />
                                <div class="relative mt-1">
                                    <select
                                        id="study_program"
                                        v-model="form.study_program"
                                        class="block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 py-2.5 px-3"
                                    >
                                        <option value="Sistem Informasi">Sistem Informasi</option>
                                    </select>
                                </div>
                                <InputError class="mt-1 text-xs" :message="form.errors.study_program" />
                            </div>
                        </div>

                        <!-- Email Input -->
                        <div>
                            <InputLabel for="email" value="Alamat Email" class="text-xs font-bold" />
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="pl-9 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    placeholder="nama@mail.com"
                                />
                            </div>
                            <InputError class="mt-1 text-xs" :message="form.errors.email" />
                        </div>

                        <!-- Password Input with Show/Hide Toggle -->
                        <div>
                            <InputLabel for="password" value="Kata Sandi" class="text-xs font-bold" />
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <TextInput
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="pl-9 pr-9 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Minimal 8 karakter"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                    tabindex="-1"
                                >
                                    <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-1 text-xs" :message="form.errors.password" />
                        </div>

                        <!-- Konfirmasi Kata Sandi -->
                        <div>
                            <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi" class="text-xs font-bold" />
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <TextInput
                                    id="password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    class="pl-9 pr-9 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Ulangi kata sandi"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                    tabindex="-1"
                                >
                                    <svg v-if="!showPasswordConfirmation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-1 text-xs" :message="form.errors.password_confirmation" />
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <PrimaryButton
                                class="w-full justify-center py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-xs font-bold text-white shadow-md shadow-blue-700/20 hover:shadow-blue-700/30 transition disabled:opacity-50"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="flex items-center gap-1.5">
                                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Mendaftarkan Akun...
                                </span>
                                <span v-else>Daftar Akun SILAS-TTE</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Card Footer: Login Switcher & Document Verification Link -->
                <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <p class="text-xs text-center text-slate-500">
                        Sudah memiliki akun terdaftar?
                        <Link
                            :href="route('login')"
                            class="font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 underline ms-1"
                        >
                            Masuk ke Akun
                        </Link>
                    </p>

                    <!-- Direct Verify Document Secondary Button -->
                    <div class="pt-1">
                        <Link
                            :href="route('verify.index')"
                            class="w-full inline-flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-500 text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 bg-slate-50/50 dark:bg-slate-950/50 transition group"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Hanya ingin mengecek keaslian dokumen PDF? Klik di sini</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
