<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    categories: Array,
});

// Filter & Pencarian
const search = ref('');
const selectedDirection = ref('all');
const activeFilter = ref('all');

const directionTabs = computed(() => {
    const list = props.categories || [];
    const bothCount = list.filter((c) => c.letter_direction === 'both').length;
    const keluarCount = list.filter((c) => c.letter_direction === 'keluar').length;
    const masukCount = list.filter((c) => c.letter_direction === 'masuk').length;

    return [
        { id: 'all', label: 'Semua Peruntukan', count: list.length },
        { id: 'both', label: 'Semua Berkas (Masuk & Keluar)', count: bothCount },
        { id: 'keluar', label: 'Surat Keluar', count: keluarCount },
        { id: 'masuk', label: 'Surat Masuk', count: masukCount },
    ];
});

const filteredCategories = computed(() => {
    let list = props.categories || [];

    if (search.value.trim()) {
        const q = search.value.toLowerCase().trim();
        list = list.filter((c) =>
            (c.name && c.name.toLowerCase().includes(q)) ||
            (c.code && c.code.toLowerCase().includes(q)) ||
            (c.description && c.description.toLowerCase().includes(q))
        );
    }

    if (selectedDirection.value !== 'all') {
        list = list.filter((c) => c.letter_direction === selectedDirection.value);
    }

    if (activeFilter.value === 'active') {
        list = list.filter((c) => Boolean(c.is_active));
    } else if (activeFilter.value === 'inactive') {
        list = list.filter((c) => !Boolean(c.is_active));
    }

    return list;
});

const resetFilters = () => {
    search.value = '';
    selectedDirection.value = 'all';
    activeFilter.value = 'all';
};

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const categoryToEdit = ref(null);
const categoryToDelete = ref(null);

const form = useForm({
    name: '',
    code: '',
    letter_direction: 'both',
    description: '',
    color: 'blue',
    is_active: true,
});

const editForm = useForm({
    name: '',
    code: '',
    letter_direction: 'both',
    description: '',
    color: 'blue',
    is_active: true,
});

const colorOptions = [
    { label: 'Biru (Primary)', value: 'blue' },
    { label: 'Hijau (Emerald)', value: 'emerald' },
    { label: 'Ungu (Indigo/Purple)', value: 'indigo' },
    { label: 'Kuning (Amber)', value: 'amber' },
    { label: 'Merah (Rose)', value: 'rose' },
    { label: 'Abu-abu (Slate)', value: 'slate' },
];

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    showCreateModal.value = true;
};

const submitCreate = () => {
    form.post(route('settings.archive-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const openEditModal = (cat) => {
    categoryToEdit.value = cat;
    editForm.clearErrors();
    editForm.name = cat.name;
    editForm.code = cat.code;
    editForm.letter_direction = cat.letter_direction;
    editForm.description = cat.description || '';
    editForm.color = cat.color || 'blue';
    editForm.is_active = Boolean(cat.is_active);
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!categoryToEdit.value) return;
    editForm.put(route('settings.archive-categories.update', categoryToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            categoryToEdit.value = null;
        },
    });
};

const confirmDelete = (cat) => {
    categoryToDelete.value = cat;
    showDeleteModal.value = true;
};

