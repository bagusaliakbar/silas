<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    /**
     * Optional custom breadcrumbs array:
     * Array of { label: string, href?: string, icon?: string, current?: boolean }
     */
    items: {
        type: Array,
        default: null,
    },
});

const page = usePage();

const user = computed(() => page.props.auth?.user);
const isMahasiswa = computed(() => user.value?.role === 'mahasiswa');
const homeRoute = computed(() => isMahasiswa.value ? route('mahasiswa.dashboard') : route('dashboard'));

const resolvedItems = computed(() => {
    // If explicit items provided, use them
    if (Array.isArray(props.items) && props.items.length > 0) {
        return props.items;
    }

    const currentRoute = route().current() || '';
    const component = page.component || '';
    const letter = page.props.letter;
    const letterType = page.props.letterType;
    const document = page.props.document;

    const crumbs = [];

    // Base Home
    crumbs.push({
        label: 'Beranda',
        href: homeRoute.value,
        isHome: true,
        current: currentRoute === 'dashboard' || currentRoute === 'mahasiswa.dashboard',
    });

    // 1. Mahasiswa Routes
    if (currentRoute === 'mahasiswa.letters.create' || component === 'Mahasiswa/Pengajuan') {
        crumbs.push({
            label: 'Pengajuan Surat',
            current: true,
        });
    } else if (currentRoute === 'mahasiswa.letters.index' || component === 'Mahasiswa/Riwayat') {
        crumbs.push({
            label: 'Riwayat Pengajuan',
            current: true,
        });
    } else if (currentRoute === 'mahasiswa.letters.show') {
        crumbs.push({
            label: 'Riwayat Pengajuan',
            href: route('mahasiswa.letters.index'),
        });
        crumbs.push({
            label: letter?.letter_type?.name ? `Detail: ${letter.letter_type.name}` : 'Detail Permohonan',
            current: true,
        });
    } else if (currentRoute === 'mahasiswa.letters.edit' || component === 'Mahasiswa/Edit') {
        crumbs.push({
            label: 'Riwayat Pengajuan',
            href: route('mahasiswa.letters.index'),
        });
        crumbs.push({
            label: 'Revisi & Perbaikan Surat',
            current: true,
        });
    }

    // 2. Surat Masuk (Admin / Pejabat)
    else if (currentRoute === 'incoming-letters.index') {
        crumbs.push({
            label: 'Surat Masuk',
            current: true,
        });
    }

    // 3. Surat Keluar (Admin / Pejabat)
    else if (currentRoute === 'letters.index') {
        crumbs.push({
            label: 'Surat Keluar',
            current: true,
        });
    } else if (currentRoute === 'letters.create') {
        crumbs.push({
            label: 'Surat Keluar',
            href: route('letters.index'),
        });
        crumbs.push({
            label: 'Buat Surat Baru',
            current: true,
        });
    } else if (currentRoute === 'letters.show') {
        crumbs.push({
            label: 'Surat Keluar',
            href: route('letters.index'),
        });
        crumbs.push({
            label: letter?.student_name ? `Atas Nama : ${letter.student_name}` : 'Detail Surat & TTE',
            current: true,
        });
    }

    // 3. Dokumen & TTE Berkas
    else if (currentRoute === 'documents.index') {
        crumbs.push({
            label: 'Tanda Tangan Dokumen',
            current: true,
        });
    } else if (currentRoute === 'documents.create') {
        crumbs.push({
            label: 'Tanda Tangan Dokumen',
            href: route('documents.index'),
        });
        crumbs.push({
            label: 'Unggah Berkas Baru',
            current: true,
        });
    } else if (currentRoute === 'documents.studio') {
        crumbs.push({
            label: 'Tanda Tangan Dokumen',
            href: route('documents.index'),
        });
        crumbs.push({
            label: document?.title ? `Dokumen : ${document.title}` : 'Studio Penandatanganan',
            current: true,
        });
    }

    // 4. Sertifikat Digital (PKI) - Di bawah Menu Setting
    else if (currentRoute === 'certificates.index') {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Sertifikat Digital (PKI)',
            current: true,
        });
    }

    // 5. Letter Templates Management
    else if (currentRoute === 'letter-templates.index') {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Kelola Template',
            current: true,
        });
    } else if (currentRoute === 'letter-templates.edit') {
        crumbs.push({
            label: 'Kelola Template',
            href: route('letter-templates.index'),
        });
        crumbs.push({
            label: letterType?.name ? `Editor: ${letterType.name}` : 'Editor Template',
            current: true,
        });
    }

    // 6. Settings: Pejabat Penandatangan, Klasifikasi, Dosen, Mata Kuliah, & Kategori Arsip
    else if (currentRoute.startsWith('settings.signers')) {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Pejabat Penandatangan',
            current: true,
        });
    } else if (currentRoute.startsWith('settings.classifications')) {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Klasifikasi Surat',
            current: true,
        });
    } else if (currentRoute.startsWith('settings.lecturers')) {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Data Dosen & Pengajar',
            current: true,
        });
    } else if (currentRoute.startsWith('settings.courses')) {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Data Mata Kuliah',
            current: true,
        });
    } else if (currentRoute.startsWith('settings.archive-categories')) {
        crumbs.push({
            label: 'Pengaturan',
        });
        crumbs.push({
            label: 'Kategori Arsip',
            current: true,
        });
    }

    // 7. Profile
    else if (currentRoute === 'profile.edit') {
        crumbs.push({
            label: 'Profil Saya',
            current: true,
        });
    }

    // 8. Verification Portal
    else if (currentRoute === 'verify.index') {
        crumbs.push({
            label: 'Portal Verifikasi Dokumen',
            current: true,
        });
    } else if (currentRoute === 'documents.verify') {
        crumbs.push({
            label: 'Portal Verifikasi',
            href: route('verify.index'),
        });
        crumbs.push({
            label: 'Hasil Validasi Keabsahan',
            current: true,
        });
    }

    // 9. Notifications
    else if (currentRoute.startsWith('notifications')) {
        crumbs.push({
            label: 'Notifikasi & Pemberitahuan',
            current: true,
        });
    }

    return crumbs;
});
</script>

