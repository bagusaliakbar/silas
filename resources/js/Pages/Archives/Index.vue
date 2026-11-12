<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    archives: Object,
    categories: Array,
    incomingLetters: Array,
    outgoingLetters: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const category_id = ref(props.filters.category_id || 'all');
const letterType = ref(props.filters.type || 'all');

let searchTimeout = null;
watch([search, category_id, letterType], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('archives.index'),
            {
                search: search.value,
                category_id: category_id.value,
                type: letterType.value,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 300);
});

const resetFilters = () => {
    search.value = '';
    category_id.value = 'all';
    letterType.value = 'all';
};

const hasActiveFilters = computed(() => {
    return !!search.value || category_id.value !== 'all' || letterType.value !== 'all';
});

const typeTabs = computed(() => [
    { id: 'all', label: 'Semua Arsip', count: props.stats?.total ?? 0 },
    { id: 'masuk', label: 'Surat Masuk', count: props.stats?.masuk ?? 0 },
    { id: 'keluar', label: 'Surat Keluar', count: props.stats?.keluar ?? 0 },
]);

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const archiveToEdit = ref(null);
const archiveToDelete = ref(null);

// Source selector for Add Archive (manual, from incoming, from outgoing)
const archiveSource = ref('manual'); // 'manual' | 'incoming' | 'outgoing'

const form = useForm({
    title: '',
    reference_number: '',
    letter_date: new Date().toISOString().split('T')[0],
    letter_type: 'masuk',
    archive_category_id: props.categories[0]?.id || '',
    sender: '',
    recipient: 'Fakultas Ilmu Komputer Universitas Subang',
    google_form_url: '',
    drive_url: '',
    file: null,
    description: '',
    incoming_letter_id: null,
    letter_request_id: null,
});

const editForm = useForm({
    title: '',
    reference_number: '',
    letter_date: '',
    letter_type: 'masuk',
    archive_category_id: '',
    sender: '',
    recipient: '',
    google_form_url: '',
    drive_url: '',
    file: null,
    description: '',
});

const onSelectIncomingLetter = (letterId) => {
    const l = props.incomingLetters.find((i) => i.id === Number(letterId));
    if (l) {
        form.incoming_letter_id = l.id;
        form.letter_request_id = null;
        form.title = l.subject;
        form.reference_number = l.reference_number || '';
        form.letter_date = l.letter_date;
        form.sender = l.sender;
        form.recipient = 'Fakultas Ilmu Komputer UNSUB';
        form.letter_type = 'masuk';
        form.drive_url = l.drive_url || '';
    }
};

const onSelectOutgoingLetter = (letterId) => {
    const l = props.outgoingLetters.find((o) => o.id === Number(letterId));
    if (l) {
        form.letter_request_id = l.id;
        form.incoming_letter_id = null;
        form.title = (l.letter_type?.name || 'Surat Keluar') + ' - ' + l.student_name;
        form.reference_number = l.letter_number || '';
        form.letter_date = l.created_at ? l.created_at.split('T')[0] : new Date().toISOString().split('T')[0];
        form.sender = 'Fakultas Ilmu Komputer UNSUB';
        form.recipient = l.student_name;
        form.letter_type = 'keluar';
        form.drive_url = l.manual_drive_link || '';
    }
};

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.letter_date = new Date().toISOString().split('T')[0];
    form.archive_category_id = props.categories[0]?.id || '';
    archiveSource.value = 'manual';
    showCreateModal.value = true;
};

const submitCreate = () => {
    form.post(route('archives.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const openEditModal = (arc) => {
    archiveToEdit.value = arc;
    editForm.clearErrors();
    editForm.title = arc.title;
    editForm.reference_number = arc.reference_number || '';
    editForm.letter_date = arc.letter_date || '';
    editForm.letter_type = arc.letter_type || 'masuk';
    editForm.archive_category_id = arc.archive_category_id || '';
    editForm.sender = arc.sender || '';
    editForm.recipient = arc.recipient || '';
    editForm.google_form_url = arc.google_form_url || '';
    editForm.drive_url = arc.drive_url || '';
    editForm.description = arc.description || '';
    editForm.file = null;
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!archiveToEdit.value) return;
    editForm.put(route('archives.update', archiveToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            archiveToEdit.value = null;
        },
    });
};

