<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    breadcrumbs: {
        type: Array,
        default: null,
    },
    showBreadcrumbs: {
        type: Boolean,
        default: true,
    },
});

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
            <nav class="border-b border-slate-200/80 bg-white/90 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/90 sticky top-0 z-40">
                <!-- Primary Navigation Menu -->
                <div class="w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center gap-4">
                        <div class="flex items-center space-x-3">
                            <!-- Logo Brand Island -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="$page.props.auth.user?.role === 'mahasiswa' ? route('mahasiswa.dashboard') : route('dashboard')"
                                    class="flex items-center gap-2.5 px-3 py-1.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition group h-11"
                                >
                                    <img
                                        src="/images/logos/universitas-subang.png"
                                        alt="Universitas Subang"
                                        class="h-7 w-auto object-contain drop-shadow-xs group-hover:scale-105 transition-transform"
                                    />
                                    <div class="flex flex-col text-left">
                                        <span class="font-extrabold text-xs sm:text-sm tracking-tight text-slate-900 dark:text-white leading-tight">
                                            S I L A S
                                        </span>
                                        <span class="text-[12px] font-extrabold tracking-wider uppercase text-amber-600 dark:text-amber-400 leading-none mt-0.5">
                                            FASILKOM UNSUB
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links (Matching Pill Island) -->
                            <div class="hidden sm:flex items-center p-1 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 gap-1 sm:ms-6 h-11">
                                <!-- Mahasiswa Navigation -->
                                <template v-if="$page.props.auth.user?.role === 'mahasiswa'">
                                    <NavLink
                                        :href="route('mahasiswa.dashboard')"
                                        :active="route().current('mahasiswa.dashboard')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span>Dashboard</span>
                                    </NavLink>
                                    <NavLink
                                        :href="route('mahasiswa.letters.create')"
                                        :active="route().current('mahasiswa.letters.create')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Pengajuan Surat</span>
                                    </NavLink>
                                    <NavLink
                                        :href="route('mahasiswa.letters.index')"
                                        :active="route().current('mahasiswa.letters.index')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span>Riwayat Pengajuan</span>
                                    </NavLink>
                                </template>

                                <!-- Admin / Pejabat Navigation -->
                                <template v-else>
                                    <NavLink
                                        :href="route('dashboard')"
                                        :active="route().current('dashboard')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span>Dashboard</span>
                                    </NavLink>
                                    <NavLink
                                        :href="route('incoming-letters.index')"
                                        :active="route().current('incoming-letters.*')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <span>Surat Masuk</span>
                                    </NavLink>

                                    <!-- Menu Tersendiri: Permohonan Surat Keluar -->
                                    <NavLink
                                        :href="route('letters.index')"
                                        :active="route().current('letters.index') || route().current('letters.show') || route().current('letters.create') || route().current('letters.edit')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>Surat Keluar</span>
                                    </NavLink>

                                    <!-- Dropdown Studio Generator (Studio Surat, Studio SK, Studio MoU, Upload Dokumen) -->
                                    <div class="relative">
                                        <Dropdown align="left" width="56">
                                            <template #trigger>
                                                <button
                                                    type="button"
                                                    :class="[
                                                        'inline-flex items-center gap-2 px-3.5 py-1.5 h-9 rounded-xl text-xs sm:text-sm transition-all duration-200 select-none',
                                                        route().current('letters.live-studio') || route().current('letters.sk-studio') || route().current('letters.mou-studio') || route().current('documents.*')
                                                            ? 'bg-blue-600 text-white shadow-xs shadow-blue-900/20 font-semibold'
                                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-700/60 font-medium'
                                                    ]"
                                                >
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                    </svg>
                                                    <span>Studio Generator</span>
                                                    <svg
                                                        :class="[
                                                            'w-3.5 h-3.5 ms-0.5 transition-transform duration-200',
                                                            route().current('letters.live-studio') || route().current('letters.sk-studio') || route().current('letters.mou-studio') || route().current('documents.*') ? 'text-white/80' : 'text-slate-400'
                                                        ]"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </template>
                                            <template #content>
                                                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                                    Studio & Generator Surat
                                                </div>
                                                <DropdownLink :href="route('letters.live-studio')" :active="route().current('letters.live-studio')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Buat Surat
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('letters.sk-studio')" :active="route().current('letters.sk-studio')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <div class="flex flex-col">
                                                            <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                                                <span>Buat SK</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('letters.mou-studio')" :active="route().current('letters.mou-studio')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        <div class="flex flex-col">
                                                            <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                                                <span>Buat MOU</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('documents.index')" :active="route().current('documents.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Upload Dokumen
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                            </template>
                                        </Dropdown>
                                    </div>

                                    <!-- Menu Arsip Dokumen & Surat -->
                                    <NavLink
                                        :href="route('archives.index')"
                                        :active="route().current('archives.*')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                        <span>Arsip</span>
                                    </NavLink>

                                    <NavLink
                                        :href="route('verify.index')"
                                        :active="route().current('verify.*') || route().current('documents.verify')"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Verifikasi Surat</span>
                                    </NavLink>

                                    <!-- Setting Dropdown Menu (Admin) -->
                                    <div class="relative">
                                        <Dropdown align="right" width="48">
                                            <template #trigger>
                                                <button
                                                    type="button"
                                                    :class="[
                                                        'inline-flex items-center gap-2 px-3.5 py-1.5 h-9 rounded-xl text-xs sm:text-sm transition-all duration-200 select-none',
                                                        route().current('settings.*') || route().current('letter-templates.*') || route().current('certificates.*')
                                                            ? 'bg-blue-600 text-white shadow-xs shadow-blue-900/20 font-semibold'
                                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-700/60 font-medium'
                                                    ]"
                                                >
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <span>Pengaturan</span>
                                                    <svg
                                                        :class="[
                                                            'w-3.5 h-3.5 ms-0.5 transition-transform duration-200',
                                                            route().current('settings.*') || route().current('letter-templates.*') || route().current('certificates.*') ? 'text-white/80' : 'text-slate-400'
                                                        ]"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </template>
                                            <template #content>
                                                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                                    Menu Pengaturan
                                                </div>
                                                <DropdownLink :href="route('letter-templates.index')" :active="route().current('letter-templates.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Kelola Template
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('settings.classifications.index')" :active="route().current('settings.classifications.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Klasifikasi Surat
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('settings.signers.index')" :active="route().current('settings.signers.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Pejabat Penandatangan
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('settings.lecturers.index')" :active="route().current('settings.lecturers.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Data Dosen & Pengajar
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('settings.courses.index')" :active="route().current('settings.courses.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Data Mata Kuliah
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('settings.archive-categories.index')" :active="route().current('settings.archive-categories.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Kategori Arsip
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                                <DropdownLink :href="route('certificates.index')" :active="route().current('certificates.*')">
                                                    <div class="flex items-center gap-2.5 py-0.5">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                        </svg>
                                                        <div class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                            Sertifikat Digital
                                                        </div>
                                                    </div>
                                                </DropdownLink>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right Actions & Profile Island -->
                        <div class="hidden sm:flex sm:items-center sm:gap-2.5">
                            <!-- Notification Bell (Di sebelah icon dark/light mode) -->
                            <NotificationBell />

                            <!-- Theme Toggle (Di samping nama role user) -->
                            <ThemeToggle />

                            <div class="relative">
                                <Dropdown align="right" width="64" content-classes="py-1 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700/80 overflow-hidden divide-y divide-slate-100 dark:divide-slate-700/60">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-700/60 transition h-11"
                                        >
                                            <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-2xs overflow-hidden shrink-0">
                                                <img
                                                    v-if="$page.props.auth.user?.avatar_url"
                                                    :src="$page.props.auth.user.avatar_url"
                                                    :alt="$page.props.auth.user.name"
                                                    class="w-full h-full object-cover"
                                                />
                                                <span v-else>
                                                    {{ $page.props.auth.user.name.charAt(0) }}
                                                </span>
                                            </div>
                                            <div class="flex flex-col text-left">
                                                <span class="max-w-[130px] truncate leading-tight font-bold text-xs text-slate-800 dark:text-slate-100">
                                                    {{ $page.props.auth.user.name }}
                                                </span>
                                                <span
                                                    :class="[
                                                        'text-[9px] font-bold uppercase tracking-wider leading-none mt-0.5',
                                                        $page.props.auth.user?.role === 'mahasiswa'
                                                            ? 'text-blue-600 dark:text-blue-400'
                                                            : 'text-emerald-700 dark:text-emerald-400'
                                                    ]"
                                                >
                                                    {{ $page.props.auth.user?.role === 'mahasiswa' ? 'Mahasiswa' : 'Admin / Pejabat' }}
                                                </span>
                                            </div>
                                            <svg class="h-3.5 w-3.5 text-slate-400 ms-0.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <!-- Header User Card -->
                                        <div class="p-3.5 bg-gradient-to-br from-slate-50 to-blue-50/40 dark:from-slate-800 dark:to-slate-900/60">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0 ring-2 ring-white dark:ring-slate-700 overflow-hidden">
                                                    <img
                                                        v-if="$page.props.auth.user?.avatar_url"
                                                        :src="$page.props.auth.user.avatar_url"
                                                        :alt="$page.props.auth.user.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <span v-else>
                                                        {{ $page.props.auth.user.name.charAt(0) }}
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate leading-snug">
                                                        {{ $page.props.auth.user.name }}
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate leading-snug">
                                                        {{ $page.props.auth.user.email }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-2.5 pt-2.5 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between gap-2">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase',
                                                        $page.props.auth.user?.role === 'mahasiswa'
                                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200/50 dark:border-blue-800/40'
                                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-800/40'
                                                    ]"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="$page.props.auth.user?.role === 'mahasiswa' ? 'bg-blue-500' : 'bg-emerald-500'"></span>
                                                    {{ $page.props.auth.user?.role === 'mahasiswa' ? 'Mahasiswa' : 'Admin / Pejabat' }}
                                                </span>
                                                <span v-if="$page.props.auth.user?.npm" class="text-[11px] font-mono font-medium text-slate-600 dark:text-slate-300 bg-white/80 dark:bg-slate-950/40 px-2 py-0.5 rounded-md border border-slate-200/50 dark:border-slate-800/50">
                                                    {{ $page.props.auth.user.npm }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Menu Links -->
                                        <div class="p-1 space-y-0.5">
                                            <DropdownLink :href="route('profile.edit')" class="rounded-xl transition-colors hover:bg-slate-100 dark:hover:bg-slate-700/60">
                                                <div class="flex items-center gap-2.5 py-1 px-1">
                                                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-700/70 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex flex-col text-left">
                                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Profil Saya</span>
                                                        <span class="text-[10px] text-slate-400">Pengaturan akun & sandi</span>
                                                    </div>
                                                </div>
                                            </DropdownLink>
                                        </div>

                                        <div class="p-1">
                                            <DropdownLink :href="route('logout')" method="post" as="button" class="w-full rounded-xl transition-colors hover:bg-rose-50 dark:hover:bg-rose-950/40 group">
                                                <div class="flex items-center gap-2.5 py-1 px-1 text-rose-600 dark:text-rose-400">
                                                    <div class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 group-hover:bg-rose-100 dark:group-hover:bg-rose-900/50 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex flex-col text-left">
                                                        <span class="text-xs font-semibold">Keluar</span>
                                                        <span class="text-[10px] text-rose-400/80">Akhiri sesi saat ini</span>
                                                    </div>
                                                </div>
                                            </DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Mobile Hamburger & Actions -->
                        <div class="-me-2 flex items-center gap-2 sm:hidden">
                            <NotificationBell />
                            <ThemeToggle />
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <div class="space-y-1 px-4 py-3">
                        <!-- Mobile Mahasiswa Nav -->
                        <template v-if="$page.props.auth.user?.role === 'mahasiswa'">
                            <ResponsiveNavLink :href="route('mahasiswa.dashboard')" :active="route().current('mahasiswa.dashboard')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('mahasiswa.letters.create')" :active="route().current('mahasiswa.letters.create')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Pengajuan Surat</span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('mahasiswa.letters.index')" :active="route().current('mahasiswa.letters.index')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span>Riwayat Pengajuan</span>
                            </ResponsiveNavLink>
                        </template>

                        <!-- Mobile Admin Nav -->
                        <template v-else>
                            <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('incoming-letters.index')" :active="route().current('incoming-letters.*')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <span>Surat Masuk</span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('archives.index')" :active="route().current('archives.*')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                <span>Arsip Surat & Dokumen</span>
                            </ResponsiveNavLink>

                            <!-- Mobile Group: Permohonan Surat Keluar & Studio Generator -->
                            <div class="pt-2 pb-1 border-t border-slate-200/60 dark:border-slate-800/60">
                                <ResponsiveNavLink :href="route('letters.index')" :active="route().current('letters.index') || route().current('letters.show') || route().current('letters.create') || route().current('letters.edit')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Permohonan Surat Keluar</span>
                                </ResponsiveNavLink>

                                <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-2 mb-1">
                                    Studio Generator
                                </div>
                                <ResponsiveNavLink :href="route('letters.live-studio')" :active="route().current('letters.live-studio')">
                                    <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Buat Surat</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('letters.sk-studio')" :active="route().current('letters.sk-studio')">
                                    <svg class="w-4 h-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="flex items-center gap-1.5">
                                        <span>Buat SK</span>
                                    </div>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('letters.mou-studio')" :active="route().current('letters.mou-studio')">
                                    <svg class="w-4 h-4 shrink-0 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <div class="flex items-center gap-1.5">
                                        <span>Buat MOU</span>
                                    </div>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('documents.index')" :active="route().current('documents.*')">
                                    <svg class="w-4 h-4 shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>Upload Dokumen</span>
                                </ResponsiveNavLink>
                            </div>

                            <ResponsiveNavLink :href="route('verify.index')" :active="route().current('verify.*') || route().current('documents.verify')">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Portal Verifikasi</span>
                            </ResponsiveNavLink>

                            <div class="pt-2 pb-1 border-t border-slate-200 dark:border-slate-800">
                                <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Setting
                                </div>
                                <ResponsiveNavLink :href="route('letter-templates.index')" :active="route().current('letter-templates.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Template Surat</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('settings.classifications.index')" :active="route().current('settings.classifications.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span>Klasifikasi Surat</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('settings.signers.index')" :active="route().current('settings.signers.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Pejabat Penandatangan</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('settings.lecturers.index')" :active="route().current('settings.lecturers.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span>Data Dosen & Pengajar</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('settings.courses.index')" :active="route().current('settings.courses.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>Data Mata Kuliah</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('settings.archive-categories.index')" :active="route().current('settings.archive-categories.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>Kategori Arsip</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('certificates.index')" :active="route().current('certificates.*')">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Sertifikat Digital (PKI)</span>
                                </ResponsiveNavLink>
                            </div>
                        </template>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-800 px-4 py-3">
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $page.props.auth.user.name }}</div>
                        <div class="text-xs text-slate-500">{{ $page.props.auth.user.email }}</div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profil</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Keluar</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Toast Notification (Floating Bottom-Right) -->
            <ToastNotification />

            <!-- Breadcrumbs Navigation Bar -->
            <slot name="breadcrumbs">
                <Breadcrumbs v-if="showBreadcrumbs" :items="breadcrumbs" />
            </slot>

            <!-- Page Heading -->
            <header class="bg-transparent" v-if="$slots.header">
                <div class="w-full px-4 pt-6 pb-2 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
