<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    classifications: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            used: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category: 'all',
        }),
    },
});

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const activeFilter = ref('all'); // 'all', 'active', 'inactive'

const categoryTabs = computed(() => {
    const list = [
        { id: 'all', label: 'Semua Kategori', count: props.classifications.length },
    ];

    const countMap = {};
    props.classifications.forEach((c) => {
        const cat = c.category || 'Lainnya';
        countMap[cat] = (countMap[cat] || 0) + 1;
    });

    const uniqueCategories = [...new Set(props.categories)];
    uniqueCategories.sort((a, b) => {
        const order = ['Pendidikan (PD.1)', 'Administrasi & Keuangan (PD.2)', 'Kemahasiswaan (PD.3)'];
        const idxA = order.indexOf(a);
        const idxB = order.indexOf(b);
        if (idxA !== -1 && idxB !== -1) return idxA - idxB;
        if (idxA !== -1) return -1;
        if (idxB !== -1) return 1;
        return a.localeCompare(b);
    });

    uniqueCategories.forEach((cat) => {
        list.push({
            id: cat,
            label: cat,
            count: countMap[cat] || 0,
        });
    });

    return list;
});

const resetFilters = () => {
    search.value = '';
    selectedCategory.value = 'all';
    activeFilter.value = 'all';
};

const getCategoryBadgeClass = (category) => {
    if (!category) return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    if (category.includes('PD.1') || category.toLowerCase().includes('pendidikan')) {
        return 'bg-blue-50 text-blue-800 border-blue-200/80 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800/70';
    }
    if (category.includes('PD.2') || category.toLowerCase().includes('administrasi') || category.toLowerCase().includes('keuangan')) {
        return 'bg-purple-50 text-purple-800 border-purple-200/80 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800/70';
    }
    if (category.includes('PD.3') || category.toLowerCase().includes('kemahasiswaan')) {
        return 'bg-emerald-50 text-emerald-800 border-emerald-200/80 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/70';
    }
    return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
};

const filteredClassifications = computed(() => {
    let result = props.classifications;

    if (activeFilter.value === 'active') {
        result = result.filter((c) => c.is_active);
    } else if (activeFilter.value === 'inactive') {
        result = result.filter((c) => !c.is_active);
    }

    if (selectedCategory.value !== 'all') {
        result = result.filter((c) => c.category === selectedCategory.value);
    }

    if (search.value.trim()) {
        const query = search.value.toLowerCase();
        result = result.filter((c) =>
            (c.code || '').toLowerCase().includes(query) ||
            (c.name || '').toLowerCase().includes(query) ||
            (c.category || '').toLowerCase().includes(query) ||
            (c.description || '').toLowerCase().includes(query)
        );
    }

    return result;
});

// Modal state
const showFormModal = ref(false);
const isEditing = ref(false);
const editingClassificationId = ref(null);

const form = useForm({
    code: '',
    name: '',
    category: 'Pendidikan (PD.1)',
    description: '',
    order: 1,
    is_active: true,
});

const showDeleteModal = ref(false);
const classificationToDelete = ref(null);
const isDeleting = ref(false);

const openCreateModal = () => {
    isEditing.value = false;
    editingClassificationId.value = null;
    form.reset();
    form.clearErrors();
    form.order = (props.classifications.length > 0 ? Math.max(...props.classifications.map(c => c.order || 0)) + 1 : 1);
    form.category = 'Pendidikan (PD.1)';
    form.is_active = true;
    showFormModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    editingClassificationId.value = item.id;
    form.clearErrors();
    form.code = item.code || '';
    form.name = item.name || '';
    form.category = item.category || 'Pendidikan (PD.1)';
    form.description = item.description || '';
    form.order = item.order ?? 1;
    form.is_active = Boolean(item.is_active);
    showFormModal.value = true;
};