const confirmDelete = (arc) => {
    archiveToDelete.value = arc;
    showDeleteModal.value = true;
};

const deleteArchive = () => {
    if (!archiveToDelete.value) return;
    form.delete(route('archives.destroy', archiveToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            archiveToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Pusat Arsip Dokumen" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR (UNIFIED STYLE) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Pusat Arsip Dokumen</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Total {{ stats?.total ?? 0 }} berkas tersimpan ({{ stats?.masuk ?? 0 }} surat masuk, {{ stats?.keluar ?? 0 }} surat keluar, {{ stats?.gform ?? 0 }} tautan form)
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Arsip Baru</span>
                        </button>
                    </div>

                    <!-- Search & Filter Controls -->
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
                                type="text"
                                placeholder="Cari perihal, nomor surat, pengirim, atau penerima..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="search = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Hapus pencarian"
                                aria-label="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Category Filter Dropdown -->
                        <div class="w-full md:w-64 shrink-0">
                            <select
                                v-model="category_id"
                                class="w-full px-3 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition font-medium"
                            >
                                <option value="all">Semua Kategori Arsip</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Letter Type Tabs with Count Badges -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <button
                            v-for="tab in typeTabs"
                            :key="tab.id"
                            type="button"
                            @click="letterType = tab.id"
                            :class="[
                                'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                letterType === tab.id
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs'
                                    : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            <span>{{ tab.label }}</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                    letterType === tab.id
                                        ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900'
                                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ archives?.data?.length || 0 }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ archives?.total || 0 }}</span>
                        arsip dokumen
                        <span v-if="hasActiveFilters" class="text-blue-600 dark:text-blue-400 font-medium">
                            (terfilter)
                        </span>
                    </div>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="resetFilters"
                        class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold flex items-center gap-1"
                    >
                        <span>Reset Filter</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- ARCHIVES LIST TABLE -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div v-if="archives.data.length === 0" class="p-12 text-center space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Dokumen Diarsipkan</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">
                            Klik tombol "+ Arsipkan Dokumen Baru" untuk mendokumentasikan surat masuk, surat keluar, atau tautan Google Form.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 font-semibold border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5">Dokumen / Perihal</th>
                                    <th class="px-5 py-3.5">Jenis & Kategori</th>
                                    <th class="px-5 py-3.5">Asal & Tujuan</th>
                                    <th class="px-5 py-3.5">Tanggal</th>
                                    <th class="px-5 py-3.5">Media / Berkas</th>
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="arc in archives.data"
                                    :key="arc.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition"
                                >
                                    <td class="px-5 py-4 max-w-xs">
                                        <div class="font-bold text-slate-900 dark:text-white line-clamp-2">
                                            {{ arc.title }}
                                        </div>
                                        <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                            {{ arc.reference_number || 'Tanpa Nomor Dokumen' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1 items-start">
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                                    arc.letter_type === 'masuk' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' :
                                                    arc.letter_type === 'keluar' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' :
                                                    'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300'
                                                ]"
                                            >
                                                {{ arc.letter_type === 'masuk' ? 'Surat Masuk' : (arc.letter_type === 'keluar' ? 'Surat Keluar' : 'Lainnya') }}
                                            </span>
                                            <span class="text-[11px] text-slate-600 dark:text-slate-400 font-medium">
                                                {{ arc.category?.name || 'Umum' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-300">
                                        <div><strong class="text-slate-400 text-[10px]">DARI:</strong> {{ arc.sender || '-' }}</div>
                                        <div><strong class="text-slate-400 text-[10px]">KEPADA:</strong> {{ arc.recipient || '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                                        {{ arc.letter_date }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <!-- Google Form Pill Link -->
                                            <a
                                                v-if="arc.google_form_url"
                                                :href="arc.google_form_url"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800 hover:bg-purple-100 transition"
                                                title="Buka Tautan Google Form Respon / Upload"
                                            >
                                                <svg class="w-3.5 h-3.5 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                                </svg>
                                                <span>Google Form</span>
                                            </a>

                                            <!-- Google Drive Pill Link -->
                                            <a
                                                v-if="arc.drive_url"
                                                :href="arc.drive_url"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition"
                                                title="Buka Dokumen di Google Drive"
                                            >
                                                <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-1.41-1.41L13.17 14H8v-2h5.17l-2.58-2.59L12 8l5 5z"/>
                                                </svg>
                                                <span>Drive</span>
                                            </a>

                                            <!-- Direct Upload File Download -->
                                            <a
                                                v-if="arc.file_path"
                                                :href="route('archives.download', arc.id)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition"
                                                title="Unduh Berkas Langsung"
                                            >
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                <span>PDF</span>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                @click="openEditModal(arc)"
                                                class="w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                                title="Edit Data Arsip"
                                                aria-label="Edit Data Arsip"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDelete(arc)"
                                                class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                                title="Hapus Arsip"
                                                aria-label="Hapus Arsip"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PAGINATION -->
                <div v-if="archives.links && archives.links.length > 3" class="p-4 flex justify-center gap-1">
                    <Link
                        v-for="(link, idx) in archives.links"
                        :key="idx"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                            link.active
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                            !link.url && 'opacity-40 pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH ARSIP -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="2xl">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Arsipkan Dokumen & Surat
                        </h3>
                        <p class="text-xs text-slate-400">
                            Pilih sumber dokumen dari sistem atau masukkan secara mandiri.
                        </p>
                    </div>
                    <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <!-- SELECTOR SUMBER DOKUMEN -->
                <div class="grid grid-cols-3 gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-bold">
                    <button
                        type="button"
                        @click="archiveSource = 'manual'"
                        :class="[
                            'py-2 rounded-lg transition',
                            archiveSource === 'manual' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-xs' : 'text-slate-600 dark:text-slate-400'
                        ]"
                    >
                        Form Bebas / Mandiri
                    </button>
                    <button
                        type="button"
                        @click="archiveSource = 'incoming'"
                        :class="[
                            'py-2 rounded-lg transition',
                            archiveSource === 'incoming' ? 'bg-white dark:bg-slate-700 text-amber-600 shadow-xs' : 'text-slate-600 dark:text-slate-400'
                        ]"
                    >
                        Pilih Surat Masuk
                    </button>
                    <button
                        type="button"
                        @click="archiveSource = 'outgoing'"
                        :class="[
                            'py-2 rounded-lg transition',
                            archiveSource === 'outgoing' ? 'bg-white dark:bg-slate-700 text-indigo-600 shadow-xs' : 'text-slate-600 dark:text-slate-400'
                        ]"
                    >
                        Pilih Surat Keluar
                    </button>
                </div>

                <!-- DROPDOWN PILIHAN SURAT MASUK / KELUAR -->
                <div v-if="archiveSource === 'incoming'" class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl space-y-2">
                    <label class="block text-xs font-bold text-amber-900 dark:text-amber-200">
                        Pilih Surat Masuk yang Akan Diarsipkan:
                    </label>
                    <select
                        @change="(e) => onSelectIncomingLetter(e.target.value)"
                        class="w-full text-xs rounded-xl border border-amber-300 bg-white dark:bg-slate-900 p-2"
                    >
                        <option value="">-- Pilih Surat Masuk dari Daftar --</option>
                        <option v-for="inc in incomingLetters" :key="inc.id" :value="inc.id">
                            {{ inc.reference_number || 'Tanpa No' }} - {{ inc.subject }} ({{ inc.sender }})
                        </option>
                    </select>
                </div>

                <div v-else-if="archiveSource === 'outgoing'" class="p-3 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-xl space-y-2">
                    <label class="block text-xs font-bold text-indigo-900 dark:text-indigo-200">
                        Pilih Surat Keluar / Mahasiswa yang Akan Diarsipkan:
                    </label>
                    <select
                        @change="(e) => onSelectOutgoingLetter(e.target.value)"
                        class="w-full text-xs rounded-xl border border-indigo-300 bg-white dark:bg-slate-900 p-2"
                    >
                        <option value="">-- Pilih Surat Keluar dari Daftar --</option>
                        <option v-for="out in outgoingLetters" :key="out.id" :value="out.id">
                            {{ out.letter_number || 'Draf' }} - {{ out.letter_type?.name }} ({{ out.student_name }})
                        </option>
                    </select>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Judul / Perihal Arsip *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Surat Keputusan Dekan tentang Tugas Mengajar Semester Genap"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nomor Surat / Dokumen
                            </label>
                            <input
                                v-model="form.reference_number"
                                type="text"
                                placeholder="045/UNSUB/FIK/X/2026"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Dokumen / Surat *
                            </label>
                            <input
                                v-model="form.letter_date"
                                type="date"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                required
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Jenis Aliran Surat *
                            </label>
                            <select
                                v-model="form.letter_type"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            >
                                <option value="masuk">Surat Masuk (Eksternal ke Fakultas)</option>
                                <option value="keluar">Surat Keluar (Diterbitkan Fakultas)</option>
                                <option value="lainnya">Dokumen / Formulir Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kategori Arsip *
                            </label>
                            <select
                                v-model="form.archive_category_id"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            >
                                <option v-for="c in categories" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Pengirim / Asal Surat
                            </label>
                            <input
                                v-model="form.sender"
                                type="text"
                                placeholder="Rektorat Universitas Subang"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Penerima / Tujuan Surat
                            </label>
                            <input
                                v-model="form.recipient"
                                type="text"
                                placeholder="Dekan Fakultas Ilmu Komputer"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            />
                        </div>
                    </div>

                    <!-- INPUT MEDIA: GOOGLE FORM, GOOGLE DRIVE, FILE UPLOAD -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3">
                        <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <span>📎 Tautan Google Form & Berkas Arsip</span>
                        </div>

                        <div>
                            <label class="block font-semibold text-purple-700 dark:text-purple-300 mb-1 flex items-center gap-1">
                                <span>Link Google Form Respon / Bukti Upload:</span>
                            </label>
                            <input
                                v-model="form.google_form_url"
                                type="url"
                                placeholder="https://forms.gle/... atau https://docs.google.com/forms/..."
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-blue-700 dark:text-blue-300 mb-1 flex items-center gap-1">
                                <span>Link Google Drive Berkas:</span>
                            </label>
                            <input
                                v-model="form.drive_url"
                                type="url"
                                placeholder="https://drive.google.com/file/d/..."
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-emerald-700 dark:text-emerald-300 mb-1">
                                Atau Unggah File Langsung (PDF / JPG, Maks 20MB):
                            </label>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                @change="(e) => form.file = e.target.files[0]"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-300 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-900"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Ringkasan / Keterangan Isi
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Keterangan singkat mengenai isi surat atau catatan disposisi..."
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs disabled:opacity-50"
                        >
                            Simpan ke Arsip
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL EDIT ARSIP -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="2xl">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Edit Data Arsip
                    </h3>
                    <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Judul / Perihal *</label>
                            <input
                                v-model="editForm.title"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                required
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Surat</label>
                            <input
                                v-model="editForm.reference_number"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Dokumen *</label>
                            <input
                                v-model="editForm.letter_date"
                                type="date"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                required
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Surat *</label>
                            <select
                                v-model="editForm.letter_type"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            >
                                <option value="masuk">Surat Masuk</option>
                                <option value="keluar">Surat Keluar</option>
                                <option value="lainnya">Dokumen Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori Arsip *</label>
                            <select
                                v-model="editForm.archive_category_id"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            >
                                <option v-for="c in categories" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pengirim</label>
                            <input
                                v-model="editForm.sender"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penerima</label>
                            <input
                                v-model="editForm.recipient"
                                type="text"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            />
                        </div>
                    </div>

                    <!-- MEDIA LINKS -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3">
                        <div>
                            <label class="block font-semibold text-purple-700 dark:text-purple-300 mb-1">Link Google Form:</label>
                            <input
                                v-model="editForm.google_form_url"
                                type="url"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-blue-700 dark:text-blue-300 mb-1">Link Google Drive:</label>
                            <input
                                v-model="editForm.drive_url"
                                type="url"
                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-emerald-700 dark:text-emerald-300 mb-1">Ganti Berkas File (Opsional):</label>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                @change="(e) => editForm.file = e.target.files[0]"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-300 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-900"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ringkasan / Keterangan Isi</label>
                        <textarea
                            v-model="editForm.description"
                            rows="2"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs disabled:opacity-50"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI HAPUS -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Hapus Arsip Dokumen?
                </h3>
                <p class="text-xs text-slate-500">
                    Apakah Anda yakin ingin menghapus arsip <strong>{{ archiveToDelete?.title }}</strong>?
                </p>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteArchive"
                        class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
