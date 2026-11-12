<script setup>
import { ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    courses: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
            total_sks: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: 'all',
            study_program: 'all',
        }),
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const prodiFilter = ref(props.filters.study_program || 'all');

const applyFilter = () => {
    router.get(
        route('settings.courses.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            study_program: prodiFilter.value !== 'all' ? prodiFilter.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

let searchTimeout = null;
const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilter();
    }, 400);
};

const resetFilter = () => {
    search.value = '';
    statusFilter.value = 'all';
    prodiFilter.value = 'all';
    applyFilter();
};

// Modal State Form Create/Edit
const showFormModal = ref(false);
const isEditing = ref(false);
const editingCourseId = ref(null);

const form = useForm({
    code: '',
    name: '',
    sks: 2,
    semester: 1,
    study_program: 'Sistem Informasi',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingCourseId.value = null;
    form.reset();
    form.clearErrors();
    form.sks = 2;
    form.semester = 1;
    form.study_program = 'Sistem Informasi';
    form.is_active = true;
    showFormModal.value = true;
};

const openEditModal = (course) => {
    isEditing.value = true;
    editingCourseId.value = course.id;
    form.clearErrors();
    form.code = course.code;
    form.name = course.name;
    form.sks = course.sks;
    form.semester = course.semester || 1;
    form.study_program = course.study_program || 'Sistem Informasi';
    form.description = course.description || '';
    form.is_active = Boolean(course.is_active);
    showFormModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('settings.courses.update', editingCourseId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('settings.courses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            },
        });
    }
};

// Toggle status active
const toggleStatus = (course) => {
    router.patch(
        route('settings.courses.toggle', course.id),
        {},
        {
            preserveScroll: true,
        }
    );
};

// Delete Confirmation Modal
const showDeleteModal = ref(false);
const courseToDelete = ref(null);

const confirmDelete = (course) => {
    courseToDelete.value = course;
    showDeleteModal.value = true;
};

const deleteCourse = () => {
    if (!courseToDelete.value) return;
    router.delete(route('settings.courses.destroy', courseToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            courseToDelete.value = null;
        },
    });
};

// Modal State Import CSV
const showImportModal = ref(false);
const importForm = useForm({
    file: null,
});

const openImportModal = () => {
    importForm.reset();
    importForm.clearErrors();
    showImportModal.value = true;
};

