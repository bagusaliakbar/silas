<script setup>
import { ref, computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const focusedField = ref(null); // 'login' | 'password' | null

// 3D Interactive Card Tilt State
const cardRef = ref(null);
const tiltX = ref(0);
const tiltY = ref(0);
const glareX = ref(50);
const glareY = ref(30);
const isHoveringCard = ref(false);

const handleCardMouseMove = (e) => {
    if (!cardRef.value) return;
    const rect = cardRef.value.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    // Smooth 3D tilt angles
    tiltY.value = ((x - centerX) / centerX) * 8;
    tiltX.value = -((y - centerY) / centerY) * 7;

    glareX.value = (x / rect.width) * 100;
    glareY.value = (y / rect.height) * 100;
    isHoveringCard.value = true;
};

const handleCardMouseLeave = () => {
    tiltX.value = 0;
    tiltY.value = 0;
    glareX.value = 50;
    glareY.value = 30;
    isHoveringCard.value = false;
};

// 3D Guardian / Lock Core tracking angle when typing username
const lookAngleY = computed(() => {
    if (focusedField.value === 'login') {
        const len = Math.min((form.login || '').length, 24);
        return -18 + (len / 24) * 36; // Sweeps from -18deg to +18deg as user types
    }
    if (focusedField.value === 'password') {
        return showPassword.value ? 12 : 0;
    }
    return tiltY.value * 1.6;
});

const lookAngleX = computed(() => {
    if (focusedField.value === 'login') {
        return -14; // Tilts down to watch username input
    }
    if (focusedField.value === 'password') {
        return showPassword.value ? -8 : 6; // Slightly tilts head up when closing eyes
    }
    return tiltX.value * 1.4;
});

// Eye pupil horizontal offset when typing username
const eyePupilOffsetX = computed(() => {
    if (focusedField.value === 'login') {
        const len = Math.min((form.login || '').length, 24);
        return -3.5 + (len / 24) * 7; // Moves from -3.5px to +3.5px
    }
    if (focusedField.value === 'password' && showPassword.value) {
        return 2;
    }
    return 0;
});

const eyePupilOffsetY = computed(() => {
    if (focusedField.value === 'login') return 2;
    if (focusedField.value === 'password' && showPassword.value) return 1.5;
    return 0;
});

const securityStatusText = computed(() => {
    if (form.processing) return 'Mengenkripsi Sesi...';
    if (focusedField.value === 'password') {
        return showPassword.value ? 'Mode Intip Sandi' : 'Privasi Sandi Terkunci';
    }
    if (focusedField.value === 'login') {
        return form.login.length > 0 ? 'Memindai Identitas...' : 'Menunggu Username...';
    }
    if (form.login && form.password) return 'Siap Otentikasi';
    return 'S I L A S';
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-stretch py-2 sm:py-6">
            <!-- LEFT COLUMN: INSTITUTIONAL BRANDING & BENTO SERVICES GRID (7 Cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-sm flex flex-col justify-between relative overflow-hidden">
                <!-- Subtle Ambient Glow -->
                <div class="pointer-events-none absolute -top-16 -right-16 w-56 h-56 rounded-full bg-blue-500/5 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-16 -left-16 w-56 h-56 rounded-full bg-indigo-500/5 blur-3xl"></div>

                <!-- Top: Welcome Title -->
                <div class="relative z-10 space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-snug pt-1">
                        Sistem Informasi Layanan Surat (SILAS)
                    </h1>
                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400">
                        Fakultas Ilmu Komputer Universitas Subang
                    </p>
                </div>

                <!-- Middle: BENTO GRID 4 LAYANAN UTAMA SILAS -->
                <div class="relative z-10 my-6 grid grid-cols-1 sm:grid-cols-2 gap-3.5 bento-perspective">
                    <!-- 1. Surat Akademik Mahasiswa -->
                    <div class="bento-card group relative p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-950/70 border border-slate-200/80 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 hover:bg-white dark:hover:bg-slate-900 transition-all duration-300">
                        <span class="absolute top-3.5 right-4 text-[10px] font-mono font-bold text-slate-300 dark:text-slate-700 group-hover:text-blue-500/60 transition-colors">01</span>
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-700/80 shadow-xs flex items-center justify-center shrink-0 text-blue-600 dark:text-blue-400 group-hover:border-blue-200 group-hover:bg-blue-50/50 dark:group-hover:bg-blue-950/50 transition-all duration-300">
                                <!-- Academic Cap / Mortarboard Duotone -->
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 4L3 8.5L12 13L21 8.5L12 4Z" fill="currentColor" fill-opacity="0.16" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" />
                                    <path d="M6.5 10.5V15.5C6.5 17.1569 8.96243 18.5 12 18.5C15.0376 18.5 17.5 17.1569 17.5 15.5V10.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M21 8.5V14.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                                </svg>
                            </div>
                            <div class="min-w-0 pr-5">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    Surat Akademik
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Pengajuan surat aktif kuliah, pengantar magang/KP, izin penelitian, & administrasi akademik.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Surat Keputusan (SK Dekan) -->
                    <div class="bento-card group relative p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-950/70 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-white dark:hover:bg-slate-900 transition-all duration-300">
                        <span class="absolute top-3.5 right-4 text-[10px] font-mono font-bold text-slate-300 dark:text-slate-700 group-hover:text-indigo-500/60 transition-colors">02</span>
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-700/80 shadow-xs flex items-center justify-center shrink-0 text-indigo-600 dark:text-indigo-400 group-hover:border-indigo-200 group-hover:bg-indigo-50/50 dark:group-hover:bg-indigo-950/50 transition-all duration-300">
                                <!-- Official Ribbon Seal / Decree Badge Duotone -->
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="9.5" r="5.5" fill="currentColor" fill-opacity="0.16" stroke="currentColor" stroke-width="1.75" />
                                    <path d="M9.8 9.5L11.3 11L14.3 8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M9 14.2L7.5 20L12 17.8L16.5 20L15 14.2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="min-w-0 pr-5">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    Surat Keputusan (SK)
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Penerbitan SK Pembimbing Tugas Akhir, BKP MBKM, penguji, serta kepanitiaan resmi fakultas.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Nota Kesepahaman (MoU) -->
                    <div class="bento-card group relative p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-950/70 border border-slate-200/80 dark:border-slate-800 hover:border-sky-300 dark:hover:border-sky-700 hover:bg-white dark:hover:bg-slate-900 transition-all duration-300">
                        <span class="absolute top-3.5 right-4 text-[10px] font-mono font-bold text-slate-300 dark:text-slate-700 group-hover:text-sky-500/60 transition-colors">03</span>
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-700/80 shadow-xs flex items-center justify-center shrink-0 text-sky-600 dark:text-sky-400 group-hover:border-sky-200 group-hover:bg-sky-50/50 dark:group-hover:bg-sky-950/50 transition-all duration-300">
                                <!-- Signed Contract / Partnership Document Duotone -->
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="3.5" width="13" height="17" rx="2.5" fill="currentColor" fill-opacity="0.15" stroke="currentColor" stroke-width="1.75" />
                                    <path d="M8 8H13M8 11.5H11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                                    <path d="M14.5 16.5L19.5 11.5C20.1 10.9 21 10.9 21.5 11.5C22.1 12.1 22.1 13 21.5 13.5L16.5 18.5L13.5 19.2L14.5 16.5Z" fill="currentColor" fill-opacity="0.25" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="min-w-0 pr-5">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                    Nota Kesepahaman (MoU)
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Penyusunan dokumen perjanjian kerja sama magang & kemitraan institusi secara terstandarisasi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Verifikasi Dokumen & QR (Clickable Link to Verify) -->
                    <Link
                        :href="route('verify.index')"
                        class="bento-card group relative p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-950/70 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-700 hover:bg-white dark:hover:bg-slate-900 transition-all duration-300 block"
                    >
                        <span class="absolute top-3.5 right-4 text-[10px] font-mono font-bold text-slate-300 dark:text-slate-700 group-hover:text-emerald-500/70 transition-colors">04</span>
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-700/80 shadow-xs flex items-center justify-center shrink-0 text-emerald-600 dark:text-emerald-400 group-hover:border-emerald-200 group-hover:bg-emerald-50/50 dark:group-hover:bg-emerald-950/50 transition-all duration-300">
                                <!-- QR Matrix + Verification Badge Duotone -->
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <rect x="3.5" y="3.5" width="6.5" height="6.5" rx="1.5" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.75" />
                                    <rect x="14" y="3.5" width="6.5" height="6.5" rx="1.5" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.75" />
                                    <rect x="3.5" y="14" width="6.5" height="6.5" rx="1.5" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.75" />
                                    <path d="M14 17.2L16.2 19.4L20.5 14.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1 pr-5">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    Verifikasi & QR Code
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Validasi keaslian tanda tangan digital X.509 & pindai QR Code dokumen tanpa perlu login.
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Bottom: Key Security Features Pill List -->
                <div class="relative z-10 pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <span class="font-medium text-[11px]">Sertifikat Digital X.509</span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <div class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-medium text-[11px]">Integritas SHA-256</span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <span class="font-medium text-[11px]">Verifikasi QR Instan</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: 3D INTERACTIVE LOGIN FORM CARD (5 Cols) -->
            <div
                ref="cardRef"
                @mousemove="handleCardMouseMove"
                @mouseleave="handleCardMouseLeave"
                class="lg:col-span-5 login-perspective-wrapper flex"
            >
                <div
                    class="login-3d-card w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col justify-between relative"
                    :class="{
                        'ring-2 ring-blue-500/25 border-blue-300 dark:border-blue-800': focusedField === 'login',
                        'ring-2 ring-indigo-500/30 border-indigo-300 dark:border-indigo-800': focusedField === 'password',
                    }"
                    :style="{
                        transform: `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`,
                    }"
                >
                    <!-- Dynamic 3D Specular Glare Overlay -->
                    <div
                        class="pointer-events-none absolute inset-0 rounded-3xl overflow-hidden transition-opacity duration-300 z-0"
                        :style="{
                            opacity: isHoveringCard || focusedField ? 1 : 0,
                            background: `radial-gradient(420px circle at ${glareX}% ${glareY}%, rgba(59, 130, 246, 0.09), rgba(99, 102, 241, 0.04) 40%, transparent 75%)`,
                        }"
                    ></div>

                    <!-- Floating Ambient 3D Corner Orbs inside Card -->
                    <div class="pointer-events-none absolute -top-6 -right-6 w-28 h-28 rounded-full bg-blue-500/10 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-6 -left-6 w-28 h-28 rounded-full bg-indigo-500/10 blur-2xl"></div>

                    <div class="relative z-10 preserve-3d">
                        <!-- 3D REACTIVE SECURITY CORE & HEADER -->
                        <div class="mb-6 flex items-center justify-between gap-4 layer-z-30">
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase mb-2 transition-colors duration-300"
                                    :class="[
                                        focusedField === 'password'
                                            ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800'
                                            : (form.login && form.password)
                                                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800'
                                                : 'bg-blue-50 text-blue-600 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800'
                                    ]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                                        :class="[
                                            focusedField === 'password' ? 'bg-indigo-500' : (form.login && form.password) ? 'bg-emerald-500' : 'bg-blue-500'
                                        ]"
                                    ></span>
                                    <span>{{ securityStatusText }}</span>
                                </div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                    Masuk ke Akun
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Silahkan masuk sesuai <strong class="text-slate-700 dark:text-slate-200">Username & Password</strong> Masing-Masing Akun.
                                </p>
                            </div>

                            <!-- 3D Interactive Lock / Cyber Guardian Widget -->
                            <div class="guardian-stage shrink-0" title="Sensor Keamanan 3D Interaktif">
                                <div
                                    class="guardian-rig"
                                    :class="{
                                        'is-typing-user': focusedField === 'login',
                                        'is-typing-pass': focusedField === 'password' && !showPassword,
                                        'is-peeking-pass': focusedField === 'password' && showPassword,
                                        'is-submitting': form.processing,
                                        'is-ready': form.login && form.password && !focusedField,
                                    }"
                                    :style="{
                                        transform: `rotateX(${lookAngleX}deg) rotateY(${lookAngleY}deg)`,
                                    }"
                                >
                                    <!-- Outer 3D Gyro Ring -->
                                    <div class="guardian-ring ring-outer"></div>
                                    <div class="guardian-ring ring-inner"></div>

                                    <!-- 3D Shackle (Lock Arch at Top) -->
                                    <div class="guardian-shackle"></div>

                                    <!-- 3D Core Body -->
                                    <div class="guardian-body">
                                        <!-- Glowing Cyber Eyes / Scanner Visor -->
                                        <div class="guardian-visor">
                                            <span
                                                class="visor-eye eye-left"
                                                :style="{
                                                    '--eye-x': `${eyePupilOffsetX}px`,
                                                    '--eye-y': `${eyePupilOffsetY}px`,
                                                }"
                                            ></span>
                                            <span
                                                class="visor-eye eye-right"
                                                :style="{
                                                    '--eye-x': `${eyePupilOffsetX}px`,
                                                    '--eye-y': `${eyePupilOffsetY}px`,
                                                }"
                                            ></span>
                                        </div>
                                    </div>

                                    <!-- 3D Floor Shadow -->
                                    <div class="guardian-shadow"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Flash / Session Status -->
                        <div
                            v-if="status"
                            class="mb-4 text-xs font-semibold p-3 rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 layer-z-20"
                        >
                            {{ status }}
                        </div>

                        <!-- Login Form with 3D Elevating Fields -->
                        <form @submit.prevent="submit" class="space-y-4 preserve-3d">
                            <!-- NPM / Username Input -->
                            <div
                                class="field-3d-layer rounded-2xl transition-all duration-300"
                                :class="{ 'field-3d-active': focusedField === 'login' }"
                            >
                                <div class="flex items-center justify-between">
                                    <InputLabel for="login" value="NPM / NIP / Username" class="text-xs font-bold" />
                                    <span
                                        v-if="focusedField === 'login' && form.login.length > 0"
                                        class="text-[10px] font-mono font-semibold text-blue-600 dark:text-blue-400"
                                    >
                                        {{ form.login.length }} karakter
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Mahasiswa gunakan NPM, Dosen & Staf gunakan Username / NIP
                                </p>
                                <div class="relative mt-1">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors duration-200"
                                        :class="focusedField === 'login' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <TextInput
                                        id="login"
                                        type="text"
                                        class="pl-9 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 font-medium transition-shadow"
                                        v-model="form.login"
                                        @focus="focusedField = 'login'"
                                        @blur="focusedField = null"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        placeholder="Masukkan NPM atau Username / NIP"
                                    />
                                </div>
                                <InputError class="mt-1.5 text-xs" :message="form.errors.login || form.errors.email" />
                            </div>

                            <!-- Password Input with Show/Hide Toggle -->
                            <div
                                class="field-3d-layer rounded-2xl transition-all duration-300"
                                :class="{ 'field-3d-active-indigo': focusedField === 'password' }"
                            >
                                <div class="flex items-center justify-between">
                                    <InputLabel for="password" value="Kata Sandi" class="text-xs font-bold" />
                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-[11px] text-blue-600 hover:text-blue-700 dark:text-blue-400 font-semibold transition"
                                    >
                                        Lupa kata sandi?
                                    </Link>
                                </div>
                                <div class="relative mt-1">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none transition-colors duration-200"
                                        :class="focusedField === 'password' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <TextInput
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="pl-9 pr-10 block w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition-shadow"
                                        v-model="form.password"
                                        @focus="focusedField = 'password'"
                                        @blur="focusedField = null"
                                        required
                                        autocomplete="current-password"
                                        placeholder="••••••••"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition min-w-[40px]"
                                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    >
                                        <!-- Eye / Eye Off Icon -->
                                        <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg v-else class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-1.5 text-xs" :message="form.errors.password" />
                            </div>

                            <!-- Remember Me -->
                            <div class="flex items-center justify-between pt-1 layer-z-15">
                                <label class="flex items-center cursor-pointer">
                                    <Checkbox
                                        name="remember"
                                        v-model:checked="form.remember"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-600"
                                    />
                                    <span class="ms-2 text-xs text-slate-600 dark:text-slate-400 select-none">
                                        Ingat saya di perangkat ini
                                    </span>
                                </label>
                            </div>

                            <!-- 3D Tactile Submit Button -->
                            <div class="pt-2 layer-z-25">
                                <PrimaryButton
                                    class="btn-3d-push w-full justify-center py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-xs font-bold text-white shadow-lg shadow-blue-700/25 hover:shadow-blue-700/35 transition disabled:opacity-50"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="flex items-center gap-1.5">
                                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Memproses Masuk...
                                    </span>
                                    <span v-else>Masuk ke SILAS</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Card Footer: Register & Quick Document Verification Link -->
                    <div class="relative z-10 mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 space-y-3 layer-z-15">
                        <p class="text-xs text-center text-slate-500">
                            Belum memiliki akun mahasiswa?
                            <Link
                                :href="route('register')"
                                class="font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 underline ms-1"
                            >
                                Daftar Akun Mahasiswa
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
        </div>
    </GuestLayout>
