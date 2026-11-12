<script setup>
import ThemeToggle from '@/Components/ThemeToggle.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col justify-between selection:bg-blue-600 selection:text-white transition-colors duration-200">
        <!-- TOP NAVIGATION BAR -->
        <header class="border-b border-slate-200/80 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-40">
            <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                <!-- Brand & Logo -->
                <Link :href="route('login')" class="flex items-center gap-3 group">
                    <img
                        src="/images/logos/universitas-subang.png"
                        alt="Logo Universitas Subang"
                        class="h-10 w-auto object-contain drop-shadow-xs group-hover:scale-105 transition-transform"
                    />
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-sm sm:text-base tracking-tight text-slate-900 dark:text-white leading-tight">
                            S I L A S
                        </span>
                        <span class="text-[12px] font-extrabold tracking-wider uppercase text-amber-600 dark:text-amber-400 leading-none mt-0.5">
                            FASILKOM UNSUB
                        </span>
                    </div>
                </Link>

                <!-- Navigation Action Buttons -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <ThemeToggle />
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <slot />
        </main>

        <!-- Toast Notification (Floating Bottom-Right) -->
        <ToastNotification />

        <!-- FOOTER -->
        <footer class="border-t border-slate-200/80 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 py-4 px-4 text-center">
            <div class="w-full px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                <div>
                    © 2026 <strong>Fakultas Ilmu Komputer Universitas Subang</strong> • 
                </div>
                <div class="flex items-center gap-3 text-slate-400">
                    <span>Sistem Informasi Layanan Surat</span>
                    <span>•</span>
                    <span>SILAS v1.0</span>
                </div>
            </div>
        </footer>
    </div>
</template>