<template>
    <!-- Accessible Breadcrumb Island Strip -->
    <nav aria-label="Breadcrumb" class="w-full px-4 sm:px-6 lg:px-8 pt-3 pb-1">
        <div class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 backdrop-blur-md shadow-2xs text-xs text-slate-500 overflow-x-auto scrollbar-none max-w-full">
            <template v-for="(item, index) in resolvedItems" :key="index">
                <!-- Delimiter between items -->
                <svg
                    v-if="index > 0"
                    class="w-3 h-3 text-slate-300 dark:text-slate-600 shrink-0 mx-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>

                <!-- Current Active Page (Last Item) -->
                <span
                    v-if="item.current || index === resolvedItems.length - 1"
                    class="font-bold text-blue-700 dark:text-blue-400 flex items-center gap-1.5 truncate max-w-[200px] sm:max-w-md"
                    aria-current="page"
                >
                    <!-- Home icon for root -->
                    <svg
                        v-if="item.isHome"
                        class="w-3.5 h-3.5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>{{ item.label }}</span>
                </span>

                <!-- Navigable Link Item -->
                <Link
                    v-else-if="item.href"
                    :href="item.href"
                    class="font-medium text-slate-600 dark:text-slate-300 hover:text-blue-700 dark:hover:text-blue-400 transition flex items-center gap-1.5 shrink-0"
                >
                    <svg
                        v-if="item.isHome"
                        class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>{{ item.label }}</span>
                </Link>

                <!-- Non-navigable intermediate label (e.g. "Pengaturan") -->
                <span
                    v-else
                    class="font-medium text-slate-400 dark:text-slate-500 shrink-0"
                >
                    {{ item.label }}
                </span>
            </template>
        </div>
    </nav>
</template>