</template>

<style scoped>
.bento-perspective {
    perspective: 900px;
}

.bento-card {
    transform-style: preserve-3d;
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s ease, border-color 0.25s ease, background-color 0.25s ease;
}

.bento-card:hover {
    transform: translateY(-4px) rotateX(3deg) rotateY(-2deg);
    box-shadow: 0 14px 28px -10px rgba(15, 23, 42, 0.08);
}

.login-perspective-wrapper {
    perspective: 1100px;
}

.login-3d-card {
    transform-style: preserve-3d;
    transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease, border-color 0.3s ease;
    will-change: transform;
}

.preserve-3d {
    transform-style: preserve-3d;
}

/* 3D Z-Axis Depth Layers */
.layer-z-15 {
    transform: translateZ(14px);
}
.layer-z-20 {
    transform: translateZ(20px);
}
.layer-z-25 {
    transform: translateZ(26px);
}
.layer-z-30 {
    transform: translateZ(32px);
}

/* Interactive 3D Input Pop on Focus */
.field-3d-layer {
    transform: translateZ(12px);
    transform-style: preserve-3d;
}
.field-3d-active {
    transform: translateZ(28px) scale(1.015);
    padding: 10px 12px;
    margin: -10px -12px;
    background: rgba(239, 246, 255, 0.55);
    box-shadow: 0 14px 28px -10px rgba(37, 99, 235, 0.18);
}
:global(.dark) .field-3d-active,
.dark .field-3d-active {
    background: rgba(30, 58, 138, 0.25) !important;
    box-shadow: 0 14px 28px -10px rgba(30, 58, 138, 0.35) !important;
}

