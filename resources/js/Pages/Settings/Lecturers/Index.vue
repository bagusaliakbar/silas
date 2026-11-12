<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    lecturers: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: 'all',
            functional_position: 'all',
        }),
    },
    functionalPositions: {
        type: Array,
        default: () => [],
    },
    studyPrograms: {
        type: Array,
        default: () => [],
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const positionFilter = ref(props.filters.functional_position || 'all');

const applyFilter = () => {
    router.get(
        route('settings.lecturers.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            functional_position: positionFilter.value !== 'all' ? positionFilter.value : undefined,
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
    positionFilter.value = 'all';
    applyFilter();
};

// Modal State Form Create/Edit
const showFormModal = ref(false);
const isEditing = ref(false);
const editingLecturerId = ref(null);

const form = useForm({
    name: '',
    nidn: '',
    nip: '',
    functional_position: '',
    academic_rank: '',
    study_program: 'Sistem Informasi',
    email: '',
    phone: '',
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingLecturerId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.study_program = 'Sistem Informasi';
    showFormModal.value = true;
};

const openEditModal = (lecturer) => {
    isEditing.value = true;
    editingLecturerId.value = lecturer.id;
    form.clearErrors();
    form.name = lecturer.name || '';
    form.nidn = lecturer.nidn || '';
    form.nip = lecturer.nip || '';
    form.functional_position = lecturer.functional_position || '';
    form.academic_rank = lecturer.academic_rank || '';
    form.study_program = lecturer.study_program || 'Sistem Informasi';
    form.email = lecturer.email || '';
    form.phone = lecturer.phone || '';
    form.is_active = Boolean(lecturer.is_active);
    showFormModal.value = true;
};

const closeFormModal = () => {
    showFormModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('settings.lecturers.update', editingLecturerId.value), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(route('settings.lecturers.store'), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    }
};

// Toggle status aktif
const toggleStatus = (lecturer) => {
    router.patch(
        route('settings.lecturers.toggle', lecturer.id),
        {},
        {
            preserveScroll: true,
        }
    );
};

// Modal Delete
const showDeleteModal = ref(false);
const lecturerToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (lecturer) => {
    lecturerToDelete.value = lecturer;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    lecturerToDelete.value = null;
    isDeleting.value = false;
};

const deleteLecturer = () => {
    if (!lecturerToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('settings.lecturers.destroy', lecturerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const getBadgePosition = (pos) => {
    switch (pos) {
        case 'Guru Besar':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'Lektor Kepala':
            return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
        case 'Lektor':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'Asisten Ahli':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        default:
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
};
</script>

<template>
    <Head title="Daftar Dosen & Pengajar" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- CONTROLS & FILTER TOOLBAR (MATCHING TEMPLATE & KLASIFIKASI SURAT) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Dosen</h3>
                        </div>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Dosen Baru</span>
                        </button>
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
                                placeholder="Cari nama dosen, NIDN, NIP, jabatan fungsional..."
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

                        <!-- Right Control Group: Jabatan Fungsional Dropdown & Status Filter Toggle -->
                        <div class="flex flex-wrap items-center gap-2.5 shrink-0 self-end md:self-auto">
                            <!-- Filter Jabatan -->
                            <select
                                v-model="positionFilter"
                                @change="applyFilter"
                                class="py-2 pl-3 pr-8 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition font-medium"
                            >
                                <option value="all">Semua Jabatan Fungsional</option>
                                <option v-for="pos in functionalPositions" :key="pos" :value="pos">{{ pos }}</option>
                            </select>

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
                        <span class="font-bold text-slate-900 dark:text-white">{{ lecturers.total || lecturers.data.length }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ stats.total }}</span>
                        dosen terdaftar
                    </div>
                    <div v-if="search || statusFilter !== 'all' || positionFilter !== 'all'">
                        <button
                            type="button"
                            @click="resetFilter"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- TABLE CARD -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs overflow-hidden">
                    <!-- DESKTOP TABLE VIEW -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-700/80 bg-slate-50/75 dark:bg-slate-900/40 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    <th class="py-3.5 px-4 w-12 text-center">No</th>
                                    <th class="py-3.5 px-4">Nama Dosen & Gelar</th>
                                    <th class="py-3.5 px-4">NIDN / NIP</th>
                                    <th class="py-3.5 px-4">Jabatan Fungsional</th>
                                    <th class="py-3.5 px-4">Program Studi</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-xs sm:text-sm">
                                <tr
                                    v-for="(lecturer, idx) in lecturers.data"
                                    :key="lecturer.id"
                                    class="hover:bg-blue-50/40 dark:hover:bg-slate-700/30 transition-colors"
                                >
                                    <td class="py-3.5 px-4 text-center text-xs font-medium text-slate-400">
                                        {{ (lecturers.current_page - 1) * lecturers.per_page + idx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                                {{ (lecturer.name || 'D').substring(0, 2).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-white">
                                                    {{ lecturer.name }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                                    <span v-if="lecturer.email" class="inline-flex items-center gap-1">
                                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        {{ lecturer.email }}
                                                    </span>
                                                    <span v-if="lecturer.academic_rank" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-[10px] text-slate-600 dark:text-slate-300">
                                                        {{ lecturer.academic_rank }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono text-xs text-slate-800 dark:text-slate-200">
                                            <div v-if="lecturer.nidn">
                                                <span class="text-[10px] text-slate-400 font-sans font-semibold">NIDN:</span> {{ lecturer.nidn }}
                                            </div>
                                            <div v-if="lecturer.nip" class="text-slate-500 dark:text-slate-400 mt-0.5">
                                                <span class="text-[10px] text-slate-400 font-sans font-semibold">NIP:</span> {{ lecturer.nip }}
                                            </div>
                                            <div v-if="!lecturer.nidn && !lecturer.nip" class="text-slate-400 text-xs italic">
                                                -
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span
                                            v-if="lecturer.functional_position"
                                            :class="[
                                                'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border',
                                                getBadgePosition(lecturer.functional_position)
                                            ]"
                                        >
                                            {{ lecturer.functional_position }}
                                        </span>
                                        <span v-else class="text-slate-400 text-xs italic">-</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                        {{ lecturer.study_program || 'Fakultas Ilmu Komputer' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button
                                            type="button"
                                            @click="toggleStatus(lecturer)"
                                            :title="lecturer.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all',
                                                lecturer.is_active
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100'
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-slate-200'
                                            ]"
                                        >
                                            <span :class="['w-1.5 h-1.5 rounded-full', lecturer.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                            <span>{{ lecturer.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditModal(lecturer)"
                                                class="w-8 h-8 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-slate-700/60 transition-colors inline-flex items-center justify-center"
                                                title="Edit Data Dosen"
                                                aria-label="Edit Data Dosen"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDelete(lecturer)"
                                                class="w-8 h-8 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-slate-700/60 transition-colors inline-flex items-center justify-center"
                                                title="Hapus Data Dosen"
                                                aria-label="Hapus Data Dosen"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="lecturers.data.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <p class="font-semibold text-sm">Belum ada data dosen</p>
                                            <p class="text-xs text-slate-400">Klik tombol "Tambah Dosen Baru" untuk mendaftarkan dosen pertama.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE CARD VIEW -->
                    <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-700/60">
                        <div
                            v-for="lecturer in lecturers.data"
                            :key="lecturer.id"
                            class="p-4 space-y-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                        {{ (lecturer.name || 'D').substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-white text-sm truncate">
                                            {{ lecturer.name }}
                                        </div>
                                        <div v-if="lecturer.email" class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                            {{ lecturer.email }}
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="toggleStatus(lecturer)"
                                    :title="lecturer.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition shrink-0',
                                        lecturer.is_active
                                            ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <span :class="['w-1.5 h-1.5 rounded-full', lecturer.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                    <span>{{ lecturer.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </button>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-2.5 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <span
                                        v-if="lecturer.functional_position"
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border',
                                            getBadgePosition(lecturer.functional_position)
                                        ]"
                                    >
                                        {{ lecturer.functional_position }}
                                    </span>
                                    <span class="text-[11px] font-medium text-slate-600 dark:text-slate-400">
                                        {{ lecturer.study_program || 'Fakultas Ilmu Komputer' }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-slate-600 dark:text-slate-300 flex items-center gap-2 flex-wrap">
                                    <span v-if="lecturer.nidn">NIDN: {{ lecturer.nidn }}</span>
                                    <span v-if="lecturer.nidn && lecturer.nip">•</span>
                                    <span v-if="lecturer.nip">NIP: {{ lecturer.nip }}</span>
                                    <span v-if="!lecturer.nidn && !lecturer.nip" class="text-slate-400 italic">Tanpa NIDN/NIP</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-1 pt-1">
                                <button
                                    type="button"
                                    @click="openEditModal(lecturer)"
                                    class="w-8 h-8 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-slate-700/60 transition-colors inline-flex items-center justify-center"
                                    title="Edit Data Dosen"
                                    aria-label="Edit Data Dosen"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete(lecturer)"
                                    class="w-8 h-8 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-slate-700/60 transition-colors inline-flex items-center justify-center"
                                    title="Hapus Data Dosen"
                                    aria-label="Hapus Data Dosen"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="lecturers.data.length === 0" class="py-10 text-center text-slate-400 p-4">
                            <p class="font-medium text-xs">Belum ada data dosen.</p>
                        </div>
                    </div>

                    <!-- PAGINATION -->
                    <div
                        v-if="lecturers.links && lecturers.links.length > 3"
                        class="px-4 py-3 border-t border-slate-100 dark:border-slate-700/80 flex items-center justify-between flex-wrap gap-2 text-xs"
                    >
                        <div class="text-slate-500 dark:text-slate-400">
                            Menampilkan <span class="font-bold text-slate-800 dark:text-slate-200">{{ lecturers.from || 0 }}</span> sampai <span class="font-bold text-slate-800 dark:text-slate-200">{{ lecturers.to || 0 }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ lecturers.total }}</span> dosen
                        </div>

                        <div class="inline-flex items-center gap-1">
                            <template v-for="(link, i) in lecturers.links" :key="i">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-medium transition-all',
                                        link.active
                                            ? 'bg-blue-600 text-white font-bold shadow-xs'
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1.5 rounded-lg text-slate-400 dark:text-slate-600 select-none"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL FORM CREATE / EDIT -->
        <Modal :show="showFormModal" @close="closeFormModal" max-width="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Data Dosen' : 'Tambah Data Dosen Baru' }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Data ini akan digunakan sebagai rujukan resmi pada surat akademik dan surat tugas.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeFormModal"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-5 space-y-4">
                    <!-- Nama Lengkap beserta Gelar -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Lengkap & Gelar <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                            class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- NIDN & NIP (2 kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                NIDN (Nomor Induk Dosen Nasional)
                            </label>
                            <input
                                v-model="form.nidn"
                                type="text"
                                placeholder="0412038901"
                                class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                            <p v-if="form.errors.nidn" class="mt-1 text-xs text-rose-500">{{ form.errors.nidn }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                NIP / NIDK (Opsional)
                            </label>
                            <input
                                v-model="form.nip"
                                type="text"
                                placeholder="19890312..."
                                class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                            <p v-if="form.errors.nip" class="mt-1 text-xs text-rose-500">{{ form.errors.nip }}</p>
                        </div>
                    </div>

                    <!-- Jabatan Fungsional & Pangkat/Golongan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Jabatan Fungsional
                            </label>
                            <select
                                v-model="form.functional_position"
                                class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">-- Pilih Jabatan Fungsional --</option>
                                <option v-for="pos in functionalPositions" :key="pos" :value="pos">{{ pos }}</option>
                            </select>
                            <p v-if="form.errors.functional_position" class="mt-1 text-xs text-rose-500">{{ form.errors.functional_position }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Pangkat / Golongan (Opsional)
                            </label>
                            <input
                                v-model="form.academic_rank"
                                type="text"
                                placeholder="Penata Muda / III a"
                                class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                            <p v-if="form.errors.academic_rank" class="mt-1 text-xs text-rose-500">{{ form.errors.academic_rank }}</p>
                        </div>
                    </div>

                    <!-- Program Studi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Program Studi / Unit
                        </label>
                        <select
                            v-model="form.study_program"
                            class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Fakultas Ilmu Komputer (Umum)</option>
                            <option v-for="prodi in studyPrograms" :key="prodi" :value="prodi">{{ prodi }}</option>
                        </select>
                    </div>

                    <!-- Email & No Telp -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Email Resmi
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="nama@unsub.ac.id"
                                class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nomor WhatsApp / HP
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="0812xxxxxxx"
                                class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Status Aktif -->
                    <div class="pt-2 flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Dosen Aktif Mengajar & Bimbingan
                            </span>
                        </label>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                        <button
                            type="button"
                            @click="closeFormModal"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 disabled:opacity-50 shadow-md shadow-blue-900/20 transition-all"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambahkan Dosen' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL DELETE CONFIRMATION -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="sm">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 mx-auto flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-4">
                    Hapus Data Dosen?
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                    Apakah Anda yakin ingin menghapus dosen <strong class="text-slate-800 dark:text-slate-200">{{ lecturerToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteLecturer"
                        :disabled="isDeleting"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 disabled:opacity-50 shadow-md shadow-rose-900/20 transition-all"
                    >
                        <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

