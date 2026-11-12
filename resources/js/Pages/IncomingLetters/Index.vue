<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    letters: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'all');
const status = ref(props.filters.status || 'all');

let searchTimeout = null;
watch([search, category, status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('incoming-letters.index'),
            {
                search: search.value,
                category: category.value,
                status: status.value,
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
    category.value = 'all';
    status.value = 'all';
};

const hasActiveFilters = computed(() => {
    return !!search.value || category.value !== 'all' || status.value !== 'all';
});

// Modal state
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const itemToEdit = ref(null);
const itemToDelete = ref(null);

const form = useForm({
    reference_number: '',
    sender: '',
    subject: '',
    letter_date: new Date().toISOString().split('T')[0],
    received_date: new Date().toISOString().split('T')[0],
    category: 'Dinas / Eksternal',
    status: 'received',
    description: '',
    drive_url: '',
});

const editForm = useForm({
    reference_number: '',
    sender: '',
    subject: '',
    letter_date: '',
    received_date: '',
    category: '',
    status: '',
    description: '',
    drive_url: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.letter_date = new Date().toISOString().split('T')[0];
    form.received_date = new Date().toISOString().split('T')[0];
    form.category = 'Dinas / Eksternal';
    form.status = 'received';
    showCreateModal.value = true;
};

const submitCreate = () => {
    form.post(route('incoming-letters.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const openEditModal = (item) => {
    itemToEdit.value = item;
    editForm.clearErrors();
    editForm.reference_number = item.reference_number || '';
    editForm.sender = item.sender || '';
    editForm.subject = item.subject || '';
    editForm.letter_date = item.letter_date || '';
    editForm.received_date = item.received_date || '';
    editForm.category = item.category || 'Dinas / Eksternal';
    editForm.status = item.status || 'received';
    editForm.description = item.description || '';
    editForm.drive_url = item.drive_url || '';
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!itemToEdit.value) return;
    editForm.put(route('incoming-letters.update', itemToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            itemToEdit.value = null;
        },
    });
};

const openDeleteModal = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('incoming-letters.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
};

const archivingId = ref(null);
const archiveIncoming = (item) => {
    if (!confirm(`Arsipkan surat masuk "${item.reference_number || item.subject}" ke Pusat Arsip?`)) return;
    archivingId.value = item.id;
    router.post(
        route('archives.incoming', item.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                archivingId.value = null;
            },
        }
    );
};

const getStatusBadge = (s) => {
    switch (s) {
        case 'disposed':
            return {
                text: 'Didisposisi',
                class: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300 dark:border-blue-800',
            };
        case 'archived':
            return {
                text: 'Diarsipkan',
                class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800',
            };
        default:
            return {
                text: 'Diterima',
                class: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800',
            };
    }
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};
</script>

<template>
    <Head title="Daftar Surat Masuk" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR (UNIFIED STYLE) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Surat Masuk</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Total {{ stats?.total ?? 0 }} surat masuk tercatat ({{ stats?.this_month ?? 0 }} diterima bulan ini, {{ stats?.with_file ?? 0 }} memiliki berkas Drive)
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
                            <span>Catat Surat Masuk</span>
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
                                placeholder="Cari perihal, nomor surat, atau instansi pengirim..."
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
                        <div class="w-full md:w-56 shrink-0">
                            <select
                                v-model="category"
                                class="w-full px-3 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition font-medium"
                            >
                                <option value="all">Semua Kategori</option>
                                <option v-for="c in categories" :key="c.id" :value="c.name">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter Toggle -->
                        <div class="flex items-center gap-2.5 shrink-0 self-end md:self-auto">
                            <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs">
                                <button
                                    type="button"
                                    @click="status = 'all'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        status === 'all'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    type="button"
                                    @click="status = 'received'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        status === 'received'
                                            ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Diterima
                                </button>
                                <button
                                    type="button"
                                    @click="status = 'disposed'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        status === 'disposed'
                                            ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Didisposisi
                                </button>
                                <button
                                    type="button"
                                    @click="status = 'archived'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        status === 'archived'
                                            ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Diarsipkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ letters?.data?.length || 0 }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ letters?.total || 0 }}</span>
                        surat masuk
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

                <!-- TABLE CARD -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase tracking-wider text-[11px] font-semibold border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="px-5 py-3.5">No. Surat & Asal Pengirim</th>
                                    <th class="px-5 py-3.5">Perihal & Keterangan</th>
                                    <th class="px-5 py-3.5">Tanggal Surat & Diterima</th>
                                    <th class="px-5 py-3.5">Kategori & Status</th>
                                    <th class="px-5 py-3.5 text-center">Berkas Fisik</th>
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="!letters.data.length">
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <span class="font-semibold text-slate-600 dark:text-slate-400">Belum ada data surat masuk</span>
                                            <span class="text-[11px] text-slate-400">Klik tombol "+ Catat Surat Masuk" untuk merekam surat baru.</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="item in letters.data"
                                    :key="item.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                >
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ item.reference_number || '(Tanpa Nomor Surat)' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-medium">
                                            Pengirim: <span class="text-slate-700 dark:text-slate-300">{{ item.sender }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-xs">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 line-clamp-1">
                                            {{ item.subject }}
                                        </div>
                                        <div v-if="item.description" class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                            {{ item.description }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="text-slate-700 dark:text-slate-300">
                                            Surat: <span class="font-medium">{{ item.letter_date }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            Diterima: <span class="font-medium">{{ item.received_date }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 mb-1">
                                            {{ item.category }}
                                        </div>
                                        <div>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                    getStatusBadge(item.status).class
                                                ]"
                                            >
                                                {{ getStatusBadge(item.status).text }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <a
                                            v-if="item.drive_url"
                                            :href="item.drive_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900 transition border border-emerald-200 dark:border-emerald-800"
                                            title="Buka Berkas di Google Drive"
                                        >
                                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 87.3 78" fill="none">
                                                <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                                                <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44c-.8 1.4-1.2 2.95-1.2 4.5h27.5z" fill="#00ac47"/>
                                                <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                                                <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                                                <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                                                <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                                            </svg>
                                            <span>Buka Drive</span>
                                        </a>
                                        <a
                                            v-else-if="item.file_path"
                                            :href="route('incoming-letters.download', item.id)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900 transition border border-blue-200 dark:border-blue-800"
                                            :title="`Unduh: ${item.file_name} (${formatFileSize(item.file_size)})`"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>Unduh File</span>
                                        </a>
                                        <span v-else class="text-[11px] text-slate-400 italic">
                                            Tanpa Lampiran
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Tombol Arsipkan Surat Masuk -->
                                            <button
                                                v-if="!item.archive"
                                                type="button"
                                                @click="archiveIncoming(item)"
                                                :disabled="archivingId === item.id"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 rounded-lg border border-indigo-200 dark:border-indigo-800 transition shadow-2xs disabled:opacity-50"
                                                title="Simpan surat masuk ini ke Pusat Arsip"
                                            >
                                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                </svg>
                                                <span>{{ archivingId === item.id ? 'Mengarsipkan...' : 'Arsipkan' }}</span>
                                            </button>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800"
                                                title="Surat ini sudah tersimpan di Pusat Arsip"
                                            >
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Diarsipkan</span>
                                            </span>

                                            <button
                                                type="button"
                                                @click="openEditModal(item)"
                                                class="w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                                title="Edit Data Surat Masuk"
                                                aria-label="Edit Data Surat Masuk"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openDeleteModal(item)"
                                                class="w-8 h-8 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition inline-flex items-center justify-center"
                                                title="Hapus Surat Masuk"
                                                aria-label="Hapus Surat Masuk"
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

                    <!-- PAGINATION -->
                    <div v-if="letters.links && letters.links.length > 3" class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div class="text-slate-500">
                            Menampilkan {{ letters.from }} sampai {{ letters.to }} dari {{ letters.total }} surat
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                v-for="(link, i) in letters.links"
                                :key="i"
                                @click="link.url && router.get(link.url)"
                                :disabled="!link.url"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-medium transition',
                                    link.active ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40'
                                ]"
                                v-html="link.label"
                            ></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div @click="showCreateModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Catat Surat Masuk Baru
                        </h3>
                        <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Surat (Pengirim)</label>
                                <input v-model="form.reference_number" type="text" placeholder="045/PAN-STIKOM/X/2026" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Asal / Instansi Pengirim <span class="text-rose-500">*</span></label>
                                <input v-model="form.sender" type="text" required placeholder="PT. Telkom Indonesia" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Perihal / Isi Ringkas Surat <span class="text-rose-500">*</span></label>
                            <input v-model="form.subject" type="text" required placeholder="Permohonan Narasumber Kuliah Umum AI" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Surat <span class="text-rose-500">*</span></label>
                                <input v-model="form.letter_date" type="date" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Diterima <span class="text-rose-500">*</span></label>
                                <input v-model="form.received_date" type="date" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori Surat <span class="text-rose-500">*</span></label>
                            <select v-model="form.category" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                                <option v-for="c in categories" :key="c.id" :value="c.name">
                                    {{ c.name }}
                                </option>
                                <option v-if="!categories.length" value="Dinas / Eksternal">Dinas / Eksternal</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Ringkasan Disposisi</label>
                            <textarea v-model="form.description" rows="2" placeholder="Catatan tambahan isi surat atau arahan pimpinan..." class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800"></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tautan Google Drive Berkas Surat
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4" viewBox="0 0 87.3 78" fill="none">
                                        <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                                        <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44c-.8 1.4-1.2 2.95-1.2 4.5h27.5z" fill="#00ac47"/>
                                        <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                                        <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                                        <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                                        <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                                    </svg>
                                </span>
                                <input
                                    v-model="form.drive_url"
                                    type="url"
                                    placeholder="https://drive.google.com/file/d/.../view?usp=sharing"
                                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Masukkan link Google Drive (pastikan akses link sudah diset "Siapa saja yang memiliki link dapat melihat").
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">Batal</button>
                            <button type="submit" :disabled="form.processing" class="px-5 py-2 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-500 disabled:opacity-50">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Surat Masuk' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- EDIT MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div @click="showEditModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Edit Data Surat Masuk
                        </h3>
                        <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-3.5 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Surat (Pengirim)</label>
                                <input v-model="editForm.reference_number" type="text" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Asal / Instansi Pengirim <span class="text-rose-500">*</span></label>
                                <input v-model="editForm.sender" type="text" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Perihal / Isi Ringkas Surat <span class="text-rose-500">*</span></label>
                            <input v-model="editForm.subject" type="text" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Surat <span class="text-rose-500">*</span></label>
                                <input v-model="editForm.letter_date" type="date" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Diterima <span class="text-rose-500">*</span></label>
                                <input v-model="editForm.received_date" type="date" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori Surat <span class="text-rose-500">*</span></label>
                            <select v-model="editForm.category" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                                <option v-for="c in categories" :key="c.id" :value="c.name">
                                    {{ c.name }}
                                </option>
                                <option v-if="!categories.length" value="Dinas / Eksternal">Dinas / Eksternal</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Ringkasan Disposisi</label>
                            <textarea v-model="editForm.description" rows="2" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800"></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tautan Google Drive Berkas Surat
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4" viewBox="0 0 87.3 78" fill="none">
                                        <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                                        <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44c-.8 1.4-1.2 2.95-1.2 4.5h27.5z" fill="#00ac47"/>
                                        <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                                        <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                                        <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                                        <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                                    </svg>
                                </span>
                                <input
                                    v-model="editForm.drive_url"
                                    type="url"
                                    placeholder="https://drive.google.com/file/d/.../view?usp=sharing"
                                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Masukkan link Google Drive berkas surat (pastikan akses share link dapat dilihat).
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700">Batal</button>
                            <button type="submit" :disabled="editForm.processing" class="px-5 py-2 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-500 disabled:opacity-50">
                                {{ editForm.processing ? 'Menyimpan...' : 'Perbarui Surat Masuk' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- DELETE CONFIRM MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div @click="showDeleteModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Arsip Surat Masuk?</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Anda akan menghapus arsip surat <strong class="text-slate-800 dark:text-slate-200">{{ itemToDelete?.subject }}</strong> dari <strong>{{ itemToDelete?.sender }}</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-xl text-xs border border-slate-200 dark:border-slate-700">Batal</button>
                        <button type="button" @click="confirmDelete" class="px-4 py-2 rounded-xl text-xs bg-rose-600 text-white font-bold hover:bg-rose-500">Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