.field-3d-active-indigo {
    transform: translateZ(28px) scale(1.015);
    padding: 10px 12px;
    margin: -10px -12px;
    background: rgba(238, 242, 255, 0.6);
    box-shadow: 0 14px 28px -10px rgba(79, 70, 229, 0.2);
}
:global(.dark) .field-3d-active-indigo,
.dark .field-3d-active-indigo {
    background: rgba(49, 46, 129, 0.3) !important;
    box-shadow: 0 14px 28px -10px rgba(49, 46, 129, 0.4) !important;
}

/* 3D Tactile Button */
.btn-3d-push {
    transform: translateZ(24px);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.btn-3d-push:hover:not(:disabled) {
    transform: translateZ(34px) translateY(-2px);
}
.btn-3d-push:active:not(:disabled) {
    transform: translateZ(14px) translateY(1px);
}

/* ============================================================
   3D REACTIVE CYBER GUARDIAN / LOCK CORE IN FORM HEADER
   ============================================================ */
.guardian-stage {
    width: 68px;
    height: 68px;
    perspective: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.guardian-rig {
    position: relative;
    width: 56px;
    height: 56px;
    transform-style: preserve-3d;
    transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    animation: guardianFloat 3.6s ease-in-out infinite;
}

@keyframes guardianFloat {
    0%, 100% { translate: 0 0; }
    50% { translate: 0 -4px; }
}

.guardian-ring {
    position: absolute;
    inset: -4px;
    border-radius: 9999px;
    pointer-events: none;
    transform-style: preserve-3d;
}

.ring-outer {
    border: 1.5px dashed rgba(59, 130, 246, 0.4);
    transform: translateZ(-6px) rotateX(62deg);
    animation: spinRing 9s linear infinite;
}

.ring-inner {
    inset: 1px;
    border: 1.5px solid rgba(99, 102, 241, 0.28);
    transform: translateZ(4px) rotateY(55deg);
    animation: spinRingReverse 7s linear infinite;
}

@keyframes spinRing {
    from { transform: translateZ(-6px) rotateX(62deg) rotateZ(0deg); }
    to { transform: translateZ(-6px) rotateX(62deg) rotateZ(360deg); }
}
@keyframes spinRingReverse {
    from { transform: translateZ(4px) rotateY(55deg) rotateZ(360deg); }
    to { transform: translateZ(4px) rotateY(55deg) rotateZ(0deg); }
}

/* 3D Lock Shackle Arch */
.guardian-shackle {
    position: absolute;
    top: -7px;
    left: 50%;
    width: 24px;
    height: 20px;
    margin-left: -12px;
    border: 3.5px solid #64748b;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    transform: translateZ(8px) translateY(0);
    transition: transform 0.32s cubic-bezier(0.34, 1.56, 0.64, 1), border-color 0.25s ease;
}

/* Shackle lifts when typing username or ready, snaps down when typing password */
.guardian-rig.is-typing-user .guardian-shackle {
    transform: translateZ(8px) translateY(-4px) rotateZ(-8deg);
    border-color: #3b82f6;
}
.guardian-rig.is-typing-pass .guardian-shackle {
    transform: translateZ(10px) translateY(3px);
    border-color: #6366f1;
}
.guardian-rig.is-peeking-pass .guardian-shackle {
    transform: translateZ(10px) translateY(-2px);
    border-color: #06b6d4;
}
.guardian-rig.is-ready .guardian-shackle {
    border-color: #10b981;
}

/* 3D Core Cube/Sphere Body */
.guardian-body {
    position: absolute;
    inset: 7px 6px 5px 6px;
    border-radius: 14px;
    background: linear-gradient(145deg, #1e293b, #0f172a);
    border: 1.5px solid rgba(148, 163, 184, 0.3);
    box-shadow:
        0 10px 20px -6px rgba(15, 23, 42, 0.45),
        inset 0 2px 4px rgba(255, 255, 255, 0.15);
    transform: translateZ(14px);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.guardian-rig.is-typing-user .guardian-body {
    border-color: rgba(59, 130, 246, 0.8);
    box-shadow: 0 10px 22px -5px rgba(37, 99, 235, 0.45);
}
.guardian-rig.is-typing-pass .guardian-body {
    border-color: rgba(99, 102, 241, 0.9);
    box-shadow: 0 10px 22px -5px rgba(79, 70, 229, 0.5);
}
.guardian-rig.is-ready .guardian-body {
    border-color: rgba(16, 185, 129, 0.85);
    box-shadow: 0 10px 22px -5px rgba(16, 185, 129, 0.45);
}

/* Cyber Visor Eyes (tracks username input & closes on password!) */
.guardian-visor {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 34px;
    height: 18px;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.92);
    border: 1px solid rgba(56, 189, 248, 0.35);
    transition: all 0.28s ease;
    position: relative;
    z-index: 2;
}

.guardian-rig.is-typing-pass .guardian-visor,
.guardian-rig.is-peeking-pass .guardian-visor {
    border-color: rgba(129, 140, 248, 0.55);
    background: rgba(15, 23, 42, 0.96);
}

.visor-eye {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
    background: #38bdf8;
    box-shadow: 0 0 8px #38bdf8;
    transform: translate(var(--eye-x, 0px), var(--eye-y, 0px));
    transition:
        width 0.25s cubic-bezier(0.22, 1, 0.36, 1),
        height 0.25s cubic-bezier(0.22, 1, 0.36, 1),
        border-radius 0.25s ease,
        background-color 0.25s ease,
        border 0.25s ease,
        box-shadow 0.25s ease,
        transform 0.18s ease;
    animation: eyeBlink 4s infinite;
}

.guardian-rig.is-typing-user .visor-eye {
    width: 7px;
    height: 8.5px;
    background: #60a5fa;
    box-shadow: 0 0 10px #60a5fa;
}

.guardian-rig.is-ready .visor-eye {
    background: #34d399;
    box-shadow: 0 0 10px #34d399;
}

/* CLOSED EYES when clicking Password field (curved sleeping eyelid arc ︶ ︶ centered in visor) */
.guardian-rig.is-typing-pass .visor-eye {
    animation: none;
    width: 9px;
    height: 5px;
    background: transparent;
    border-bottom: 2.5px solid #818cf8;
    border-left: 1px solid transparent;
    border-right: 1px solid transparent;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 5px rgba(129, 140, 248, 0.45);
    transform: translateY(-2px);
}

/* PEEKING EYE when Show Password is active (Left eye stays closed ︶, Right eye opens ●) */
.guardian-rig.is-peeking-pass .visor-eye.eye-left {
    animation: none;
    width: 9px;
    height: 5px;
    background: transparent;
    border-bottom: 2.5px solid #38bdf8;
    border-left: 1px solid transparent;
    border-right: 1px solid transparent;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 5px rgba(56, 189, 248, 0.45);
    transform: translateY(-2px);
}

.guardian-rig.is-peeking-pass .visor-eye.eye-right {
    width: 7px;
    height: 7px;
    background: #38bdf8;
    box-shadow: 0 0 10px #38bdf8;
    transform: translateY(0px);
}

@keyframes eyeBlink {
    0%, 92%, 100% { scale: 1 1; }
    96% { scale: 1 0.15; }
}

/* Spin in 3D when submitting */
.guardian-rig.is-submitting {
    animation: submitSpin3D 0.9s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}
@keyframes submitSpin3D {
    0% { transform: rotateY(0deg) rotateX(10deg); }
    100% { transform: rotateY(360deg) rotateX(10deg); }
}

.guardian-shadow {
    position: absolute;
    bottom: -6px;
    left: 15%;
    right: 15%;
    height: 6px;
    border-radius: 9999px;
    background: radial-gradient(closest-side, rgba(37, 99, 235, 0.35), transparent);
    transform: rotateX(75deg) translateZ(-10px);
}
</style>