const deleteCategory = () => {
    if (!categoryToDelete.value) return;
    form.delete(route('settings.archive-categories.destroy', categoryToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            categoryToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Daftar Kategori Arsip" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- CONTROLS & FILTER TOOLBAR (MATCHING TEMPLATE & KLASIFIKASI SURAT) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title & Action -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Kategori Arsip</h3>
                        </div>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Kategori Baru</span>
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
                                type="text"
                                placeholder="Cari nama kategori, kode (misal: SK_DOSEN), atau deskripsi..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="search = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Status Filter Toggle -->
                        <div class="flex items-center gap-2.5 shrink-0 self-end md:self-auto">
                            <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs">
                                <button
                                    type="button"
                                    @click="activeFilter = 'all'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        activeFilter === 'all'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    type="button"
                                    @click="activeFilter = 'active'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        activeFilter === 'active'
                                            ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Aktif
                                </button>
                                <button
                                    type="button"
                                    @click="activeFilter = 'inactive'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        activeFilter === 'inactive'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Nonaktif
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Category Direction Pills with Badges -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <button
                            v-for="tab in directionTabs"
                            :key="tab.id"
                            type="button"
                            @click="selectedDirection = tab.id"
                            :class="[
                                'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                selectedDirection === tab.id
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs'
                                    : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            <span>{{ tab.label }}</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                    selectedDirection === tab.id
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
                        <span class="font-bold text-slate-900 dark:text-white">{{ filteredCategories.length }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ (categories || []).length }}</span>
                        kategori arsip terdaftar
                    </div>
                    <div v-if="search || selectedDirection !== 'all' || activeFilter !== 'all'">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- CATEGORIES GRID -->
                <div v-if="filteredCategories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="cat in filteredCategories"
                        :key="cat.id"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between hover:shadow-md transition space-y-4"
                    >
                        <div class="space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <span class="px-2.5 py-1 rounded-lg font-mono text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ cat.code }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        v-if="!cat.is_active"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Nonaktif
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            cat.letter_direction === 'masuk' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' :
                                            cat.letter_direction === 'keluar' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' :
                                            'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300'
                                        ]"
                                    >
                                        {{ cat.letter_direction === 'masuk' ? 'Surat Masuk' : (cat.letter_direction === 'keluar' ? 'Surat Keluar' : 'Semua Berkas') }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ cat.name }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                    {{ cat.description || 'Tidak ada keterangan tambahan.' }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-600 dark:text-slate-400">
                                {{ cat.archives_count || 0 }} Berkas Diarsipkan
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="openEditModal(cat)"
                                    class="w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                    title="Edit Kategori"
                                    aria-label="Edit Kategori"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete(cat)"
                                    class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                    title="Hapus Kategori"
                                    aria-label="Hapus Kategori"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EMPTY STATE -->
                <div
                    v-else
                    class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs"
                >
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada kategori arsip yang sesuai</p>
                    <p class="text-xs text-slate-400 mt-1">Coba ubah kata kunci pencarian atau reset filter Anda.</p>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="mt-4 px-4 py-2 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH KATEGORI -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Tambah Kategori Arsip
                </h3>

                <form @submit.prevent="submitCreate" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kategori *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="SK Pengangkatan Dosen"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            required
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Kategori *</label>
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="SK_DOSEN"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono uppercase"
                            required
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Peruntukan Berkas *</label>
                        <select
                            v-model="form.letter_direction"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        >
                            <option value="both">Semua Berkas (Masuk & Keluar)</option>
                            <option value="masuk">Khusus Surat Masuk</option>
                            <option value="keluar">Khusus Surat Keluar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Warna Badge</label>
                        <select
                            v-model="form.color"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        >
                            <option v-for="c in colorOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Catatan</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Catatan peruntukan arsip ini..."
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="form.is_active"
                            id="create_is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="create_is_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                            Kategori Aktif (Dapat dipilih pada pengarsipan dokumen)
                        </label>
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
                            class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl disabled:opacity-50"
                        >
                            Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL EDIT KATEGORI -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Edit Kategori Arsip
                </h3>

                <form @submit.prevent="submitEdit" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kategori *</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                            required
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Kategori *</label>
                        <input
                            v-model="editForm.code"
                            type="text"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono uppercase"
                            required
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Peruntukan Berkas *</label>
                        <select
                            v-model="editForm.letter_direction"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        >
                            <option value="both">Semua Berkas (Masuk & Keluar)</option>
                            <option value="masuk">Khusus Surat Masuk</option>
                            <option value="keluar">Khusus Surat Keluar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Warna Badge</label>
                        <select
                            v-model="editForm.color"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        >
                            <option v-for="c in colorOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Catatan</label>
                        <textarea
                            v-model="editForm.description"
                            rows="2"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="editForm.is_active"
                            id="edit_is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="edit_is_active" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                            Kategori Aktif (Dapat dipilih pada pengarsipan dokumen)
                        </label>
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
                            class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl disabled:opacity-50"
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
                    Hapus Kategori Arsip?
                </h3>
                <p class="text-xs text-slate-500">
                    Apakah Anda yakin ingin menghapus kategori <strong>{{ categoryToDelete?.name }}</strong>?
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
                        @click="deleteCategory"
                        class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