const submitImport = () => {
    if (!importForm.file) return;
    importForm.post(route('settings.courses.import'), {
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const handleExport = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
    if (prodiFilter.value !== 'all') params.append('study_program', prodiFilter.value);

    const url = route('settings.courses.export') + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
};
</script>

<template>
    <Head title="Daftar Mata Kuliah" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- CONTROLS & FILTER TOOLBAR (MATCHING TEMPLATE & KLASIFIKASI SURAT) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title & Actions -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Mata Kuliah</h3>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Tombol Export Excel -->
                            <button
                                type="button"
                                @click="handleExport"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-emerald-800 dark:text-emerald-200 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 shadow-2xs transition"
                                title="Unduh data mata kuliah dalam format Excel (.xlsx)"
                            >
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Export Excel (.xlsx)</span>
                            </button>

                            <!-- Tombol Import Excel -->
                            <button
                                type="button"
                                @click="openImportModal"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition"
                                title="Unggah berkas Excel (.xlsx / .xls) untuk mengimpor mata kuliah secara massal"
                            >
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>Import Excel</span>
                            </button>

                            <!-- Tombol Tambah Manual -->
                            <button
                                type="button"
                                @click="openCreateModal"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Mata Kuliah</span>
                            </button>
                        </div>
                    </div>

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
                                @input="handleSearchInput"
                                type="text"
                                placeholder="Cari berdasarkan Kode MK, Nama Mata Kuliah..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="search = ''; applyFilter()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Right Control Group: Status Filter -->
                        <div class="flex items-center gap-2.5 shrink-0 self-end md:self-auto">
                            <!-- Filter Status -->
                            <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs">
                                <button
                                    type="button"
                                    @click="statusFilter = 'all'; applyFilter()"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        statusFilter === 'all'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    type="button"
                                    @click="statusFilter = 'active'; applyFilter()"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        statusFilter === 'active'
                                            ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Aktif
                                </button>
                                <button
                                    type="button"
                                    @click="statusFilter = 'inactive'; applyFilter()"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        statusFilter === 'inactive'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Nonaktif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ courses.total || (courses.data ? courses.data.length : 0) }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ stats.total }}</span>
                        mata kuliah terdaftar
                    </div>
                    <div v-if="search || statusFilter !== 'all'">
                        <button
                            type="button"
                            @click="resetFilter"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

            <!-- TABLE -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-2xs overflow-hidden">
                <!-- DESKTOP TABLE VIEW -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Kode MK</th>
                                <th class="py-3.5 px-4">Nama Mata Kuliah</th>
                                <th class="py-3.5 px-4 text-center">SKS</th>
                                <th class="py-3.5 px-4">Semester</th>
                                <th class="py-3.5 px-4">Program Studi</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="(course, idx) in courses.data"
                                :key="course.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono">
                                    {{ (courses.current_page - 1) * courses.per_page + idx + 1 }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold px-2 py-1 rounded-md bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                                        {{ course.code }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm">
                                        {{ course.name }}
                                    </div>
                                    <div v-if="course.description" class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">
                                        {{ course.description }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg font-black bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs">
                                        {{ course.sks }} SKS
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-700 dark:text-slate-300 font-medium">
                                        {{ course.semester ? 'Semester ' + course.semester : '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-slate-700 dark:text-slate-300">
                                        {{ course.study_program || 'Sistem Informasi' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="toggleStatus(course)"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition shadow-2xs',
                                            course.is_active
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 hover:bg-emerald-200'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 hover:bg-slate-200'
                                        ]"
                                        :title="course.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="course.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        <span>{{ course.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(course)"
                                            class="w-8 h-8 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-blue-50 dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                            title="Ubah Data Mata Kuliah"
                                            aria-label="Ubah Data Mata Kuliah"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(course)"
                                            class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                            title="Hapus Mata Kuliah"
                                            aria-label="Hapus Mata Kuliah"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="courses.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-slate-700 dark:text-slate-300">Tidak ada data mata kuliah ditemukan</p>
                                    <p class="text-[11px] mt-0.5">Silakan tambahkan data mata kuliah baru menggunakan tombol di atas.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- MOBILE CARD VIEW -->
                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                    <div
                        v-for="course in courses.data"
                        :key="course.id"
                        class="p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono font-bold px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900 text-xs">
                                        {{ course.code }}
                                    </span>
                                </div>
                                <div class="font-bold text-slate-900 dark:text-white text-sm">
                                    {{ course.name }}
                                </div>
                                <div v-if="course.description" class="text-[11px] text-slate-400 mt-0.5 line-clamp-2">
                                    {{ course.description }}
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="toggleStatus(course)"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition shadow-2xs shrink-0',
                                    course.is_active
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 hover:bg-emerald-200'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-300 hover:bg-slate-200'
                                ]"
                                :title="course.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="course.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span>{{ course.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-950/50 rounded-xl p-2.5 space-y-1.5 text-xs border border-slate-100 dark:border-slate-800/60">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[11px]">
                                        {{ course.sks }} SKS
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ course.semester ? 'Semester ' + course.semester : '-' }}
                                    </span>
                                </div>
                                <span class="text-[11px] font-medium text-slate-600 dark:text-slate-400">
                                    {{ course.study_program || 'Sistem Informasi' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-1.5 pt-1">
                            <button
                                type="button"
                                @click="openEditModal(course)"
                                class="w-8 h-8 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-blue-50 dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                title="Ubah Data Mata Kuliah"
                                aria-label="Ubah Data Mata Kuliah"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="confirmDelete(course)"
                                class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                title="Hapus Mata Kuliah"
                                aria-label="Hapus Mata Kuliah"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="courses.data.length === 0" class="p-8 text-center text-slate-400">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <p class="font-bold text-slate-700 dark:text-slate-300">Tidak ada data mata kuliah ditemukan</p>
                        <p class="text-[11px] mt-0.5">Silakan tambahkan data mata kuliah baru menggunakan tombol di atas.</p>
                    </div>
                </div>

                <!-- PAGINATION -->
                <div
                    v-if="courses.links && courses.links.length > 3"
                    class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs"
                >
                    <div class="text-slate-500">
                        Menampilkan <span class="font-bold text-slate-800 dark:text-slate-200">{{ courses.from || 0 }}</span> sampai <span class="font-bold text-slate-800 dark:text-slate-200">{{ courses.to || 0 }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ courses.total }}</span> mata kuliah
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in courses.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-medium transition',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 text-slate-300 dark:text-slate-700 select-none"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- MODAL FORM CREATE / EDIT -->
        <Modal :show="showFormModal" @close="showFormModal = false" max-width="lg">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Ubah Data Mata Kuliah' : 'Tambah Mata Kuliah Baru' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Isi informasi kode mata kuliah, nama mata kuliah, dan bobot SKS.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- KODE MK -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kode Mata Kuliah <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.code"
                                placeholder="D1A12004 / SI-201"
                                class="w-full text-xs font-mono font-bold uppercase rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-blue-500 py-2.5"
                                required
                            />
                            <div v-if="form.errors.code" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.code }}
                            </div>
                        </div>

                        <!-- BOBOT SKS -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Bobot SKS <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                max="12"
                                v-model="form.sks"
                                placeholder="2, 3, 4"
                                class="w-full text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-blue-500 py-2.5"
                                required
                            />
                            <div v-if="form.errors.sks" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.sks }}
                            </div>
                        </div>
                    </div>

                    <!-- NAMA MATA KULIAH -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Mata Kuliah <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.name"
                            placeholder="Rekayasa Perangkat Lunak, Jaringan Komputer, dll."
                            class="w-full text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-blue-500 py-2.5"
                            required
                        />
                        <div v-if="form.errors.name" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- SEMESTER -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Semester (Kurikulum)
                            </label>
                            <input
                                type="number"
                                min="1"
                                max="14"
                                v-model="form.semester"
                                placeholder="Semester 1 s/d 8"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-blue-500 py-2.5"
                            />
                            <div v-if="form.errors.semester" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.semester }}
                            </div>
                        </div>

                        <!-- PROGRAM STUDI -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Program Studi
                            </label>
                            <select
                                v-model="form.study_program"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-blue-500 py-2.5"
                            >
                                <option value="Sistem Informasi">Sistem Informasi</option>
                                <option value="Teknik Informatika">Teknik Informatika</option>
                                <option value="Fakultas Ilmu Komputer">Fakultas Ilmu Komputer (Umum)</option>
                            </select>
                            <div v-if="form.errors.study_program" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.study_program }}
                            </div>
                        </div>
                    </div>

                    <!-- DESKRIPSI -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Deskripsi / Keterangan (Opsional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Catatan tambahan mengenai mata kuliah atau prasyarat..."
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-blue-500 py-2"
                        ></textarea>
                    </div>

                    <!-- STATUS AKTIF -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="course_is_active"
                            v-model="form.is_active"
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded"
                        />
                        <label for="course_is_active" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            Mata kuliah aktif (dapat dipilih dan digunakan pada pembuatan surat)
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showFormModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm shadow-blue-500/20 transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Mata Kuliah' : 'Simpan Mata Kuliah') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL IMPORT EXCEL -->
        <Modal :show="showImportModal" @close="showImportModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Import Data Mata Kuliah
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Unggah berkas Microsoft Excel (.xlsx / .xls) untuk menambahkan atau memperbarui data mata kuliah sekaligus.
                        </p>
                    </div>
                </div>

                <!-- Template Download Card -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            Unduh Template Excel (.xlsx)
                        </span>
                        <div class="flex items-center gap-2">
                            <a
                                :href="route('settings.courses.template')"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-700 underline"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Template Kosong</span>
                            </a>
                            <span class="text-slate-300">|</span>
                            <a
                                href="/examples/contoh_import_mata_kuliah.xlsx"
                                download="contoh_import_mata_kuliah.xlsx"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 underline"
                            >
                                <span>Contoh Lengkap</span>
                            </a>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Kolom header Excel: <strong>kode_mk</strong>, <strong>nama_mk</strong>, <strong>sks</strong>, <strong>semester</strong>, <strong>program_studi</strong>, <strong>keterangan</strong>.
                    </p>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4 pt-1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Pilih Berkas Excel (.xlsx, .xls, atau .csv, maks. 10MB)
                        </label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="(e) => importForm.file = e.target.files[0]"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950 dark:file:text-blue-300 cursor-pointer border border-slate-200 dark:border-slate-800 rounded-xl p-1"
                            required
                        />
                        <div v-if="importForm.errors.file" class="text-xs text-rose-500 mt-1">
                            {{ importForm.errors.file }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showImportModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing || !importForm.file"
                            class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span v-if="importForm.processing">Mengimpor...</span>
                            <span v-else>Mulai Import Excel</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL DELETE CONFIRMATION -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Hapus Mata Kuliah?
                </h3>
                <p class="text-xs text-slate-500">
                    Apakah Anda yakin ingin menghapus data mata kuliah <strong>{{ courseToDelete?.name }}</strong> ({{ courseToDelete?.code }})?
                </p>
                <div class="flex items-center justify-center gap-2 pt-3">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteCourse"
                        class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