const saveClassification = () => {
    if (isEditing.value) {
        form.put(route('settings.classifications.update', editingClassificationId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('settings.classifications.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (item) => {
    router.patch(route('settings.classifications.toggle', item.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (item) => {
    classificationToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!classificationToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('settings.classifications.destroy', classificationToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            classificationToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Daftar Klasifikasi Surat" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR (MATCHING TEMPLATE SURAT STYLE) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Kode & Klasifikasi Surat</h3>
                        </div>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Kode Klasifikasi</span>
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
                                placeholder="Cari kode (misal: PD.1.1), nama perihal, atau ruang lingkup surat..."
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

                    <!-- Category Pills with Badges (Matching Template Surat tabs) -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <button
                            v-for="cat in categoryTabs"
                            :key="cat.id"
                            type="button"
                            @click="selectedCategory = cat.id"
                            :class="[
                                'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl font-bold whitespace-nowrap transition border',
                                selectedCategory === cat.id
                                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-slate-900 dark:border-white shadow-xs'
                                    : 'bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-200/80 dark:border-slate-700 hover:bg-slate-100'
                            ]"
                        >
                            <span>{{ cat.label }}</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
                                    selectedCategory === cat.id
                                        ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900'
                                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                {{ cat.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ filteredClassifications.length }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ classifications.length }}</span>
                        kode klasifikasi surat
                    </div>
                    <div v-if="search || selectedCategory !== 'all' || activeFilter !== 'all'">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3.5 px-4 w-12 text-center">No.</th>
                                    <th class="py-3.5 px-4 w-40">Kode Surat</th>
                                    <th class="py-3.5 px-4">Keterangan / Hal Surat</th>
                                    <th class="py-3.5 px-4 w-48">Kategori Bidang</th>
                                    <th class="py-3.5 px-4 w-28 text-center">Penggunaan</th>
                                    <th class="py-3.5 px-4 w-24 text-center">Status</th>
                                    <th class="py-3.5 px-4 w-28 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="(item, idx) in filteredClassifications"
                                    :key="item.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                >
                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-black text-slate-900 dark:text-white text-xs">
                                        <span class="px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-blue-700 dark:text-blue-300 border border-slate-200 dark:border-slate-700">
                                            {{ item.code }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs">
                                            {{ item.name }}
                                        </div>
                                        <div v-if="item.description" class="text-[11px] text-slate-500 mt-0.5">
                                            {{ item.description }}
                                        </div>
                                        <div v-if="item.letter_types && item.letter_types.length > 0" class="mt-2 flex flex-wrap items-center gap-1.5">
                                            <span class="text-[10px] font-semibold text-slate-400">Contoh / Format:</span>
                                            <span
                                                v-for="lt in item.letter_types"
                                                :key="lt.id"
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800/80"
                                            >
                                                {{ lt.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span
                                            :class="[
                                                'inline-block px-2.5 py-1 rounded-lg text-[11px] font-bold border',
                                                getCategoryBadgeClass(item.category)
                                            ]"
                                        >
                                            {{ item.category || 'Umum' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold',
                                                item.letter_types_count > 0
                                                    ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300'
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                            ]"
                                        >
                                            {{ item.letter_types_count }} Format
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button
                                            type="button"
                                            @click="toggleStatus(item)"
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition shadow-2xs',
                                                item.is_active
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 hover:bg-emerald-200'
                                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200'
                                            ]"
                                        >
                                            <span :class="['w-1.5 h-1.5 rounded-full', item.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400']"></span>
                                            <span>{{ item.is_active ? 'Aktif' : 'Non-aktif' }}</span>
                                        </button>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditModal(item)"
                                                class="w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                                title="Edit Kode Klasifikasi"
                                                aria-label="Edit Kode Klasifikasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDelete(item)"
                                                class="w-8 h-8 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                                title="Hapus Klasifikasi"
                                                aria-label="Hapus Klasifikasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredClassifications.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <p class="font-medium text-xs">Tidak ada data klasifikasi surat yang sesuai filter pencarian.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <Modal :show="showFormModal" @close="showFormModal = false" max-width="lg">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <span>{{ isEditing ? 'Edit Klasifikasi Surat' : 'Tambah Klasifikasi Surat Baru' }}</span>
                    </h3>
                    <button type="button" @click="showFormModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="saveClassification" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Kode Surat Resmi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="PD.1.1/FIK-US"
                            class="w-full px-3 py-2 text-xs font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                            required
                        />
                        <p v-if="form.errors.code" class="text-rose-500 text-[10px] mt-1">{{ form.errors.code }}</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Keterangan / Hal Surat <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Hubungan Dengan Dosen"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                            required
                        />
                        <p v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kategori Bidang
                            </label>
                            <input
                                v-model="form.category"
                                type="text"
                                placeholder="Pendidikan (PD.1)"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Urutan Tampilan
                            </label>
                            <input
                                v-model.number="form.order"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Deskripsi / Ruang Lingkup Surat
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Penjelasan jenis permohonan atau lingkup yang menggunakan kode ini..."
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                        ></textarea>
                    </div>

                    <div class="flex items-center pt-2">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 focus:ring-blue-600"
                            />
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                Aktifkan Kode Klasifikasi Ini
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showFormModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Perbarui Klasifikasi' : 'Simpan Klasifikasi') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- DELETE MODAL -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Hapus Kode Klasifikasi?</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Anda akan menghapus kode klasifikasi <strong>{{ classificationToDelete?.code }}</strong> ({{ classificationToDelete?.name }}).
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showDeleteModal = false" class="px-4 py-2 rounded-xl text-xs border border-slate-200 dark:border-slate-700">Batal</button>
                    <button type="button" @click="executeDelete" :disabled="isDeleting" class="px-4 py-2 rounded-xl text-xs bg-rose-600 text-white font-bold hover:bg-rose-500 disabled:opacity-50">
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

